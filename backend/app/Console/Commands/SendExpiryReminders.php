<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\SystemMessageService;
use Illuminate\Console\Command;

class SendExpiryReminders extends Command
{
    protected $signature = 'subscriptions:send-expiry-reminders';
    protected $description = 'Send reminders for subscriptions and trials expiring soon';

    public function handle(SystemMessageService $messageService): int
    {
        $count = 0;

        // Trial expiring in 3 days
        $trialsSoon = Subscription::where('status', 'active')
            ->where('is_trial', true)
            ->whereDate('end_date', now()->addDays(3)->toDateString())
            ->with(['user', 'plan'])
            ->get();

        foreach ($trialsSoon as $sub) {
            $messageService->sendTrialExpiring($sub->user, $sub, 3);
            $count++;
        }

        // Trial expiring in 1 day
        $trialsUrgent = Subscription::where('status', 'active')
            ->where('is_trial', true)
            ->whereDate('end_date', now()->addDay()->toDateString())
            ->with(['user', 'plan'])
            ->get();

        foreach ($trialsUrgent as $sub) {
            $messageService->sendTrialExpiring($sub->user, $sub, 1);
            $count++;
        }

        // Subscriptions expiring in 7 days
        $subsSoon = Subscription::where('status', 'active')
            ->where('is_trial', false)
            ->whereDate('end_date', now()->addDays(7)->toDateString())
            ->with(['user', 'plan'])
            ->get();

        foreach ($subsSoon as $sub) {
            $messageService->sendSubscriptionExpiring($sub->user, $sub, 7);
            $count++;
        }

        // Subscriptions expiring in 3 days
        $subsUrgent = Subscription::where('status', 'active')
            ->where('is_trial', false)
            ->whereDate('end_date', now()->addDays(3)->toDateString())
            ->with(['user', 'plan'])
            ->get();

        foreach ($subsUrgent as $sub) {
            $messageService->sendSubscriptionExpiring($sub->user, $sub, 3);
            $count++;
        }

        // Subscriptions expiring in 1 day
        $subsFinal = Subscription::where('status', 'active')
            ->where('is_trial', false)
            ->whereDate('end_date', now()->addDay()->toDateString())
            ->with(['user', 'plan'])
            ->get();

        foreach ($subsFinal as $sub) {
            $messageService->sendSubscriptionExpiring($sub->user, $sub, 1);
            $count++;
        }

        $this->info("Sent {$count} expiry reminder(s).");

        return self::SUCCESS;
    }
}
