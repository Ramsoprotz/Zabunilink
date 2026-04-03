@extends('emails.layout')

@section('content')
<p class="greeting">Hello, {{ $recipient->name }} 👋</p>

<p class="text">
    A new tender has been posted that matches your notification preferences.
</p>

<div class="card">
    <div class="card-row">
        <span class="card-label">Tender</span>
        <span class="card-value">{{ $tender->title }}</span>
    </div>
    <div class="card-row">
        <span class="card-label">Organization</span>
        <span class="card-value">{{ $tender->organization }}</span>
    </div>
    <div class="card-row">
        <span class="card-label">Category</span>
        <span class="card-value">{{ $tender->category?->name ?? '—' }}</span>
    </div>
    <div class="card-row">
        <span class="card-label">Location</span>
        <span class="card-value">{{ $tender->location?->name ?? '—' }}</span>
    </div>
    <div class="card-row">
        <span class="card-label">Type</span>
        <span class="card-value" style="text-transform: capitalize;">{{ $tender->type }}</span>
    </div>
    @if($tender->value)
    <div class="card-row">
        <span class="card-label">Value</span>
        <span class="card-value">TZS {{ number_format($tender->value, 0) }}</span>
    </div>
    @endif
    <div class="card-row">
        <span class="card-label">Deadline</span>
        <span class="card-value" style="color: #dc2626;">{{ $tender->deadline->format('d M Y') }}</span>
    </div>
    <div class="card-row">
        <span class="card-label">Status</span>
        <span class="card-value"><span class="badge badge-open">Open</span></span>
    </div>
</div>

@if($tender->description)
<p class="text" style="font-size:14px; color:#52525b;">
    {{ Str::limit($tender->description, 300) }}
</p>
@endif

<div class="btn-center">
    <a href="{{ rtrim(config('app.frontend_url', 'http://localhost:5173'), '/') }}/tenders/{{ $tender->id }}" class="btn">
        View Tender Details
    </a>
</div>

<hr class="divider" />
<p class="text" style="font-size:13px; color:#a1a1aa;">
    You received this because your notification preferences match this tender's category and location.
    You can update your preferences at any time from your profile.
</p>
@endsection
