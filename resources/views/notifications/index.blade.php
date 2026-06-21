@extends('layouts.app')
@section('title', 'Notifications')
@section('content')
    <div class="max-w-2xl">
        <div class="flex items-center justify-between mb-5">
            <h1 class="text-2xl font-bold">Notifications</h1>
            <form method="POST" action="{{ route('notifications.read_all') }}"
                  x-data @submit.prevent="fetch('{{ route('notifications.read_all') }}',{method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}}).then(()=>location.reload())">
                @csrf<button class="text-sm text-violet-300 hover:text-violet-200">Mark all read</button>
            </form>
        </div>
        @if ($notifications->isEmpty())
            <p class="text-gray-400 text-center py-16">You're all caught up.</p>
        @else
            <div class="space-y-2">
                @foreach ($notifications as $n)
                    <a href="{{ route('notifications.read', $n) }}" class="block glass rounded-xl p-4 {{ $n->read_at ? 'opacity-60' : '' }}">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium">{{ $n->title }}</p>
                            <span class="text-xs text-gray-500">{{ $n->created_at->diffForHumans() }}</span>
                        </div>
                        @if ($n->body)<p class="text-sm text-gray-400 mt-1">{{ $n->body }}</p>@endif
                    </a>
                @endforeach
            </div>
            <div class="mt-6">{{ $notifications->links() }}</div>
        @endif
    </div>
@endsection
