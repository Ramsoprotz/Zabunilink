@extends('emails.layout')

@section('content')
<p class="greeting">Your Free Trial Expires Soon ⏳</p>

<p class="text">
    Hello {{ $user->name }}, your free Basic plan trial expires in <strong>{{ $daysLeft }} day{{ $daysLeft > 1 ? 's' : '' }}</strong> (on {{ $subscription->end_date->format('d M Y') }}).
</p>

<p class="text">
    To continue accessing tender listings and other features without interruption, subscribe to a plan before your trial ends.
</p>

<div class="card">
    <div class="card-row">
        <span class="card-label">Current Plan</span>
        <span class="card-value">Basic (Free Trial)</span>
    </div>
    <div class="card-row">
        <span class="card-label">Expires</span>
        <span class="card-value" style="color:#ef4444; font-weight:700;">{{ $subscription->end_date->format('d M Y') }}</span>
    </div>
</div>

<div class="btn-center">
    <a href="{{ rtrim(config('app.frontend_url', 'http://localhost:5173'), '/') }}/subscription" class="btn">
        Subscribe Now
    </a>
</div>

<hr class="divider" />
<p class="text" style="font-size:13px; color:#a1a1aa;">
    After your trial expires, you'll need an active subscription to continue using ZabuniLink features.
</p>
@endsection
