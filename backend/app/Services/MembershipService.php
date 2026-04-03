<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MembershipService
{
    /**
     * Subscribe a user to a plan (first-time or no active subscription).
     */
    public function subscribe(User $user, int $planId, string $billingCycle): Subscription
    {
        $plan = Plan::findOrFail($planId);

        $startDate = Carbon::now();
        $endDate = $this->calculateEndDate($startDate, $billingCycle);
        $amount = $plan->getPriceForCycle($billingCycle);

        return Subscription::create([
            'user_id'       => $user->id,
            'plan_id'       => $plan->id,
            'billing_cycle' => $billingCycle,
            'amount'        => $amount,
            'start_date'    => $startDate,
            'end_date'      => $endDate,
            'status'        => 'pending',
        ]);
    }

    /**
     * Change a user's plan (upgrade or downgrade).
     *
     * Upgrades: immediate, prorated credit applied.
     * Downgrades: scheduled for end of current billing period.
     *
     * Returns an array with the action taken and relevant data.
     */
    public function changePlan(User $user, int $newPlanId, string $billingCycle): array
    {
        $currentSub = $user->activeSubscription;
        if (! $currentSub) {
            // No active subscription — treat as new subscription
            $sub = $this->subscribe($user, $newPlanId, $billingCycle);
            return ['action' => 'new_subscription', 'subscription' => $sub, 'amount_due' => $sub->amount];
        }

        $currentPlan = $currentSub->plan;
        $newPlan = Plan::findOrFail($newPlanId);

        if ($currentPlan->id === $newPlan->id && $currentSub->billing_cycle === $billingCycle) {
            throw new \InvalidArgumentException('You are already on this plan and billing cycle.');
        }

        $isUpgrade = $newPlan->tier > $currentPlan->tier
            || ($newPlan->tier === $currentPlan->tier && $newPlan->getPriceForCycle($billingCycle) > $currentPlan->getPriceForCycle($currentSub->billing_cycle));

        if ($isUpgrade) {
            return $this->processUpgrade($user, $currentSub, $newPlan, $billingCycle);
        }

        return $this->scheduleDowngrade($currentSub, $newPlan, $billingCycle);
    }

    /**
     * Process an immediate upgrade with proration.
     */
    protected function processUpgrade(User $user, Subscription $currentSub, Plan $newPlan, string $billingCycle): array
    {
        $credit = $this->calculateProrationCredit($currentSub);
        $newAmount = $newPlan->getPriceForCycle($billingCycle);
        $amountDue = max(0, $newAmount - $credit);

        $newSub = DB::transaction(function () use ($user, $currentSub, $newPlan, $billingCycle, $newAmount, $credit) {
            // Cancel current subscription immediately
            $currentSub->update([
                'status'              => 'cancelled',
                'scheduled_plan_id'   => null,
                'scheduled_billing_cycle' => null,
            ]);

            $startDate = Carbon::now();
            $endDate = $this->calculateEndDate($startDate, $billingCycle);

            // Create new subscription
            return Subscription::create([
                'user_id'                  => $user->id,
                'plan_id'                  => $newPlan->id,
                'billing_cycle'            => $billingCycle,
                'amount'                   => $newAmount,
                'start_date'               => $startDate,
                'end_date'                 => $endDate,
                'status'                   => 'pending',
                'previous_subscription_id' => $currentSub->id,
                'proration_credit'         => $credit,
            ]);
        });

        return [
            'action'       => 'upgrade',
            'subscription' => $newSub->load('plan'),
            'credit'       => $credit,
            'amount_due'   => $amountDue,
        ];
    }

    /**
     * Schedule a downgrade for the end of the current billing period.
     */
    protected function scheduleDowngrade(Subscription $currentSub, Plan $newPlan, string $billingCycle): array
    {
        $currentSub->update([
            'scheduled_plan_id'       => $newPlan->id,
            'scheduled_billing_cycle' => $billingCycle,
        ]);

        $currentSub->load('scheduledPlan');

        return [
            'action'         => 'downgrade_scheduled',
            'subscription'   => $currentSub,
            'effective_date' => $currentSub->end_date->toDateString(),
            'new_plan'       => $newPlan->name,
        ];
    }

    /**
     * Cancel a scheduled downgrade.
     */
    public function cancelScheduledChange(Subscription $subscription): Subscription
    {
        $subscription->update([
            'scheduled_plan_id'       => null,
            'scheduled_billing_cycle' => null,
        ]);

        return $subscription->fresh();
    }

    /**
     * Apply scheduled downgrades that have reached their effective date.
     * Called by a scheduled command (e.g. daily).
     */
    public function applyScheduledChanges(): int
    {
        $subscriptions = Subscription::where('status', 'active')
            ->whereNotNull('scheduled_plan_id')
            ->where('end_date', '<=', now())
            ->with('scheduledPlan')
            ->get();

        $count = 0;
        foreach ($subscriptions as $sub) {
            $newPlan = $sub->scheduledPlan;
            $billingCycle = $sub->scheduled_billing_cycle ?? $sub->billing_cycle;

            DB::transaction(function () use ($sub, $newPlan, $billingCycle) {
                $sub->update(['status' => 'expired']);

                $startDate = Carbon::now();
                Subscription::create([
                    'user_id'                  => $sub->user_id,
                    'plan_id'                  => $newPlan->id,
                    'billing_cycle'            => $billingCycle,
                    'amount'                   => $newPlan->getPriceForCycle($billingCycle),
                    'start_date'               => $startDate,
                    'end_date'                 => $this->calculateEndDate($startDate, $billingCycle),
                    'status'                   => 'pending',
                    'previous_subscription_id' => $sub->id,
                ]);
            });

            $count++;
        }

        return $count;
    }

    /**
     * Calculate prorated credit for unused days on the current subscription.
     */
    public function calculateProrationCredit(Subscription $subscription): float
    {
        $startDate = $subscription->start_date;
        $endDate = $subscription->end_date;
        $now = Carbon::now();

        // If subscription hasn't started or already ended, no credit
        if ($now->lt($startDate) || $now->gte($endDate)) {
            return 0;
        }

        $totalDays = $startDate->diffInDays($endDate);
        if ($totalDays === 0) {
            return 0;
        }

        $usedDays = $startDate->diffInDays($now);
        $remainingDays = $totalDays - $usedDays;

        $dailyRate = (float) $subscription->amount / $totalDays;

        return round($dailyRate * $remainingDays, 2);
    }

    /**
     * Preview what a plan change would cost (for the frontend).
     */
    public function previewPlanChange(User $user, int $newPlanId, string $billingCycle): array
    {
        $currentSub = $user->activeSubscription;
        $newPlan = Plan::findOrFail($newPlanId);
        $newAmount = $newPlan->getPriceForCycle($billingCycle);

        if (! $currentSub) {
            return [
                'action'     => 'new_subscription',
                'credit'     => 0,
                'new_amount' => $newAmount,
                'amount_due' => $newAmount,
            ];
        }

        $currentPlan = $currentSub->plan;

        $isUpgrade = $newPlan->tier > $currentPlan->tier
            || ($newPlan->tier === $currentPlan->tier && $newPlan->getPriceForCycle($billingCycle) > $currentPlan->getPriceForCycle($currentSub->billing_cycle));

        if ($isUpgrade) {
            $credit = $this->calculateProrationCredit($currentSub);
            return [
                'action'     => 'upgrade',
                'credit'     => $credit,
                'new_amount' => $newAmount,
                'amount_due' => max(0, $newAmount - $credit),
            ];
        }

        return [
            'action'         => 'downgrade_scheduled',
            'credit'         => 0,
            'new_amount'     => $newAmount,
            'amount_due'     => 0,
            'effective_date' => $currentSub->end_date->toDateString(),
        ];
    }

    // ── Free Trial ──────────────────────────────────────────────

    /**
     * Check if free trials are currently enabled by admin.
     */
    public function isTrialEnabled(): bool
    {
        return (bool) Setting::get('free_trial_enabled', false);
    }

    /**
     * Get the configured trial duration in days.
     */
    public function getTrialDays(): int
    {
        return (int) Setting::get('free_trial_days', 14);
    }

    /**
     * Check if a user has already used their free trial.
     */
    public function hasUsedTrial(User $user): bool
    {
        return $user->subscriptions()->where('is_trial', true)->exists();
    }

    /**
     * Start a free trial for the Basic plan.
     */
    public function startTrial(User $user): Subscription
    {
        if (! $this->isTrialEnabled()) {
            throw new \InvalidArgumentException('Free trials are not currently available.');
        }

        if ($this->hasUsedTrial($user)) {
            throw new \InvalidArgumentException('You have already used your free trial.');
        }

        if ($user->activeSubscription) {
            throw new \InvalidArgumentException('You already have an active subscription.');
        }

        $basicPlan = Plan::where('name', 'Basic')->where('is_active', true)->firstOrFail();
        $trialDays = $this->getTrialDays();

        $startDate = Carbon::now();
        $endDate = $startDate->copy()->addDays($trialDays);

        return Subscription::create([
            'user_id'       => $user->id,
            'plan_id'       => $basicPlan->id,
            'billing_cycle' => 'monthly',
            'amount'        => 0,
            'start_date'    => $startDate,
            'end_date'      => $endDate,
            'status'        => 'active',
            'is_trial'      => true,
        ]);
    }

    /**
     * Get trial configuration for the public API.
     */
    public function getTrialConfig(): array
    {
        $enabled = $this->isTrialEnabled();

        return [
            'enabled' => $enabled,
            'days'    => $enabled ? $this->getTrialDays() : null,
        ];
    }

    /**
     * Cancel an active subscription.
     */
    public function cancelSubscription(Subscription $subscription): Subscription
    {
        $subscription->update(['status' => 'cancelled']);

        return $subscription->fresh();
    }

    /**
     * Renew an existing subscription.
     */
    public function renewSubscription(Subscription $subscription): Subscription
    {
        $startDate = $subscription->end_date->isFuture()
            ? $subscription->end_date
            : Carbon::now();

        $endDate = $this->calculateEndDate($startDate, $subscription->billing_cycle);

        $subscription->update([
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'status'     => 'active',
        ]);

        return $subscription->fresh();
    }

    /**
     * Check if a user has an active subscription.
     */
    public function hasActiveSubscription(User $user): bool
    {
        return $user->subscriptions()
            ->where('status', 'active')
            ->where('end_date', '>=', now())
            ->exists();
    }

    /**
     * Calculate the end date based on the billing cycle.
     */
    protected function calculateEndDate(Carbon $startDate, string $billingCycle): Carbon
    {
        return match ($billingCycle) {
            'monthly'     => $startDate->copy()->addMonth(),
            'quarterly'   => $startDate->copy()->addMonths(3),
            'semi_annual' => $startDate->copy()->addMonths(6),
            'annual'      => $startDate->copy()->addYear(),
            default       => $startDate->copy()->addMonth(),
        };
    }
}
