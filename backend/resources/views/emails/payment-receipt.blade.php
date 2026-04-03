@extends('emails.layout')

@section('content')
<p class="greeting">Payment Receipt</p>

<p class="text">
    Hello {{ $user->name }}, thank you for your payment. Here is your receipt:
</p>

<div class="card">
    <div class="card-row">
        <span class="card-label">Reference</span>
        <span class="card-value">{{ $payment->reference }}</span>
    </div>
    <div class="card-row">
        <span class="card-label">Amount</span>
        <span class="card-value" style="font-size:16px; color:#10b981;">TZS {{ number_format($payment->amount, 0) }}</span>
    </div>
    <div class="card-row">
        <span class="card-label">Method</span>
        <span class="card-value">{{ ucfirst(str_replace('_', ' ', $payment->method)) }}</span>
    </div>
    @if($payment->transaction_id)
    <div class="card-row">
        <span class="card-label">Transaction ID</span>
        <span class="card-value">{{ $payment->transaction_id }}</span>
    </div>
    @endif
    <div class="card-row">
        <span class="card-label">Date</span>
        <span class="card-value">{{ $payment->updated_at->format('d M Y, H:i') }}</span>
    </div>
    <div class="card-row">
        <span class="card-label">Status</span>
        <span class="card-value"><span class="badge badge-open">Completed</span></span>
    </div>
</div>

<hr class="divider" />
<p class="text" style="font-size:13px; color:#a1a1aa;">
    Keep this email as your payment record. If you have any questions, contact support.
</p>
@endsection
