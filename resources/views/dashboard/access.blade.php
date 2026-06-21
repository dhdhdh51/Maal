@extends('layouts.auth')
@section('title', 'My access')
@section('heading')
    <h1 class="text-xl font-semibold mb-1">My access</h1>
    <p class="text-sm text-gray-400 mb-6">Categories you can watch in full.</p>
@endsection
@section('content')
    <div class="space-y-3">
        @forelse ($access as $a)
            <div class="flex items-center justify-between rounded-lg border border-white/10 bg-white/5 px-4 py-3">
                <div>
                    <p class="text-sm font-medium text-gray-100">{{ $a->category?->name ?? 'Category' }}</p>
                    <p class="text-xs text-gray-500">
                        {{ ucfirst($a->source) }} ·
                        @if ($a->expires_at) expires {{ $a->expires_at->format('d M Y') }} @else Lifetime @endif
                    </p>
                </div>
                <span class="text-xs px-2 py-1 rounded-full {{ $a->isValid() ? 'bg-emerald-500/15 text-emerald-300' : 'bg-gray-500/15 text-gray-400' }}">
                    {{ ucfirst($a->status) }}
                </span>
            </div>
        @empty
            <p class="text-sm text-gray-500 text-center py-6">You don't have any active access yet.</p>
        @endforelse
    </div>
@endsection
