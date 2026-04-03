<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\MembershipService;
use App\Services\PaymentService;
use App\Services\SystemMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(
        protected MembershipService    $membershipService,
        protected PaymentService       $paymentService,
        protected SystemMessageService $messageService,
    ) {}

    /**
     * List all active subscription plans + trial config.
     */
    public function plans(): JsonResponse
    {
        $plans = Plan::where('is_active', true)->orderBy('tier')->get();

        return response()->json([
            'data'  => $plans,
            'trial' => $this->membershipService->getTrialConfig(),
        ]);
    }

    /**
     * Get the current user's active subscription.
     */
    public function mySubscription(Request $request): JsonResponse
    {
        $subscription = $request->user()
            ->activeSubscription()
            ->with(['plan', 'scheduledPlan'])
            ->first();

        return response()->json([
            'data' => $subscription,
        ]);
    }

    /**
     * Subscribe to a plan (first-time, no active subscription).
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_id'       => 'required|integer|exists:plans,id',
            'billing_cycle' => 'required|string|in:monthly,quarterly,semi_annual,annual',
        ]);

        $user = $request->user();

        // If user already has an active subscription, redirect to changePlan
        if ($user->activeSubscription) {
            return $this->changePlan($request);
        }

        $subscription = $this->membershipService->subscribe(
            $user,
            $validated['plan_id'],
            $validated['billing_cycle'],
        );

        $payment = $this->paymentService->initiatePayment($user, $subscription);

        return response()->json([
            'message'      => 'Subscription created. Please complete payment.',
            'subscription' => $subscription->load('plan'),
            'payment'      => $payment,
        ], 201);
    }

    /**
     * Change plan (upgrade or downgrade).
     */
    public function changePlan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_id'       => 'required|integer|exists:plans,id',
            'billing_cycle' => 'required|string|in:monthly,quarterly,semi_annual,annual',
        ]);

        $user = $request->user();

        try {
            $result = $this->membershipService->changePlan(
                $user,
                $validated['plan_id'],
                $validated['billing_cycle'],
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        // For upgrades and new subscriptions, initiate payment
        if (in_array($result['action'], ['upgrade', 'new_subscription'])) {
            $amountDue = $result['amount_due'];

            if ($amountDue > 0) {
                $payment = $this->paymentService->initiatePayment(
                    $user,
                    $result['subscription'],
                    $amountDue,
                );
                $result['payment'] = $payment;
            } else {
                // Credit covers the full cost — activate immediately
                $result['subscription']->update(['status' => 'active']);
                $result['subscription']->refresh();
            }

            $message = $result['action'] === 'upgrade'
                ? 'Plan upgraded successfully.'
                : 'Subscription created. Please complete payment.';

            return response()->json(array_merge($result, ['message' => $message]), 201);
        }

        // Downgrade scheduled
        return response()->json([
            'message'        => "Your plan will change to {$result['new_plan']} on {$result['effective_date']}.",
            'action'         => 'downgrade_scheduled',
            'subscription'   => $result['subscription'],
            'effective_date' => $result['effective_date'],
        ]);
    }

    /**
     * Preview what a plan change would cost (no side effects).
     */
    public function previewChange(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_id'       => 'required|integer|exists:plans,id',
            'billing_cycle' => 'required|string|in:monthly,quarterly,semi_annual,annual',
        ]);

        $preview = $this->membershipService->previewPlanChange(
            $request->user(),
            $validated['plan_id'],
            $validated['billing_cycle'],
        );

        return response()->json(['data' => $preview]);
    }

    /**
     * Cancel a scheduled downgrade.
     */
    public function cancelScheduledChange(Request $request): JsonResponse
    {
        $subscription = $request->user()->activeSubscription;

        if (! $subscription || ! $subscription->hasScheduledChange()) {
            return response()->json(['message' => 'No scheduled plan change to cancel.'], 404);
        }

        $this->membershipService->cancelScheduledChange($subscription);

        return response()->json([
            'message'      => 'Scheduled plan change cancelled.',
            'subscription' => $subscription->fresh()->load('plan'),
        ]);
    }

    /**
     * Start a free trial (Basic plan only).
     */
    public function startTrial(Request $request): JsonResponse
    {
        $user = $request->user();

        try {
            $subscription = $this->membershipService->startTrial($user);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        // Send trial started notification (email + SMS)
        $this->messageService->sendTrialStarted($user, $subscription);

        return response()->json([
            'message'      => 'Free trial started! Enjoy your Basic plan.',
            'subscription' => $subscription->load('plan'),
        ], 201);
    }
}
