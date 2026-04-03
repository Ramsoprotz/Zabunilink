<?php

namespace App\Console\Commands;

use App\Services\MembershipService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('subscriptions:apply-scheduled-changes')]
#[Description('Apply scheduled plan downgrades that have reached their effective date')]
class ApplyScheduledPlanChanges extends Command
{
    public function handle(MembershipService $membershipService): int
    {
        $count = $membershipService->applyScheduledChanges();

        $this->info("Applied {$count} scheduled plan change(s).");

        return self::SUCCESS;
    }
}
