@extends('layouts.auth')
@section('title', 'Home')
@section('heading')
    <h1 class="text-xl font-semibold mb-1">{{ setting('site_name', 'Maal') }}</h1>
    <p class="text-sm text-gray-400 mb-6">{{ setting('site_tagline', 'Premium streaming, unlocked.') }}</p>
@endsection
@section('content')
    <div class="space-y-3">
        @auth
            <p class="text-sm text-gray-300">Signed in as {{ auth()->user()->name }}.</p>
            <div class="grid grid-cols-2 gap-3">
                <a href="{{ route('devices.index') }}" class="rounded-lg border border-white/10 py-2.5 text-center text-sm hover:bg-white/5">My devices</a>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="w-full rounded-lg bg-white/5 border border-white/10 py-2.5 text-sm hover:bg-white/10">Sign out</button>
                </form>
            </div>
        @else
            <div class="grid grid-cols-2 gap-3">
                <a href="{{ route('login') }}" class="rounded-lg bg-violet-600 hover:bg-violet-500 py-2.5 text-center text-sm font-medium text-white">Sign in</a>
                <a href="{{ route('register') }}" class="rounded-lg border border-white/10 py-2.5 text-center text-sm hover:bg-white/5">Register</a>
            </div>
        @endauth
        <p class="text-xs text-gray-500 text-center pt-2">The full cinematic experience is coming in a later build phase.</p>
    </div>
@endsection
