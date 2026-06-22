@extends('admin.layout')
@section('title', 'Edit template')
@section('heading', 'Template: '.$template->name)
@section('content')
    <form method="POST" action="{{ route('admin.email-templates.update', $template) }}" class="glass rounded-xl p-5 space-y-4 max-w-3xl">
        @csrf @method('PUT')
        @if (! empty($template->available_variables))
            <p class="text-xs text-gray-400">Variables: {{ collect($template->available_variables)->map(fn ($v) => '{'.$v.'}')->implode(', ') }}</p>
        @endif
        <div><label>Subject</label><input name="subject" value="{{ old('subject', $template->subject) }}" required></div>
        <div><label>Body (HTML)</label><textarea name="body_html" rows="10">{{ old('body_html', $template->body_html) }}</textarea></div>
        <div><label>Channel</label><select name="channel">@foreach (['email','whatsapp','both'] as $c)<option value="{{ $c }}" @selected($template->channel === $c)>{{ $c }}</option>@endforeach</select></div>
        <label class="flex items-center gap-2 text-gray-300"><input type="checkbox" name="is_active" value="1" class="w-auto" @checked($template->is_active)> Active</label>
        <button class="btn">Save</button> <a href="{{ route('admin.email-templates.index') }}" class="btn btn-ghost">Back</a>
    </form>
@endsection
