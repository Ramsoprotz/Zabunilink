<?php

namespace App\Services;

use App\Integrations\NextSmsService;
use App\Mail\TemplateMail;
use App\Models\Favorite;
use App\Models\NotificationPreference;
use App\Models\NotificationTemplate;
use App\Models\Tender;
use App\Models\TenderNotification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function __construct(
        protected NextSmsService $smsService,
    ) {}

    /**
     * Notify users about a new tender based on their preferences.
     */
    public function notifyNewTender(Tender $tender): void
    {
        $preferences = NotificationPreference::whereJsonContains('category_ids', $tender->category_id)
            ->whereJsonContains('location_ids', $tender->location_id)
            ->with('user')
            ->get();

        foreach ($preferences as $preference) {
            $user   = $preference->user;
            $locale = $preference->locale ?? 'en';
            $title  = 'New Tender: ' . $tender->title;

            $data = [
                'user_name'    => $user->name,
                'tender_title' => $tender->title,
                'organization' => $tender->organization,
                'deadline'     => $tender->deadline->format('d M Y'),
                'tender_link'  => $this->tenderUrl($tender->id),
            ];

            if ($preference->email_enabled && $user->email) {
                $this->sendTemplateEmail('new_tender', $locale, $user->email, $data);
                $this->createNotificationRecord($user, $tender, 'new_tender', 'email', $title, $data['tender_title']);
            }

            if ($preference->sms_enabled && $user->phone) {
                $this->sendTemplateSms('new_tender', $locale, $user->phone, $data);
                $this->createNotificationRecord($user, $tender, 'new_tender', 'sms', $title, $data['tender_title']);
            }

            if ($preference->push_enabled && $user->fcm_token) {
                $this->createNotificationRecord($user, $tender, 'new_tender', 'push', $title, $data['tender_title']);
            }
        }
    }

    /**
     * Notify users who favorited a tender about an update.
     */
    public function notifyTenderUpdate(Tender $tender): void
    {
        $favorites = Favorite::where('tender_id', $tender->id)
            ->with('user.notificationPreference')
            ->get();

        foreach ($favorites as $favorite) {
            $user       = $favorite->user;
            $preference = $user->notificationPreference;
            $locale     = $preference->locale ?? 'en';
            $title      = 'Tender Updated: ' . $tender->title;

            $data = [
                'user_name'    => $user->name,
                'tender_title' => $tender->title,
                'tender_link'  => $this->tenderUrl($tender->id),
            ];

            if ($preference && $preference->email_enabled && $user->email) {
                $this->sendTemplateEmail('tender_update', $locale, $user->email, $data);
                $this->createNotificationRecord($user, $tender, 'tender_update', 'email', $title, $data['tender_title']);
            }

            if ($preference && $preference->sms_enabled && $user->phone) {
                $this->sendTemplateSms('tender_update', $locale, $user->phone, $data);
                $this->createNotificationRecord($user, $tender, 'tender_update', 'sms', $title, $data['tender_title']);
            }
        }
    }

    /**
     * Send deadline reminders for tenders closing in 3 days.
     */
    public function sendDeadlineReminders(): void
    {
        $tenders = Tender::where('status', 'open')
            ->whereDate('deadline', now()->addDays(3)->toDateString())
            ->get();

        foreach ($tenders as $tender) {
            $favorites = Favorite::where('tender_id', $tender->id)
                ->with('user.notificationPreference')
                ->get();

            foreach ($favorites as $favorite) {
                $user       = $favorite->user;
                $preference = $user->notificationPreference;
                $locale     = $preference->locale ?? 'en';
                $title      = 'Deadline Reminder: ' . $tender->title;

                $data = [
                    'user_name'    => $user->name,
                    'tender_title' => $tender->title,
                    'deadline'     => $tender->deadline->format('d M Y'),
                    'tender_link'  => $this->tenderUrl($tender->id),
                ];

                if ($preference && $preference->email_enabled && $user->email) {
                    $this->sendTemplateEmail('deadline_reminder', $locale, $user->email, $data);
                    $this->createNotificationRecord($user, $tender, 'deadline_reminder', 'email', $title, $data['tender_title']);
                }

                if ($preference && $preference->sms_enabled && $user->phone) {
                    $this->sendTemplateSms('deadline_reminder', $locale, $user->phone, $data);
                    $this->createNotificationRecord($user, $tender, 'deadline_reminder', 'sms', $title, $data['tender_title']);
                }
            }
        }
    }

    /**
     * Send a new application notification to the tender owner.
     */
    public function notifyNewApplication(Tender $tender, User $owner, User $applicant, $application): void
    {
        $locale = $owner->notificationPreference?->locale ?? 'en';

        $data = [
            'owner_name'        => $owner->name,
            'tender_title'      => $tender->title,
            'applicant_name'    => $applicant->name,
            'applicant_business' => $applicant->business_name ?? '-',
            'applicant_email'   => $applicant->email,
            'applied_at'        => $application->created_at->format('d M Y, H:i'),
            'application_link'  => $this->appUrl() . '/business/tenders/' . $tender->id . '/applications',
        ];

        if ($owner->email) {
            $this->sendTemplateEmail('new_application', $locale, $owner->email, $data);
        }

        if ($owner->phone) {
            $this->sendTemplateSms('new_application', $locale, $owner->phone, $data);
        }
    }

    /**
     * Send application status change notification to the applicant.
     */
    public function notifyApplicationStatus(Tender $tender, User $applicant, string $status): void
    {
        $locale = $applicant->notificationPreference?->locale ?? 'en';

        $data = [
            'applicant_name' => $applicant->name,
            'tender_title'   => $tender->title,
            'status'         => ucfirst($status),
            'tender_link'    => $this->tenderUrl($tender->id),
        ];

        if ($applicant->email) {
            $this->sendTemplateEmail('application_status', $locale, $applicant->email, $data);
        }

        if ($applicant->phone) {
            $this->sendTemplateSms('application_status', $locale, $applicant->phone, $data);
        }
    }

    // ── Template-based sending ──────────────────────────────────

    protected function sendTemplateEmail(string $type, string $locale, string $email, array $data): void
    {
        $rendered = NotificationTemplate::render($type, 'email', $locale, $data);
        if (! $rendered) {
            return;
        }

        try {
            Mail::to($email)->queue(new TemplateMail(
                $rendered['subject'] ?? 'ZabuniLink',
                $rendered['body'],
            ));
        } catch (\Throwable $e) {
            Log::warning("Notification email failed [{$type}]", ['error' => $e->getMessage()]);
        }
    }

    protected function sendTemplateSms(string $type, string $locale, string $phone, array $data): void
    {
        $rendered = NotificationTemplate::render($type, 'sms', $locale, $data);
        if (! $rendered) {
            return;
        }

        try {
            $this->smsService->sendSms($phone, $rendered['body']);
        } catch (\Throwable $e) {
            Log::warning("Notification SMS failed [{$type}]", ['phone' => $phone, 'error' => $e->getMessage()]);
        }
    }

    // ── Helpers ─────────────────────────────────────────────────

    protected function createNotificationRecord(
        User $user,
        Tender $tender,
        string $type,
        string $channel,
        string $title,
        string $message,
    ): TenderNotification {
        return TenderNotification::create([
            'user_id'   => $user->id,
            'tender_id' => $tender->id,
            'type'      => $type,
            'channel'   => $channel,
            'title'     => $title,
            'message'   => $message,
            'status'    => 'sent',
            'sent_at'   => now(),
        ]);
    }

    protected function tenderUrl(int $tenderId): string
    {
        return $this->appUrl() . '/tenders/' . $tenderId;
    }

    protected function appUrl(): string
    {
        return rtrim(config('app.frontend_url', 'http://localhost:5173'), '/');
    }
}
