@extends('layouts.app')
@section('title', $category->seo_title ?: $category->name)
@section('meta_description', $category->meta_description ?: '')
@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold">{{ $category->name }}</h1>
            @if ($category->description)<p class="text-sm text-gray-400 mt-1 max-w-2xl">{{ $category->description }}</p>@endif
        </div>
        @unless ($hasAccess)
            <a href="{{ route('unlock.show', $category) }}" class="bg-violet-600 hover:bg-violet-500 px-4 py-2 rounded-lg text-sm font-medium text-white">
                Unlock · {{ money($category->price, $category->currency) }}
            </a>
        @else
            <span class="text-xs px-3 py-1.5 rounded-full bg-emerald-500/15 text-emerald-300">You have access</span>
        @endunless
    </div>

    @if ($videos->isEmpty())
        <div class="text-center py-16 text-gray-400">No videos in this category yet.</div>
    @else
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
            @foreach ($videos as $video)
                @include('partials.video-card', ['video' => $video])
            @endforeach
        </div>
        <div class="mt-6">{{ $videos->links() }}</div>
    @endif

    <div class="mt-8 text-xs text-gray-500">
        <a href="{{ route('report.create', ['category' => $category->id]) }}" class="hover:text-gray-300">Report this category</a>
    </div>
@endsection
