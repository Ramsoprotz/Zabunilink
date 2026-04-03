<?php

namespace App\Services;

use App\Integrations\NextSmsService;
use App\Mail\TemplateMail;
use App\Models\NotificationTemplate;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SystemMessageService
{
    public function __construct(
        protected NextSmsService $smsService,
    ) {}

    // ── Welcome ─────────────────────────────────────────────────

    public function sendWelcome(User $user): void
    {
        $data = [
            'user_name'     => $user->name,
            'user_email'    => $user->email,
            'business_name' => $user->business_name ?? '-',
            'app_url'       => $this->appUrl(),
        ];

        $this->send('welcome', $user, $data);
    }

    // ── Password Reset ──────────────────────────────────────────

    public function sendPasswordResetOtp(User $user, string $otp): void
    {
        $data = [
            'user_name' => $user->name,
            'otp'       => $otp,
        ];

        $this->send('password_reset', $user, $data);
    }

    // ── Subscription Confirmed ──────────────────────────────────

    public function sendSubscriptionConfirmed(User $user, Subscription $subscription): void
    {
        $subscription->load('plan');

        $data = [
            'user_name'     => $user->name,
            'plan_name'     => $subscription->plan->name,
            'billing_cycle' => ucfirst(str_replace('_', '-', $subscription->billing_cycle)),
            'amount'        => number_format($subscription->amount, 0),
            'start_date'    => $subscription->start_date->format('d M Y'),
            'end_date'      => $subscription->end_date->format('d M Y'),
            'app_url'       => $this->appUrl(),
        ];

        $this->send('subscription_confirmed', $user, $data);
    }

    // ── Payment Receipt ─────────────────────────────────────────

    public function sendPaymentReceipt(User $user, Payment $payment): void
    {
        $data = [
            'user_name'      => $user->name,
            'reference'      => $payment->reference,
            'amount'         => number_format($payment->amount, 0),
            'method'         => ucfirst(str_replace('_', ' ', $payment->method)),
            'transaction_id' => $payment->transaction_id ?? '-',
            'date'           => $payment->updated_at->format('d M Y, H:i'),
        ];

        $this->send('payment_receipt', $user, $data);
    }

    // ── Trial Started ───────────────────────────────────────────

    public function sendTrialStarted(User $user, Subscription $subscription): void
    {
        $subscription->load('plan');

        $data = [
            'user_name'  => $user->name,
            'plan_name'  => $subscription->plan->name,
            'start_date' => $subscription->start_date->format('d M Y'),
            'end_date'   => $subscription->end_date->format('d M Y'),
            'trial_days' => $subscription->start_date->diffInDays($subscription->end_date),
            'app_url'    => $this->appUrl(),
        ];

        $this->send('trial_started', $user, $data);
    }

    // ── Trial Expiring ──────────────────────────────────────────

    public function sendTrialExpiring(User $user, Subscription $subscription, int $daysLeft): void
    {
        $data = [
            'user_name' => $user->name,
            'days_left' => $daysLeft,
            'end_date'  => $subscription->end_date->format('d M Y'),
            'app_url'   => $this->appUrl(),
        ];

        $this->send('trial_expiring', $user, $data);
    }

    // ── Subscription Expiring ───────────────────────────────────

    public function sendSubscriptionExpiring(User $user, Subscription $subscription, int $daysLeft): void
    {
        $subscription->load('plan');

        $data = [
            'user_name'     => $user->name,
            'plan_name'     => $subscription->plan->name,
            'billing_cycle' => ucfirst(str_replace('_', '-', $subscription->billing_cycle)),
            'days_left'     => $daysLeft,
            'end_date'      => $subscription->end_date->format('d M Y'),
            'app_url'       => $this->appUrl(),
        ];

        $this->send('subscription_expiring', $user, $data);
    }

    // ── Core Send Logic ─────────────────────────────────────────

    /**
     * Send a notification using templates, respecting user locale.
     */
    protected function send(string $type, User $user, array $data): void
    {
        $locale = $this->getUserLocale($user);

        // Email
        if ($user->email) {
            $rendered = NotificationTemplate::render($type, 'email', $locale, $data);
            if ($rendered) {
                try {
                    Mail::to($user->email)->queue(new TemplateMail(
                        $rendered['subject'] ?? 'ZabuniLink',
                        $rendered['body'],
                    ));
                } catch (\Throwable $e) {
                    Log::warning("System email failed [{$type}]", ['error' => $e->getMessage()]);
                }
            }
        }

        // SMS
        if ($user->phone) {
            $rendered = NotificationTemplate::render($type, 'sms', $locale, $data);
            if ($rendered) {
                $this->sms($user->phone, $rendered['body']);
            }
        }
    }

    /**
     * Get the user's notification language preference.
     */
    protected function getUserLocale(User $user): string
    {
        return $user->notificationPreference?->locale ?? 'en';
    }

    /**
     * Send SMS with error handling.
     */
    protected function sms(string $phone, string $message): void
    {
        try {
            $this->smsService->sendSms($phone, $message);
        } catch (\Throwable $e) {
            Log::warning('System SMS failed', ['phone' => $phone, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Get the frontend app URL.
     */
    protected function appUrl(): string
    {
        return rtrim(config('app.frontend_url', 'http://localhost:5173'), '/');
    }
}
