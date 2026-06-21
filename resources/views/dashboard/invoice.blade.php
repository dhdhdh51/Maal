@extends('layouts.auth')
@section('title', 'Invoice '.$payment->invoice_number)
@section('heading')
    <h1 class="text-xl font-semibold mb-1">Invoice {{ $payment->invoice_number }}</h1>
    <p class="text-sm text-gray-400 mb-6">{{ setting('site_name', 'Maal') }}</p>
@endsection
@section('content')
    <div class="rounded-lg border border-white/10 bg-white/5 p-4 text-sm space-y-2">
        <div class="flex justify-between"><span class="text-gray-400">Billed to</span><span>{{ $payment->user->name }}</span></div>
        <div class="flex justify-between"><span class="text-gray-400">Date</span><span>{{ optional($payment->paid_at)->format('d M Y') }}</span></div>
        <div class="flex justify-between"><span class="text-gray-400">Item</span><span>{{ $payment->plan?->name ?? $payment->category?->name ?? 'Access' }}</span></div>
        <hr class="border-white/10">
        <div class="flex justify-between"><span class="text-gray-400">Subtotal</span><span>{{ money($payment->amount, $payment->currency) }}</span></div>
        @if ($payment->discount > 0)
            <div class="flex justify-between"><span class="text-gray-400">Discount</span><span>-{{ money($payment->discount, $payment->currency) }}</span></div>
        @endif
        <div class="flex justify-between"><span class="text-gray-400">Tax</span><span>{{ money($payment->tax, $payment->currency) }}</span></div>
        <div class="flex justify-between font-semibold text-base"><span>Total</span><span>{{ money($payment->total, $payment->currency) }}</span></div>
    </div>
    <button onclick="window.print()" class="w-full mt-4 rounded-lg border border-white/10 py-2.5 text-sm hover:bg-white/5">Print / Save PDF</button>
@endsection
