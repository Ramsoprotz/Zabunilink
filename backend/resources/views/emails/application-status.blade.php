@extends('emails.layout')

@php
    $status = $application->status;
    $statusLabel = ucfirst($status);
    $badgeClass = match($status) {
        'shortlisted' => 'badge-shortlisted',
        'awarded'     => 'badge-awarded',
        'rejected'    => 'badge-rejected',
        default       => 'badge-pending',
    };
    $headline = match($status) {
        'shortlisted' => 'Congratulations — You Have Been Shortlisted!',
        'awarded'     => '🎉 Congratulations — You Have Been Awarded the Tender!',
        'rejected'    => 'Application Update',
        default       => 'Application Status Update',
    };
    $body = match($status) {
        'shortlisted' => 'Your application has been reviewed and you have been shortlisted. The tendering organization will be in touch with next steps.',
        'awarded'     => 'Your application has been selected and you have been awarded this tender. Please log in to your ZabuniLink dashboard for further instructions.',
        'rejected'    => 'After careful consideration, your application was not selected for this tender. We encourage you to keep applying for other opportunities on ZabuniLink.',
        default       => 'The status of your application has been updated.',
    };
@endphp

@section('content')
<p class="greeting">Hello, {{ $applicant->name }} 👋</p>

<p class="text">{{ $headline }}</p>

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
        <span class="card-label">New Status</span>
        <span class="card-value"><span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span></span>
    </div>
    @if($application->awarded_at && $status === 'awarded')
    <div class="card-row">
        <span class="card-label">Awarded At</span>
        <span class="card-value">{{ $application->awarded_at->format('d M Y, H:i') }}</span>
    </div>
    @endif
</div>

<p class="text">{{ $body }}</p>

@if($application->tenderee_notes)
<p class="text" style="font-size:14px;"><strong>Notes from the tendering organization:</strong></p>
<div class="card" style="background:#f0fdf4; border-left: 3px solid #10b981; border-radius: 6px;">
    <p style="font-size:14px; color:#52525b; line-height:1.6;">{{ $application->tenderee_notes }}</p>
</div>
@endif

<div class="btn-center">
    <a href="{{ rtrim(config('app.frontend_url', 'http://localhost:5173'), '/') }}/applications" class="btn">
        View My Applications
    </a>
</div>

<hr class="divider" />
<p class="text" style="font-size:13px; color:#a1a1aa;">
    Log in to your ZabuniLink dashboard to see the full details of this application.
</p>
@endsection
