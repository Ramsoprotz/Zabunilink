@extends('emails.layout')

@section('content')
<p class="greeting">Welcome to ZabuniLink, {{ $user->name }}! 🎉</p>

<p class="text">
    Your account has been created successfully. ZabuniLink connects you with the latest tenders across Tanzania — from government agencies to private businesses.
</p>

<div class="card">
    <div class="card-row">
        <span class="card-label">Name</span>
        <span class="card-value">{{ $user->name }}</span>
    </div>
    <div class="card-row">
        <span class="card-label">Email</span>
        <span class="card-value">{{ $user->email }}</span>
    </div>
    @if($user->business_name)
    <div class="card-row">
        <span class="card-label">Business</span>
        <span class="card-value">{{ $user->business_name }}</span>
    </div>
    @endif
</div>

<p class="text">Here's what you can do next:</p>
<p class="text" style="font-size:14px;">
    📋 <strong>Browse tenders</strong> — search by category, location, or keyword.<br />
    ⭐ <strong>Save favorites</strong> — bookmark tenders to track them.<br />
    📲 <strong>Set up notifications</strong> — get alerts for tenders that match your interests.<br />
    💼 <strong>Subscribe</strong> — unlock Pro or Business features for more access.
</p>

<div class="btn-center">
    <a href="{{ rtrim(config('app.frontend_url', 'http://localhost:5173'), '/') }}/tenders" class="btn">
        Browse Tenders
    </a>
</div>
@endsection
