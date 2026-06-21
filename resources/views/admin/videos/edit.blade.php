@extends('admin.layout')
@section('title', 'Edit video')
@section('heading', 'Edit: '.$video->title)
@section('content')
    <div class="grid lg:grid-cols-3 gap-6">
        <form method="POST" action="{{ route('admin.videos.update', $video) }}" class="lg:col-span-2 glass rounded-xl p-5 grid md:grid-cols-2 gap-4">
            @csrf @method('PUT')
            <div class="md:col-span-2"><label>Title</label><input name="title" value="{{ old('title', $video->title) }}" required></div>
            <div class="md:col-span-2"><label>Description</label><textarea name="description" rows="3">{{ old('description', $video->description) }}</textarea></div>
            <div><label>Category</label><select name="category_id"><option value="">—</option>@foreach ($categories as $c)<option value="{{ $c->id }}" @selected($video->category_id == $c->id)>{{ $c->name }}</option>@endforeach</select></div>
            <div><label>Preview seconds</label><input name="preview_seconds" type="number" value="{{ $video->preview_seconds }}"></div>
            <div><label>Preview start (s)</label><input name="preview_start" type="number" value="{{ $video->preview_start }}"></div>
            <div><label>SEO title</label><input name="seo_title" value="{{ $video->seo_title }}"></div>
            <div class="md:col-span-2"><label>Meta description</label><textarea name="meta_description" rows="2">{{ $video->meta_description }}</textarea></div>
            <label class="flex items-center gap-2 text-gray-300"><input type="checkbox" name="is_published" value="1" class="w-auto" @checked($video->is_published)> Published</label>
            <label class="flex items-center gap-2 text-gray-300"><input type="checkbox" name="is_locked" value="1" class="w-auto" @checked($video->is_locked)> Locked</label>
            <label class="flex items-center gap-2 text-gray-300"><input type="checkbox" name="watermark_enabled" value="1" class="w-auto" @checked($video->watermark_enabled)> Force watermark</label>
            <div class="md:col-span-2"><button class="btn">Save</button></div>
        </form>

        <div class="space-y-4">
            <div class="glass rounded-xl p-4">
                <h2 class="font-semibold mb-2">Processing</h2>
                <p class="text-sm text-gray-400 mb-2">Status: <span class="text-gray-100">{{ $video->processing_status }}</span> ({{ $video->processing_progress }}%)</p>
                <div class="flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('admin.videos.reprocess', $video) }}">@csrf<button class="btn-ghost btn">Reprocess all</button></form>
                    <form method="POST" action="{{ route('admin.videos.cancel', $video) }}">@csrf<button class="btn-ghost btn">Cancel</button></form>
                </div>
                @foreach (['poster','thumbnails','preview','transcode'] as $stage)
                    <form method="POST" action="{{ route('admin.videos.reprocess_stage', $video) }}" class="inline">@csrf<input type="hidden" name="stage" value="{{ $stage }}"><button class="text-xs text-violet-300 mr-2 mt-2">re-{{ $stage }}</button></form>
                @endforeach
                @if ($video->is_disabled)
                    <form method="POST" action="{{ route('admin.videos.restore', $video) }}" class="mt-3">@csrf<button class="btn">Restore video</button></form>
                @else
                    <form method="POST" action="{{ route('admin.videos.disable', $video) }}" class="mt-3" onsubmit="return confirm('Disable immediately?')">@csrf<button class="btn" style="background:#dc2626">Disable immediately</button></form>
                @endif
            </div>
            <div class="glass rounded-xl p-4">
                <h2 class="font-semibold mb-2">Subtitles</h2>
                @foreach ($video->subtitles as $s)<p class="text-sm">{{ $s->language }} · {{ $s->is_ready ? 'ready' : 'processing' }}</p>@endforeach
                <form method="POST" action="{{ route('admin.videos.subtitles', $video) }}" enctype="multipart/form-data" class="mt-2 space-y-2">
                    @csrf
                    <input name="language" placeholder="en" required>
                    <input type="file" name="file" accept=".srt,.vtt" required>
                    <button class="btn-ghost btn">Upload subtitle</button>
                </form>
            </div>
        </div>
    </div>
@endsection
