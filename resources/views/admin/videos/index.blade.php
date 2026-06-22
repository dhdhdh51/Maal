@extends('admin.layout')
@section('title', 'Videos')
@section('heading', 'Videos')
@section('content')
    <form method="GET" class="flex flex-wrap gap-2 mb-4">
        <input name="q" value="{{ request('q') }}" placeholder="Search title…" class="max-w-xs">
        <select name="status" class="max-w-[160px]">
            <option value="">Any status</option>
            @foreach (['uploading','uploaded','processing','encoding','ready','failed'] as $s)
                <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        <select name="category" class="max-w-[200px]">
            <option value="">Any category</option>
            @foreach ($categories as $c)<option value="{{ $c->id }}" @selected(request('category') == $c->id)>{{ $c->name }}</option>@endforeach
        </select>
        <button class="btn">Filter</button>
    </form>

    <form method="POST" action="{{ route('admin.videos.bulk') }}" x-data="{sel:[]}">
        @csrf
        <div class="flex items-center gap-2 mb-3">
            <select name="action" class="max-w-[200px]">
                <option value="publish">Publish</option><option value="unpublish">Unpublish</option>
                <option value="disable">Disable</option><option value="restore">Restore</option>
                <option value="reprocess">Reprocess</option><option value="delete">Delete</option>
            </select>
            <button class="btn-ghost btn" x-bind:disabled="sel.length===0">Apply to selected</button>
            <span class="text-xs text-gray-500" x-text="sel.length + ' selected'"></span>
        </div>
        <div class="glass rounded-xl p-4">
            <table>
                <tr><th></th><th>Title</th><th>Category</th><th>Status</th><th>Pub</th><th>Disabled</th><th></th></tr>
                @foreach ($videos as $v)
                    <tr>
                        <td><input type="checkbox" name="ids[]" value="{{ $v->id }}" class="w-auto" x-model="sel"></td>
                        <td>{{ $v->title }}</td>
                        <td>{{ $v->category?->name ?? '—' }}</td>
                        <td><span class="text-xs px-2 py-0.5 rounded bg-white/10">{{ $v->processing_status }}{{ $v->processing_status !== 'ready' ? ' '.$v->processing_progress.'%' : '' }}</span></td>
                        <td>{!! $v->is_published ? '✓' : '—' !!}</td>
                        <td>{!! $v->is_disabled ? '<span class="text-red-400">Yes</span>' : '—' !!}</td>
                        <td><a href="{{ route('admin.videos.edit', $v) }}" class="text-violet-300">Edit</a></td>
                    </tr>
                @endforeach
            </table>
        </div>
    </form>
    <div class="mt-4">{{ $videos->links() }}</div>
@endsection
