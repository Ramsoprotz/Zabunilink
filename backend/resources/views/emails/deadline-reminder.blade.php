@extends('emails.layout')

@section('content')
<p class="greeting">Hello, {{ $recipient->name }} 👋</p>

<p class="text">
    This is a reminder that a tender you saved is closing in <strong>3 days</strong>. Don't miss the deadline!
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
    @if($tender->value)
    <div class="card-row">
        <span class="card-label">Value</span>
        <span class="card-value">TZS {{ number_format($tender->value, 0) }}</span>
    </div>
    @endif
    <div class="card-row">
        <span class="card-label">Deadline</span>
        <span class="card-value" style="color: #dc2626; font-size: 16px;">
            ⏰ {{ $tender->deadline->format('d M Y') }}
        </span>
    </div>
</div>

<div class="btn-center">
    <a href="{{ rtrim(config('app.frontend_url', 'http://localhost:5173'), '/') }}/tenders/{{ $tender->id }}" class="btn">
        View Tender &amp; Apply
    </a>
</div>

<hr class="divider" />
<p class="text" style="font-size:13px; color:#a1a1aa;">
    You received this because you saved this tender to your favourites.
    Manage your saved tenders from your ZabuniLink dashboard.
</p>
@endsection
