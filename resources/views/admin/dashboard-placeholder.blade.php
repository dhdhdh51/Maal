@extends('layouts.auth')
@section('title', 'Admin dashboard')
@section('heading')
    <h1 class="text-xl font-semibold mb-1">Admin Console</h1>
    <p class="text-sm text-gray-400 mb-6">Signed in as {{ auth()->user()->name }}.</p>
@endsection
@section('content')
    <p class="text-sm text-gray-300 mb-4">Admin authentication &amp; 2FA are active. The full admin panel is built in a later system.</p>
    <div class="grid grid-cols-2 gap-3">
        <a href="{{ route('2fa.setup') }}" class="rounded-lg border border-white/10 py-2.5 text-center text-sm hover:bg-white/5">Manage 2FA</a>
        <form method="POST" action="{{ route('admin.logout') }}">@csrf
            <button class="w-full rounded-lg bg-white/5 border border-white/10 py-2.5 text-sm hover:bg-white/10">Sign out</button>
        </form>
    </div>
@endsection
