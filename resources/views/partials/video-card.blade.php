@php($locked = $video->getAttribute('user_locked'))
<a href="{{ route('video.show', $video) }}" class="card block group">
    <div class="relative aspect-video rounded-xl overflow-hidden glass">
        @if ($video->poster_path)
            <img src="{{ cdn_url($video->poster_path) }}" loading="lazy" alt="{{ $video->title }}" class="w-full h-full object-cover">
        @else
            <div class="w-full h-full flex items-center justify-center text-gray-600 text-xs">No preview</div>
        @endif
        @if ($locked)
            <span class="absolute top-2 right-2 bg-black/60 rounded-full p-1.5" title="Locked">
                <svg class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 0h10.5a2.25 2.25 0 0 1 2.25 2.25v6A2.25 2.25 0 0 1 17.25 21H6.75A2.25 2.25 0 0 1 4.5 18.75v-6a2.25 2.25 0 0 1 2.25-2.25Z"/></svg>
            </span>
        @endif
        @if ($video->duration)
            <span class="absolute bottom-2 right-2 bg-black/70 text-[11px] px-1.5 py-0.5 rounded">{{ gmdate($video->duration >= 3600 ? 'H:i:s' : 'i:s', $video->duration) }}</span>
        @endif
    </div>
    <p class="mt-2 text-sm font-medium text-gray-100 truncate group-hover:text-white">{{ $video->title }}</p>
    @if ($video->category)
        <p class="text-xs text-gray-500">{{ $video->category->name }}</p>
    @endif
</a>
