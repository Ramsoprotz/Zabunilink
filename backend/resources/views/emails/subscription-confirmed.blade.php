@extends('emails.layout')

@section('content')
<p class="greeting">Subscription Activated! 🎉</p>

<p class="text">
    Hello {{ $user->name }}, your subscription has been activated successfully. Here are your plan details:
</p>

<div class="card">
    <div class="card-row">
        <span class="card-label">Plan</span>
        <span class="card-value"><span class="badge badge-open">{{ $subscription->plan->name }}</span></span>
    </div>
    <div class="card-row">
        <span class="card-label">Billing Cycle</span>
        <span class="card-value">{{ ucfirst(str_replace('_', '-', $subscription->billing_cycle)) }}</span>
    </div>
    <div class="card-row">
        <span class="card-label">Amount</span>
        <span class="card-value">TZS {{ number_format($subscription->amount, 0) }}</span>
    </div>
    <div class="card-row">
        <span class="card-label">Start Date</span>
        <span class="card-value">{{ $subscription->start_date->format('d M Y') }}</span>
    </div>
    <div class="card-row">
        <span class="card-label">Expires</span>
        <span class="card-value">{{ $subscription->end_date->format('d M Y') }}</span>
    </div>
</div>

<div class="btn-center">
    <a href="{{ rtrim(config('app.frontend_url', 'http://localhost:5173'), '/') }}/subscription" class="btn">
        View Subscription
    </a>
</div>

<hr class="divider" />
<p class="text" style="font-size:13px; color:#a1a1aa;">
    Your subscription will auto-expire on the end date. You'll receive a reminder before it expires.
</p>
@endsection
