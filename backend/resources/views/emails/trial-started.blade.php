@extends('emails.layout')

@section('content')
<p class="greeting">Your Free Trial Has Started! 🚀</p>

<p class="text">
    Hello {{ $user->name }}, your free Basic plan trial is now active. Enjoy full access to Basic features at no cost.
</p>

<div class="card">
    <div class="card-row">
        <span class="card-label">Plan</span>
        <span class="card-value">Basic (Free Trial)</span>
    </div>
    <div class="card-row">
        <span class="card-label">Started</span>
        <span class="card-value">{{ $subscription->start_date->format('d M Y') }}</span>
    </div>
    <div class="card-row">
        <span class="card-label">Expires</span>
        <span class="card-value">{{ $subscription->end_date->format('d M Y') }}</span>
    </div>
    <div class="card-row">
        <span class="card-label">Duration</span>
        <span class="card-value">{{ $subscription->start_date->diffInDays($subscription->end_date) }} days</span>
    </div>
</div>

<p class="text">
    When your trial ends, you'll need to subscribe to continue using premium features. You can upgrade to Pro or Business at any time.
</p>

<div class="btn-center">
    <a href="{{ rtrim(config('app.frontend_url', 'http://localhost:5173'), '/') }}/subscription" class="btn">
        View Plans
    </a>
</div>
@endsection
