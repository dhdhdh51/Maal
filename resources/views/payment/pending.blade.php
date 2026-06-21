@extends('layouts.auth')
@section('title', 'Payment pending')
@section('heading')
    <h1 class="text-xl font-semibold mb-1">Awaiting confirmation</h1>
    <p class="text-sm text-gray-400 mb-6">Complete the bank transfer using the details below. Access is granted once an admin approves your payment.</p>
@endsection
@section('content')
    <div class="rounded-lg border border-white/10 bg-white/5 p-4 text-sm space-y-1">
        <div class="flex justify-between"><span class="text-gray-400">Reference</span><span>{{ $payment->reference }}</span></div>
        <div class="flex justify-between"><span class="text-gray-400">Amount</span><span>{{ money($payment->total, $payment->currency) }}</span></div>
    </div>
    <p class="text-xs text-gray-500 mt-4">{{ setting('manual_payment_instructions', 'Use your reference as the transfer note.') }}</p>
@endsection
