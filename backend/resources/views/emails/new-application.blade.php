@extends('emails.layout')

@section('content')
<p class="greeting">Hello, {{ $owner->name }} 👋</p>

<p class="text">
    Great news! Your tender has received a new application on ZabuniLink.
</p>

<div class="card">
    <div class="card-row">
        <span class="card-label">Tender</span>
        <span class="card-value">{{ $tender->title }}</span>
    </div>
    <div class="card-row">
        <span class="card-label">Applicant</span>
        <span class="card-value">{{ $applicant->name }}</span>
    </div>
    @if($applicant->business_name)
    <div class="card-row">
        <span class="card-label">Business</span>
        <span class="card-value">{{ $applicant->business_name }}</span>
    </div>
    @endif
    <div class="card-row">
        <span class="card-label">Email</span>
        <span class="card-value">{{ $applicant->email }}</span>
    </div>
    @if($applicant->phone)
    <div class="card-row">
        <span class="card-label">Phone</span>
        <span class="card-value">{{ $applicant->phone }}</span>
    </div>
    @endif
    <div class="card-row">
        <span class="card-label">Applied At</span>
        <span class="card-value">{{ $application->created_at->format('d M Y, H:i') }}</span>
    </div>
    <div class="card-row">
        <span class="card-label">Status</span>
        <span class="card-value"><span class="badge badge-pending">Pending Review</span></span>
    </div>
</div>

@if($application->notes)
<p class="text" style="font-size:14px;"><strong>Applicant notes:</strong></p>
<div class="card" style="background:#fffbeb; border-left: 3px solid #f59e0b; border-radius: 6px;">
    <p style="font-size:14px; color:#52525b; line-height:1.6;">{{ $application->notes }}</p>
</div>
@endif

@php $docCount = count($application->documents ?? []); @endphp
@if($docCount > 0)
<p class="text" style="font-size:14px; color:#52525b;">
    📎 The applicant has attached <strong>{{ $docCount }} document{{ $docCount > 1 ? 's' : '' }}</strong>. Log in to download them.
</p>
@endif

<div class="btn-center">
    <a href="{{ rtrim(config('app.frontend_url', 'http://localhost:5173'), '/') }}/business/tenders/{{ $tender->id }}/applications" class="btn">
        Review Application
    </a>
</div>

<hr class="divider" />
<p class="text" style="font-size:13px; color:#a1a1aa;">
    Log in to your ZabuniLink business dashboard to shortlist, award, or reject this application.
</p>
@endsection
