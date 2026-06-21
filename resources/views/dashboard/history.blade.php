@extends('layouts.app')
@section('title', 'Watch history')
@section('content')
    <h1 class="text-2xl font-bold mb-5">Watch history</h1>
    @if ($history->isEmpty())
        <p class="text-gray-400 text-center py-16">No watch history.</p>
    @else
        <div class="space-y-3">
            @foreach ($history as $item)
                <div class="flex items-center gap-4 glass rounded-xl p-3">
                    <a href="{{ $item->video ? route('watch', $item->video) : '#' }}" class="w-28 aspect-video rounded-lg overflow-hidden bg-black/40 shrink-0">
                        @if ($item->video?->poster_path)<img src="{{ cdn_url($item->video->poster_path) }}" class="w-full h-full object-cover" alt="">@endif
                    </a>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium truncate">{{ $item->video?->title ?? 'Removed video' }}</p>
                        <div class="h-1.5 bg-white/10 rounded-full mt-2 overflow-hidden">
                            <div class="h-full bg-violet-500" style="width: {{ $item->percent }}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">{{ $item->percent }}% · {{ optional($item->last_watched_at)->diffForHumans() }}</p>
                    </div>
                    <form method="POST" action="{{ route('history.destroy', $item) }}">@csrf @method('DELETE')
                        <button class="text-xs text-gray-400 hover:text-red-300">Remove</button>
                    </form>
                </div>
            @endforeach
        </div>
        <div class="mt-6">{{ $history->links() }}</div>
    @endif
@endsection
