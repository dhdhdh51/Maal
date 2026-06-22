@extends('admin.layout')
@section('title', 'Settings')
@section('heading', 'Platform settings')
@section('content')
    <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6 max-w-4xl">
        @csrf @method('PUT')
        @foreach ($settings as $group => $items)
            <div class="glass rounded-xl p-5">
                <h2 class="font-semibold mb-3 capitalize">{{ $group }}</h2>
                <div class="grid md:grid-cols-2 gap-4">
                    @foreach ($items as $s)
                        <div>
                            <label>{{ ucwords(str_replace('_', ' ', $s->key)) }}</label>
                            @if ($s->type === 'boolean')
                                <label class="flex items-center gap-2 text-gray-300"><input type="checkbox" name="settings[{{ $s->key }}]" value="1" class="w-auto" @checked($s->typedValue())> Enabled</label>
                            @elseif ($s->type === 'text')
                                <textarea name="settings[{{ $s->key }}]" rows="2">{{ $s->value }}</textarea>
                            @else
                                <input name="settings[{{ $s->key }}]" value="{{ $s->value }}">
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
        <button class="btn">Save settings</button>
    </form>
@endsection
