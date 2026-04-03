<?php

namespace App\Services;

use App\Integrations\SelcomService;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(
        protected SelcomService $selcomService,
    ) {}

    /**
     * Initiate a payment for a subscription.
     *
     * Creates a Selcom order and returns the payment record with gateway URL.
     * The user is redirected to Selcom's payment page which handles
     * mobile money (USSD push), cards, and TanQR.
     *
     * @param float|null $amount Override amount (e.g. prorated upgrade). Defaults to subscription amount.
     */
    public function initiatePayment(User $user, Subscription $subscription, ?float $amount = null): Payment
    {
        $chargeAmount = $amount ?? (float) $subscription->amount;
        $reference    = 'ZBL-' . strtoupper(Str::random(10));

        $payment = Payment::create([
            'user_id'         => $user->id,
            'subscription_id' => $subscription->id,
            'amount'          => $chargeAmount,
            'method'          => 'selcom',
            'provider'        => 'selcom',
            'status'          => 'pending',
            'reference'       => $reference,
        ]);

        if (! $this->selcomService->isConfigured()) {
            Log::warning('Selcom not configured — payment created but no order sent', [
                'reference' => $reference,
            ]);
            $payment->update(['metadata' => ['error' => 'Selcom not configured']]);
            return $payment;
        }

        $phone      = $this->normalizePhone($user->phone);
        $webhookUrl = url('/api/payments/callback');
        $frontendUrl = rtrim(config('app.frontend_url', 'http://localhost:5173'), '/');
        $redirectUrl = $frontendUrl . '/subscription?payment=' . $reference . '&status=success';
        $cancelUrl   = $frontendUrl . '/subscription?payment=' . $reference . '&status=cancelled';

        $orderData = [
            'vendor'          => $this->selcomService->getVendor(),
            'order_id'        => $reference,
            'buyer_email'     => $user->email ?? 'noemail@zabunilink.co.tz',
            'buyer_name'      => $user->name,
            'buyer_phone'     => $phone,
            'amount'          => (int) round($chargeAmount),
            'currency'        => 'TZS',
            'payment_methods' => 'ALL',
            'webhook'         => base64_encode($webhookUrl),
            'redirect_url'    => base64_encode($redirectUrl),
            'cancel_url'      => base64_encode($cancelUrl),
            'no_of_items'     => 1,
            'billing.firstname'           => explode(' ', $user->name)[0] ?? $user->name,
            'billing.lastname'            => explode(' ', $user->name, 2)[1] ?? '-',
            'billing.address_1'           => 'Tanzania',
            'billing.city'                => 'Dar es Salaam',
            'billing.state_or_region'     => 'Dar es Salaam',
            'billing.postcode_or_pobox'   => '00000',
            'billing.country'             => 'TZ',
            'billing.phone'               => $phone,
            'merchant_remarks'            => 'ZabuniLink ' . ($subscription->plan->name ?? 'Plan') . ' subscription',
            'payer_remarks'               => 'ZabuniLink subscription payment',
        ];

        $orderResponse = $this->selcomService->createOrder($orderData);

        $gatewayUrl = null;

        if (($orderResponse['result'] ?? '') === 'SUCCESS') {
            // Extract gateway URL from response
            $responseData = $orderResponse['data'] ?? [];
            if (is_array($responseData) && isset($responseData[0])) {
                $responseData = (array) $responseData[0];
            }

            $encodedUrl = $responseData['payment_gateway_url'] ?? $responseData['gateway_buyer_uuid'] ?? null;
            if ($encodedUrl) {
                $gatewayUrl = base64_decode($encodedUrl);
            }

            Log::info('Selcom order created', [
                'reference'   => $reference,
                'gateway_url' => $gatewayUrl ? 'present' : 'missing',
            ]);
        } else {
            Log::error('Selcom create-order failed', [
                'reference' => $reference,
                'response'  => $orderResponse,
            ]);
        }

        $payment->update([
            'metadata' => [
                'gateway_url'          => $gatewayUrl,
                'selcom_order_created' => ($orderResponse['result'] ?? '') === 'SUCCESS',
                'selcom_response'      => $orderResponse,
            ],
        ]);

        return $payment;
    }

    /**
     * Handle the payment callback from Selcom.
     */
    public function handleCallback(array $data): Payment
    {
        $orderId       = $data['order_id'] ?? $data['reference'] ?? '';
        $transactionId = $data['transaction_id'] ?? $data['transid'] ?? null;
        $result        = $data['result'] ?? $data['payment_status'] ?? '';
        $callbackAmount = (float) ($data['amount'] ?? 0);

        $payment = Payment::where('reference', $orderId)->firstOrFail();

        // Already processed
        if ($payment->status === 'completed') {
            return $payment;
        }

        $status = in_array(strtoupper($result), ['SUCCESS', 'COMPLETED']) ? 'completed' : 'failed';

        // Verify amount (allow TZS 1 rounding tolerance)
        if ($status === 'completed' && $callbackAmount > 0) {
            if (abs((float) $payment->amount - $callbackAmount) > 1) {
                Log::error('Selcom callback amount mismatch', [
                    'reference'       => $orderId,
                    'expected_amount' => $payment->amount,
                    'received_amount' => $callbackAmount,
                ]);
                $status = 'failed';
            }
        }

        $payment->update([
            'status'         => $status,
            'transaction_id' => $transactionId,
            'metadata'       => array_merge($payment->metadata ?? [], ['callback' => $data]),
        ]);

        if ($status === 'completed') {
            $this->activateSubscription($payment);
        }

        return $payment;
    }

    /**
     * Check payment status by polling Selcom.
     */
    public function checkPaymentStatus(Payment $payment): Payment
    {
        if ($payment->status === 'completed') {
            return $payment;
        }

        if (! $this->selcomService->isConfigured()) {
            return $payment;
        }

        $response = $this->selcomService->checkOrderStatus($payment->reference);

        $data = $response['data'] ?? [];
        if (is_array($data) && isset($data[0])) {
            $data = (array) $data[0];
        }

        $paymentStatus = $data['payment_status'] ?? $data['order_status'] ?? '';

        if (in_array(strtoupper($paymentStatus), ['COMPLETED', 'SUCCESS', 'PAID'])) {
            $transId = $data['transaction_id'] ?? $data['transid'] ?? $payment->transaction_id;

            $payment->update([
                'status'         => 'completed',
                'transaction_id' => $transId,
                'metadata'       => array_merge($payment->metadata ?? [], ['status_check' => $response]),
            ]);

            $this->activateSubscription($payment);
        } elseif (in_array(strtoupper($paymentStatus), ['FAILED', 'CANCELLED', 'EXPIRED'])) {
            $payment->update([
                'status'   => 'failed',
                'metadata' => array_merge($payment->metadata ?? [], ['status_check' => $response]),
            ]);
        }

        return $payment->fresh();
    }

    /**
     * Activate subscription and send notifications after successful payment.
     */
    protected function activateSubscription(Payment $payment): void
    {
        $subscription = $payment->subscription;

        if ($subscription->status === 'active') {
            return;
        }

        $subscription->update(['status' => 'active']);

        try {
            $user = $payment->user;
            $messageService = app(SystemMessageService::class);
            $messageService->sendPaymentReceipt($user, $payment);
            $messageService->sendSubscriptionConfirmed($user, $subscription);
        } catch (\Throwable $e) {
            Log::warning('System message failed after payment', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Normalize phone to 255XXXXXXXXX.
     */
    protected function normalizePhone(?string $phone): string
    {
        if (! $phone) {
            return '';
        }

        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '255' . substr($phone, 1);
        }

        if (! str_starts_with($phone, '255')) {
            $phone = '255' . $phone;
        }

        return $phone;
    }
}
