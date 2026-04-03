@extends('emails.layout')

@section('content')
<p class="greeting">Your Subscription Expires Soon ⏳</p>

<p class="text">
    Hello {{ $user->name }}, your <strong>{{ $subscription->plan->name }}</strong> plan expires in <strong>{{ $daysLeft }} day{{ $daysLeft > 1 ? 's' : '' }}</strong> (on {{ $subscription->end_date->format('d M Y') }}).
</p>

<div class="card">
    <div class="card-row">
        <span class="card-label">Plan</span>
        <span class="card-value">{{ $subscription->plan->name }}</span>
    </div>
    <div class="card-row">
        <span class="card-label">Billing Cycle</span>
        <span class="card-value">{{ ucfirst(str_replace('_', '-', $subscription->billing_cycle)) }}</span>
    </div>
    <div class="card-row">
        <span class="card-label">Expires</span>
        <span class="card-value" style="color:#ef4444; font-weight:700;">{{ $subscription->end_date->format('d M Y') }}</span>
    </div>
</div>

<p class="text">
    Renew your subscription to continue enjoying uninterrupted access to ZabuniLink.
</p>

<div class="btn-center">
    <a href="{{ rtrim(config('app.frontend_url', 'http://localhost:5173'), '/') }}/subscription" class="btn">
        Renew Subscription
    </a>
</div>
@endsection
