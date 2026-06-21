@extends('admin.layout')
@section('title', 'Support')
@section('heading', 'Support tickets')
@section('content')
    <div class="glass rounded-xl p-4">
        <table>
            <tr><th>Ref</th><th>User</th><th>Subject</th><th>Category</th><th>Status</th><th></th></tr>
            @foreach ($tickets as $t)
                <tr><td class="font-mono text-xs">{{ $t->reference }}</td><td>{{ $t->user?->email }}</td>
                    <td>{{ $t->subject }}</td><td>{{ $t->category }}</td>
                    <td><span class="text-xs px-2 py-0.5 rounded bg-white/10">{{ str_replace('_', ' ', $t->status) }}</span></td>
                    <td><a href="{{ route('admin.tickets.show', $t) }}" class="text-violet-300">Open</a></td></tr>
            @endforeach
        </table>
    </div>
    <div class="mt-4">{{ $tickets->links() }}</div>
@endsection
