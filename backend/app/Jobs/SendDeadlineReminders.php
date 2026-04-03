<?php

namespace App\Jobs;

use App\Integrations\NextSmsService;
use App\Mail\DeadlineReminderMail;
use App\Models\Favorite;
use App\Models\Tender;
use App\Models\TenderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendDeadlineReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct() {}

    /**
     * Execute the job.
     */
    public function handle(NextSmsService $smsService): void
    {
        $tenders = Tender::where('status', 'open')
            ->whereDate('deadline', now()->addDays(3)->toDateString())
            ->get();

        foreach ($tenders as $tender) {
            $favorites = Favorite::where('tender_id', $tender->id)
                ->with('user.notificationPreference')
                ->get();

            foreach ($favorites as $favorite) {
                $user = $favorite->user;
                $preference = $user->notificationPreference;

                if (!$preference) {
                    continue;
                }

                $title = 'Deadline Reminder: ' . $tender->title;
                $message = "The tender \"{$tender->title}\" closes in 3 days on {$tender->deadline->format('d M Y')}. Don't miss the deadline!";

                if ($preference->email_enabled && $user->email) {
                    Mail::to($user->email)->queue(new DeadlineReminderMail($tender, $user));

                    TenderNotification::create([
                        'user_id' => $user->id,
                        'tender_id' => $tender->id,
                        'type' => 'deadline_reminder',
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
                        'type' => 'deadline_reminder',
                        'channel' => 'sms',
                        'title' => $title,
                        'message' => $message,
                        'status' => 'sent',
                        'sent_at' => now(),
                    ]);
                }
            }
        }
    }
}
