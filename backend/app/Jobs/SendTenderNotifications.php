<?php

namespace App\Jobs;

use App\Integrations\NextSmsService;
use App\Mail\NewTenderMail;
use App\Models\NotificationPreference;
use App\Models\Tender;
use App\Models\TenderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendTenderNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Tender $tender,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(NextSmsService $smsService): void
    {
        $tender = $this->tender->load(['category', 'location']);

        $preferences = NotificationPreference::whereJsonContains('category_ids', $tender->category_id)
            ->whereJsonContains('location_ids', $tender->location_id)
            ->with('user')
            ->get();

        foreach ($preferences as $preference) {
            $user = $preference->user;
            $title = 'New Tender: ' . $tender->title;
            $message = "A new tender \"{$tender->title}\" has been posted by {$tender->organization}. Deadline: {$tender->deadline->format('d M Y')}.";

            if ($preference->email_enabled && $user->email) {
                Mail::to($user->email)->queue(new NewTenderMail($tender, $user));

                TenderNotification::create([
                    'user_id' => $user->id,
                    'tender_id' => $tender->id,
                    'type' => 'new_tender',
                    'channel' => 'email',
                    'title' => $title,
                    'message' => $message,
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
            }

            if ($preference->sms_enabled && $user->phone) {
                $smsService->sendSms($user->phone, $message);

                TenderNotification::create([
                    'user_id' => $user->id,
                    'tender_id' => $tender->id,
                    'type' => 'new_tender',
                    'channel' => 'sms',
                    'title' => $title,
                    'message' => $message,
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
            }

            if ($preference->push_enabled && $user->fcm_token) {
                TenderNotification::create([
                    'user_id' => $user->id,
                    'tender_id' => $tender->id,
                    'type' => 'new_tender',
                    'channel' => 'push',
                    'title' => $title,
                    'message' => $message,
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
            }
        }
    }
}
