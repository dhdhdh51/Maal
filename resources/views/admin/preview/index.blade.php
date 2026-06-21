@extends('admin.layout')
@section('title', 'Preview durations')
@section('heading', 'Preview duration management')
@section('content')
    <form method="POST" action="{{ route('admin.preview.update') }}" class="glass rounded-xl p-5 max-w-2xl">
        @csrf @method('PUT')
        <div class="mb-5">
            <label>Global default preview (seconds)</label>
            <input name="global" type="number" value="{{ $globalDefault }}" class="max-w-[160px]">
        </div>
        <h2 class="font-semibold mb-2">Per-category overrides (blank = use global)</h2>
        <table>
            <tr><th>Category</th><th>Preview seconds</th></tr>
            @foreach ($categories as $c)
                <tr><td>{{ $c->name }}</td><td><input name="categories[{{ $c->id }}]" type="number" value="{{ $c->preview_seconds }}" class="max-w-[120px]"></td></tr>
            @endforeach
        </table>
        <button class="btn mt-4">Save preview durations</button>
    </form>
@endsection
