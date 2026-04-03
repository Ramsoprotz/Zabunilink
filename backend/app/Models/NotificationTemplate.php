<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class NotificationTemplate extends Model
{
    protected $fillable = ['type', 'channel', 'locale', 'subject', 'body'];

    /**
     * All notification types with their available placeholders.
     */
    public const TYPES = [
        'welcome' => [
            'label'        => 'Welcome',
            'placeholders' => ['user_name', 'user_email', 'business_name', 'app_url'],
        ],
        'password_reset' => [
            'label'        => 'Password Reset',
            'placeholders' => ['user_name', 'otp'],
        ],
        'subscription_confirmed' => [
            'label'        => 'Subscription Confirmed',
            'placeholders' => ['user_name', 'plan_name', 'billing_cycle', 'amount', 'start_date', 'end_date', 'app_url'],
        ],
        'payment_receipt' => [
            'label'        => 'Payment Receipt',
            'placeholders' => ['user_name', 'reference', 'amount', 'method', 'transaction_id', 'date'],
        ],
        'trial_started' => [
            'label'        => 'Trial Started',
            'placeholders' => ['user_name', 'plan_name', 'start_date', 'end_date', 'trial_days', 'app_url'],
        ],
        'trial_expiring' => [
            'label'        => 'Trial Expiring',
            'placeholders' => ['user_name', 'days_left', 'end_date', 'app_url'],
        ],
        'subscription_expiring' => [
            'label'        => 'Subscription Expiring',
            'placeholders' => ['user_name', 'plan_name', 'billing_cycle', 'days_left', 'end_date', 'app_url'],
        ],
        'new_tender' => [
            'label'        => 'New Tender Alert',
            'placeholders' => ['user_name', 'tender_title', 'organization', 'deadline', 'tender_link'],
        ],
        'tender_update' => [
            'label'        => 'Tender Updated',
            'placeholders' => ['user_name', 'tender_title', 'tender_link'],
        ],
        'deadline_reminder' => [
            'label'        => 'Deadline Reminder',
            'placeholders' => ['user_name', 'tender_title', 'deadline', 'tender_link'],
        ],
        'new_application' => [
            'label'        => 'New Application Received',
            'placeholders' => ['owner_name', 'tender_title', 'applicant_name', 'applicant_business', 'applicant_email', 'applied_at', 'application_link'],
        ],
        'application_status' => [
            'label'        => 'Application Status Changed',
            'placeholders' => ['applicant_name', 'tender_title', 'status', 'tender_link'],
        ],
    ];

    /**
     * Get a rendered template for a given type, channel, and locale.
     */
    public static function render(string $type, string $channel, string $locale, array $data): ?array
    {
        $template = Cache::remember(
            "notif_tpl:{$type}:{$channel}:{$locale}",
            3600,
            fn () => static::where('type', $type)
                ->where('channel', $channel)
                ->where('locale', $locale)
                ->first(),
        );

        if (! $template) {
            return null;
        }

        $body = $template->body;
        $subject = $template->subject;

        // Replace placeholders: {{key}} → value
        foreach ($data as $key => $value) {
            $body = str_replace("{{{$key}}}", (string) $value, $body);
            if ($subject) {
                $subject = str_replace("{{{$key}}}", (string) $value, $subject);
            }
        }

        return [
            'subject' => $subject,
            'body'    => $body,
        ];
    }

    /**
     * Clear the template cache for a specific template or all.
     */
    public static function clearCache(?string $type = null, ?string $channel = null, ?string $locale = null): void
    {
        if ($type && $channel && $locale) {
            Cache::forget("notif_tpl:{$type}:{$channel}:{$locale}");
            return;
        }

        // Clear all template caches
        foreach (array_keys(self::TYPES) as $t) {
            foreach (['email', 'sms'] as $c) {
                foreach (['en', 'sw'] as $l) {
                    Cache::forget("notif_tpl:{$t}:{$c}:{$l}");
                }
            }
        }
    }
}
