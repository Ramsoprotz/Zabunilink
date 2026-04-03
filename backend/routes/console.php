<?php

use App\Jobs\SendDeadlineReminders;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new SendDeadlineReminders())->dailyAt('08:00');
Schedule::command('subscriptions:apply-scheduled-changes')->dailyAt('00:05');
Schedule::command('subscriptions:send-expiry-reminders')->dailyAt('09:00');
