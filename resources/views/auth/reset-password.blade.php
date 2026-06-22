@extends('layouts.auth')
@section('title', 'Set new password')
@section('heading')
    <h1 class="text-xl font-semibold mb-1">Set a new password</h1>
    <p class="text-sm text-gray-400 mb-6">Enter the code from your email and choose a new password.</p>
@endsection
@section('content')
    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm mb-1.5 text-gray-300">Email</label>
            <input name="email" type="email" value="{{ old('email', $email ?? '') }}" required
                   class="w-full rounded-lg bg-white/5 border border-white/10 px-3.5 py-2.5 text-sm focus:border-violet-400 outline-none">
        </div>
        <div>
            <label class="block text-sm mb-1.5 text-gray-300">Reset code</label>
            <input name="code" inputmode="numeric" maxlength="6" required
                   class="w-full text-center tracking-[0.5em] rounded-lg bg-white/5 border border-white/10 px-3.5 py-2.5 focus:border-violet-400 outline-none">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm mb-1.5 text-gray-300">New password</label>
                <input name="password" type="password" required
                       class="w-full rounded-lg bg-white/5 border border-white/10 px-3.5 py-2.5 text-sm focus:border-violet-400 outline-none">
            </div>
            <div>
                <label class="block text-sm mb-1.5 text-gray-300">Confirm</label>
                <input name="password_confirmation" type="password" required
                       class="w-full rounded-lg bg-white/5 border border-white/10 px-3.5 py-2.5 text-sm focus:border-violet-400 outline-none">
            </div>
        </div>
        <button class="w-full rounded-lg bg-violet-600 hover:bg-violet-500 transition py-2.5 font-medium text-white">
            Update password
        </button>
    </form>
@endsection
