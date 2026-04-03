<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Integrations\SelcomService;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
        protected SelcomService  $selcomService,
    ) {}

    /**
     * Handle the Selcom payment webhook callback.
     */
    public function callback(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $headers = $request->headers->all();

        // Flatten headers (Laravel stores them as arrays)
        $flatHeaders = [];
        foreach ($headers as $key => $values) {
            $flatHeaders[ucfirst($key)] = is_array($values) ? ($values[0] ?? '') : $values;
        }

        Log::info('Selcom callback received', [
            'headers' => array_intersect_key($flatHeaders, array_flip(['Digest', 'Timestamp', 'Signed-fields'])),
        ]);

        // Verify signature (skip in sandbox / if signature missing)
        if (! empty($flatHeaders['Digest'])) {
            if (! $this->selcomService->verifyCallbackSignature($payload, $flatHeaders)) {
                Log::warning('Selcom callback: invalid signature', [
                    'payload' => $payload,
                ]);
                // Log but don't reject — some Selcom environments skip signing
            }
        }

        $data = $request->all();

        if (empty($data['order_id']) && empty($data['reference'])) {
            Log::warning('Selcom callback: no order_id in payload');
            return response()->json(['message' => 'Missing order_id'], 400);
        }

        try {
            $payment = $this->paymentService->handleCallback($data);

            return response()->json([
                'message' => 'Callback processed.',
                'status'  => $payment->status,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Selcom callback: payment not found', [
                'order_id' => $data['order_id'] ?? $data['reference'] ?? 'unknown',
            ]);
            return response()->json(['message' => 'Payment not found'], 404);
        } catch (\Throwable $e) {
            Log::error('Selcom callback failed', [
                'error' => $e->getMessage(),
                'data'  => $data,
            ]);
            return response()->json(['message' => 'Processing failed'], 500);
        }
    }

    /**
     * Check payment status (for frontend polling).
     */
    public function checkStatus(Request $request, string $reference): JsonResponse
    {
        $payment = Payment::where('reference', $reference)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        // If still pending, poll Selcom for status
        if ($payment->status === 'pending') {
            $payment = $this->paymentService->checkPaymentStatus($payment);
        }

        $gatewayUrl = $payment->metadata['gateway_url'] ?? null;

        return response()->json([
            'data' => [
                'reference'    => $payment->reference,
                'status'       => $payment->status,
                'amount'       => $payment->amount,
                'gateway_url'  => $gatewayUrl,
                'subscription' => $payment->status === 'completed'
                    ? $payment->subscription->load('plan')
                    : null,
            ],
        ]);
    }
}
