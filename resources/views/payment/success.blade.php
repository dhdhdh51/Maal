@extends('layouts.auth')
@section('title', 'Payment successful')
@section('heading')
    <h1 class="text-xl font-semibold mb-1">Payment successful</h1>
    <p class="text-sm text-gray-400 mb-6">Your access has been unlocked. Enjoy watching!</p>
@endsection
@section('content')
    @if ($payment)
        <div class="rounded-lg border border-white/10 bg-white/5 p-4 text-sm space-y-1 mb-4">
            <div class="flex justify-between"><span class="text-gray-400">Reference</span><span>{{ $payment->reference }}</span></div>
            <div class="flex justify-between"><span class="text-gray-400">Amount</span><span>{{ money($payment->total, $payment->currency) }}</span></div>
            @if ($payment->invoice_number)
                <div class="flex justify-between"><span class="text-gray-400">Invoice</span><span>{{ $payment->invoice_number }}</span></div>
            @endif
        </div>
    @endif
    <div class="grid grid-cols-2 gap-3">
        <a href="{{ route('access.index') }}" class="rounded-lg bg-violet-600 hover:bg-violet-500 py-2.5 text-center text-sm font-medium text-white">My access</a>
        <a href="{{ url('/') }}" class="rounded-lg border border-white/10 py-2.5 text-center text-sm hover:bg-white/5">Browse</a>
    </div>
@endsection
