@extends('layouts.auth')
@section('title', 'Your devices')
@section('heading')
    <h1 class="text-xl font-semibold mb-1">Active devices</h1>
    <p class="text-sm text-gray-400 mb-6">Manage where you're signed in. Limit: {{ auth()->user()->effectiveDeviceLimit() ?: 'Unlimited' }}.</p>
@endsection
@section('content')
    <div class="space-y-3">
        @forelse ($devices as $device)
            <div class="flex items-center justify-between rounded-lg border border-white/10 bg-white/5 px-4 py-3">
                <div>
                    <p class="text-sm font-medium text-gray-200">
                        {{ $device->name ?? 'Unknown device' }}
                        @if ($device->device_id === $currentFingerprint)
                            <span class="ml-1 text-xs text-emerald-400">(this device)</span>
                        @endif
                    </p>
                    <p class="text-xs text-gray-500">
                        {{ $device->device_type }} · {{ $device->ip_address }}
                        · last active {{ optional($device->last_active_at)->diffForHumans() }}
                    </p>
                </div>
                @if ($device->device_id !== $currentFingerprint)
                    <form method="POST" action="{{ route('devices.destroy', $device) }}">
                        @csrf @method('DELETE')
                        <button class="text-xs rounded-md border border-red-500/40 text-red-300 px-3 py-1.5 hover:bg-red-500/10">Remove</button>
                    </form>
                @endif
            </div>
        @empty
            <p class="text-sm text-gray-500 text-center py-6">No devices on record.</p>
        @endforelse
    </div>
@endsection
