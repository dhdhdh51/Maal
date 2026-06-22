@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
    <h1 class="text-2xl font-bold mb-5">Welcome, {{ $user->name }}</h1>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        @foreach ([
            ['Active access', $activeAccess->count(), route('access.index')],
            ['Favorites', $favoritesCount, route('favorites.index')],
            ['Devices', $devicesCount, route('devices.index')],
            ['Open tickets', $openTickets, route('support.index')],
        ] as [$label, $value, $url])
            <a href="{{ $url }}" class="glass rounded-xl p-4 card">
                <p class="text-2xl font-bold">{{ $value }}</p>
                <p class="text-xs text-gray-400">{{ $label }}</p>
            </a>
        @endforeach
    </div>

    <div class="grid md:grid-cols-3 gap-6">
        <div class="md:col-span-2">
            <h2 class="text-lg font-semibold mb-3">Continue watching</h2>
            @if ($continue->isEmpty())
                <p class="text-sm text-gray-500">Nothing in progress. <a href="{{ route('home') }}" class="text-violet-300">Find something to watch</a>.</p>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                    @foreach ($continue as $video)
                        @include('partials.video-card', ['video' => $video])
                    @endforeach
                </div>
            @endif
        </div>
        <div class="space-y-4">
            <div class="glass rounded-xl p-4">
                <p class="text-xs text-gray-400 mb-1">Wallet balance</p>
                <p class="text-xl font-bold">{{ money($user->wallet_balance) }}</p>
            </div>
            <div class="glass rounded-xl p-4">
                <p class="text-xs text-gray-400 mb-2">Your referral link</p>
                <input readonly value="{{ $referralLink }}" onclick="this.select()" class="w-full rounded-lg bg-white/5 border border-white/10 px-3 py-2 text-xs">
            </div>
            <a href="{{ route('profile.edit') }}" class="block text-center glass rounded-xl p-3 text-sm hover:bg-white/10">Edit profile</a>
        </div>
    </div>
@endsection
