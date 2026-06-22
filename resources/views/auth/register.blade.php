@extends('layouts.auth')
@section('title', 'Create account')
@section('heading')
    <h1 class="text-xl font-semibold mb-1">Create your account</h1>
    <p class="text-sm text-gray-400 mb-6">Join {{ setting('site_name', 'Maal') }} in a few seconds.</p>
@endsection
@section('content')
    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm mb-1.5 text-gray-300">Full name</label>
            <input name="name" value="{{ old('name') }}" required
                   class="w-full rounded-lg bg-white/5 border border-white/10 px-3.5 py-2.5 text-sm focus:border-violet-400 outline-none">
        </div>
        <div>
            <label class="block text-sm mb-1.5 text-gray-300">Email</label>
            <input name="email" type="email" value="{{ old('email') }}" required
                   class="w-full rounded-lg bg-white/5 border border-white/10 px-3.5 py-2.5 text-sm focus:border-violet-400 outline-none">
        </div>
        <div>
            <label class="block text-sm mb-1.5 text-gray-300">Phone <span class="text-gray-500">(optional)</span></label>
            <input name="phone" value="{{ old('phone') }}"
                   class="w-full rounded-lg bg-white/5 border border-white/10 px-3.5 py-2.5 text-sm focus:border-violet-400 outline-none">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm mb-1.5 text-gray-300">Password</label>
                <input name="password" type="password" required
                       class="w-full rounded-lg bg-white/5 border border-white/10 px-3.5 py-2.5 text-sm focus:border-violet-400 outline-none">
            </div>
            <div>
                <label class="block text-sm mb-1.5 text-gray-300">Confirm</label>
                <input name="password_confirmation" type="password" required
                       class="w-full rounded-lg bg-white/5 border border-white/10 px-3.5 py-2.5 text-sm focus:border-violet-400 outline-none">
            </div>
        </div>
        <div>
            <label class="block text-sm mb-1.5 text-gray-300">Referral code <span class="text-gray-500">(optional)</span></label>
            <input name="referral_code" value="{{ old('referral_code') }}"
                   class="w-full rounded-lg bg-white/5 border border-white/10 px-3.5 py-2.5 text-sm focus:border-violet-400 outline-none uppercase">
        </div>
        <label class="flex items-start gap-2 text-sm text-gray-400">
            <input type="checkbox" name="age_confirm" value="1" class="mt-0.5 rounded bg-white/5 border-white/20 text-violet-500">
            I confirm I am at least {{ setting('min_age', 18) }} years old.
        </label>
        <label class="flex items-start gap-2 text-sm text-gray-400">
            <input type="checkbox" name="terms" value="1" class="mt-0.5 rounded bg-white/5 border-white/20 text-violet-500">
            I accept the <a href="/terms" class="text-violet-300">Terms</a> &amp; <a href="/privacy" class="text-violet-300">Privacy Policy</a>.
        </label>
        <button class="w-full rounded-lg bg-violet-600 hover:bg-violet-500 transition py-2.5 font-medium text-white">
            Create account
        </button>
    </form>
    <p class="text-center text-sm text-gray-400 mt-6">
        Already have an account? <a href="{{ route('login') }}" class="text-violet-300 hover:text-violet-200 font-medium">Sign in</a>
    </p>
@endsection
