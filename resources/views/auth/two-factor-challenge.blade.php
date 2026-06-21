@extends('layouts.auth')
@section('title', 'Two-factor challenge')
@section('heading')
    <h1 class="text-xl font-semibold mb-1">Two-factor authentication</h1>
    <p class="text-sm text-gray-400 mb-6">Enter the code from your authenticator app or a recovery code.</p>
@endsection
@section('content')
    <form method="POST" action="{{ route('admin.2fa.verify') }}" class="space-y-4">
        @csrf
        <input name="code" required autofocus autocomplete="one-time-code" placeholder="Authentication code"
               class="w-full text-center tracking-widest rounded-lg bg-white/5 border border-white/10 px-3.5 py-2.5 focus:border-violet-400 outline-none">
        <button class="w-full rounded-lg bg-violet-600 hover:bg-violet-500 transition py-2.5 font-medium text-white">
            Verify
        </button>
    </form>
    <form method="POST" action="{{ route('admin.logout') }}" class="mt-4 text-center">
        @csrf
        <button class="text-sm text-gray-400 hover:text-gray-300">Cancel &amp; sign out</button>
    </form>
@endsection
