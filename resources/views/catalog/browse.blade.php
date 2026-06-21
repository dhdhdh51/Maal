@extends('layouts.app')
@section('title', 'Search')
@section('content')
    <h1 class="text-2xl font-bold mb-5">Search &amp; browse</h1>

    <form method="GET" action="{{ route('search') }}" class="flex flex-wrap gap-3 mb-6">
        <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search…"
               class="flex-1 min-w-[200px] rounded-lg bg-white/5 border border-white/10 px-3.5 py-2 text-sm">
        <select name="category" class="rounded-lg bg-white/5 border border-white/10 px-3 py-2 text-sm">
            <option value="">All categories</option>
            @foreach ($categories as $c)
                <option value="{{ $c->slug }}" @selected(($filters['category'] ?? '') === $c->slug)>{{ $c->name }}</option>
            @endforeach
        </select>
        <select name="sort" class="rounded-lg bg-white/5 border border-white/10 px-3 py-2 text-sm">
            @foreach (['newest' => 'Newest', 'trending' => 'Trending', 'most_watched' => 'Most watched', 'longest' => 'Longest'] as $val => $label)
                <option value="{{ $val }}" @selected(($filters['sort'] ?? 'newest') === $val)>{{ $label }}</option>
            @endforeach
        </select>
        <label class="flex items-center gap-2 text-sm text-gray-300">
            <input type="checkbox" name="free_preview" value="1" @checked(! empty($filters['free_preview']))> Free preview
        </label>
        <button class="bg-violet-600 hover:bg-violet-500 px-4 py-2 rounded-lg text-sm font-medium text-white">Apply</button>
    </form>

    @if ($results->isEmpty())
        <div class="text-center py-16 text-gray-400">No results found.</div>
    @else
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
            @foreach ($results as $video)
                @include('partials.video-card', ['video' => $video])
            @endforeach
        </div>
        <div class="mt-6">{{ $results->links() }}</div>
    @endif
@endsection
