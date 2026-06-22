@extends('layouts.app')
@section('title', $video->seo_title ?: $video->title)
@section('meta_description', $video->meta_description ?: '')
@push('head')
    <link rel="canonical" href="{{ route('video.show', $video) }}">
    @if ($video->noindex)<meta name="robots" content="noindex">@endif
    <meta property="og:type" content="video.other">
    <meta property="og:title" content="{{ $video->title }}">
    <meta property="og:description" content="{{ $video->meta_description ?: \Illuminate\Support\Str::limit($video->description, 160) }}">
    @if ($video->poster_path)<meta property="og:image" content="{{ cdn_url($video->poster_path) }}">@endif
    <script type="application/ld+json">
    @json([
        '@context' => 'https://schema.org',
        '@type' => 'VideoObject',
        'name' => $video->title,
        'description' => $video->meta_description ?: strip_tags((string) $video->description),
        'thumbnailUrl' => $video->poster_path ? cdn_url($video->poster_path) : null,
        'uploadDate' => optional($video->published_at)->toAtomString(),
        'duration' => $video->duration ? 'PT'.$video->duration.'S' : null,
    ], JSON_UNESCAPED_SLASHES)
    </script>
@endpush
@section('content')
    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="relative aspect-video rounded-xl overflow-hidden glass mb-4">
                @if ($video->poster_path)<img src="{{ cdn_url($video->poster_path) }}" class="w-full h-full object-cover" alt="">@endif
                <a href="{{ route('watch', $video) }}" class="absolute inset-0 flex items-center justify-center bg-black/30 hover:bg-black/20">
                    <span class="bg-violet-600 hover:bg-violet-500 rounded-full w-16 h-16 flex items-center justify-center">
                        <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    </span>
                </a>
                @if ($video->getAttribute('user_locked'))
                    <span class="absolute top-3 right-3 bg-black/60 text-amber-300 text-xs px-2 py-1 rounded-full">Locked · preview available</span>
                @endif
            </div>

            <h1 class="text-2xl font-bold">{{ $video->title }}</h1>
            <div class="flex items-center gap-3 mt-2 text-sm text-gray-400">
                @if ($video->category)<a href="{{ route('category.show', $video->category) }}" class="hover:text-gray-200">{{ $video->category->name }}</a>@endif
                <span>· {{ number_format($video->views_count) }} views</span>
            </div>
            @if ($video->description)<p class="text-gray-300 mt-4 whitespace-pre-line">{{ $video->description }}</p>@endif

            <div class="flex items-center gap-3 mt-5">
                <a href="{{ route('watch', $video) }}" class="bg-violet-600 hover:bg-violet-500 px-5 py-2.5 rounded-lg text-sm font-medium text-white">
                    {{ $video->getAttribute('user_locked') ? 'Watch preview' : 'Play' }}
                </a>
                @auth
                    <button x-data="{fav:false}" @click="
                        fetch('{{ route('favorites.toggle', $video) }}', {method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}})
                          .then(r=>r.json()).then(d=>fav=d.data.favorited)"
                        class="px-4 py-2.5 rounded-lg glass text-sm" :class="fav ? 'text-rose-300' : 'text-gray-300'">
                        <span x-text="fav ? '♥ Saved' : '♡ Save'">♡ Save</span>
                    </button>
                @endauth
                <a href="{{ route('report.create', ['video' => $video->id]) }}" class="text-xs text-gray-500 hover:text-gray-300 ml-auto">Report</a>
            </div>
        </div>

        <aside>
            <h2 class="text-sm font-semibold text-gray-400 mb-3">More like this</h2>
            <div class="grid grid-cols-2 lg:grid-cols-1 gap-3">
                @foreach ($related as $r)
                    @include('partials.video-card', ['video' => $r])
                @endforeach
            </div>
        </aside>
    </div>
@endsection
