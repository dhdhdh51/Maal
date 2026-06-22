@extends('admin.layout')
@section('title', 'Email templates')
@section('heading', 'Email templates')
@section('content')
    <div class="glass rounded-xl p-4">
        <table>
            <tr><th>Name</th><th>Key</th><th>Channel</th><th>Active</th><th></th></tr>
            @foreach ($templates as $t)
                <tr><td>{{ $t->name }}</td><td class="font-mono text-xs">{{ $t->key }}</td><td>{{ $t->channel }}</td><td>{!! $t->is_active ? '✓' : '—' !!}</td>
                    <td><a href="{{ route('admin.email-templates.edit', $t) }}" class="text-violet-300">Edit</a></td></tr>
            @endforeach
        </table>
    </div>
@endsection
