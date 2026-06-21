@extends('layouts.app')
@section('title', setting('site_name', 'Maal').' — '.setting('site_tagline', ''))
@section('content')
    @if ($banner)
        <div class="relative rounded-2xl overflow-hidden glass mb-8 h-48 md:h-72 flex items-end">
            @if ($banner->image)<img src="{{ cdn_url($banner->image) }}" class="absolute inset-0 w-full h-full object-cover opacity-50" alt="">@endif
            <div class="relative p-6">
                <h1 class="text-2xl md:text-4xl font-extrabold">{{ $banner->title }}</h1>
                @if ($banner->subtitle)<p class="text-gray-300 mt-1">{{ $banner->subtitle }}</p>@endif
                @if ($banner->link_url)<a href="{{ $banner->link_url }}" class="inline-block mt-3 bg-violet-600 hover:bg-violet-500 px-4 py-2 rounded-lg text-sm font-medium text-white">{{ $banner->cta_label ?? 'Explore' }}</a>@endif
            </div>
        </div>
    @endif

    @forelse ($sections as $section)
        <section class="mb-9">
            <h2 class="text-lg font-semibold mb-3">{{ $section['title'] }}</h2>
            @if ($section['type'] === 'categories')
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                    @foreach ($section['categories'] as $cat)
                        <a href="{{ route('category.show', $cat) }}" class="card relative aspect-[16/10] rounded-xl overflow-hidden glass flex items-end p-4">
                            @if ($cat->cover_image)<img src="{{ cdn_url($cat->cover_image) }}" class="absolute inset-0 w-full h-full object-cover opacity-60" alt="">@endif
                            <span class="relative font-semibold">{{ $cat->name }}</span>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                    @foreach ($section['videos'] as $video)
                        @include('partials.video-card', ['video' => $video])
                    @endforeach
                </div>
            @endif
        </section>
    @empty
        <div class="text-center py-20">
            <h1 class="text-2xl font-bold mb-2">Welcome to {{ setting('site_name', 'Maal') }}</h1>
            <p class="text-gray-400">Content is on its way. Check back soon.</p>
            <a href="{{ route('categories') }}" class="inline-block mt-4 bg-violet-600 hover:bg-violet-500 px-4 py-2 rounded-lg text-sm font-medium text-white">Browse categories</a>
        </div>
    @endforelse
@endsection
