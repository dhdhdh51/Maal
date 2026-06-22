@extends('layouts.auth')
@section('title', 'Payment failed')
@section('heading')
    <h1 class="text-xl font-semibold mb-1">Payment not completed</h1>
    <p class="text-sm text-gray-400 mb-6">Your payment could not be processed. You have not been charged.</p>
@endsection
@section('content')
    <div class="grid grid-cols-2 gap-3">
        <a href="{{ url()->previous() }}" class="rounded-lg bg-violet-600 hover:bg-violet-500 py-2.5 text-center text-sm font-medium text-white">Try again</a>
        <a href="{{ url('/contact') }}" class="rounded-lg border border-white/10 py-2.5 text-center text-sm hover:bg-white/5">Get help</a>
    </div>
@endsection
