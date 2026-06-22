@extends('layouts.auth')
@section('title', 'Verify email')
@section('heading')
    <h1 class="text-xl font-semibold mb-1">Verify your email</h1>
    <p class="text-sm text-gray-400 mb-6">Enter the 6-digit code we sent to <span class="text-gray-200">{{ $email }}</span>.</p>
@endsection
@section('content')
    <form method="POST" action="{{ route('verification.verify') }}" class="space-y-4">
        @csrf
        <input name="code" inputmode="numeric" maxlength="6" required autofocus
               class="w-full text-center tracking-[0.6em] text-2xl rounded-lg bg-white/5 border border-white/10 px-3.5 py-3 focus:border-violet-400 outline-none"
               placeholder="••••••">
        <button class="w-full rounded-lg bg-violet-600 hover:bg-violet-500 transition py-2.5 font-medium text-white">
            Verify &amp; continue
        </button>
    </form>
    <form method="POST" action="{{ route('verification.resend') }}" class="mt-4 text-center">
        @csrf
        <button class="text-sm text-violet-300 hover:text-violet-200">Resend code</button>
    </form>
@endsection
