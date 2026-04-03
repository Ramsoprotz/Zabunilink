@extends('emails.layout')

@section('content')
<p class="greeting">Password Reset Request</p>

<p class="text">
    Hello {{ $user->name }}, we received a request to reset your ZabuniLink password. Use the code below to complete the reset:
</p>

<div class="card" style="text-align:center;">
    <p style="font-size:36px; font-weight:700; letter-spacing:8px; color:#10b981; margin:12px 0;">{{ $otp }}</p>
    <p style="font-size:13px; color:#71717a;">This code expires in 15 minutes.</p>
</div>

<p class="text">
    If you didn't request a password reset, you can safely ignore this email. Your password will remain unchanged.
</p>

<hr class="divider" />
<p class="text" style="font-size:13px; color:#a1a1aa;">
    For security, never share this code with anyone. ZabuniLink staff will never ask for your code.
</p>
@endsection
