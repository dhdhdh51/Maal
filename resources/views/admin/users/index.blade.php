@extends('admin.layout')
@section('title', 'Users')
@section('heading', 'Users')
@section('content')
    <form method="GET" class="mb-4"><input name="q" value="{{ request('q') }}" placeholder="Search name or email…" class="max-w-sm"></form>
    <div class="glass rounded-xl p-4">
        <table>
            <tr><th>Name</th><th>Email</th><th>Status</th><th>Premium</th><th>Joined</th><th></th></tr>
            @foreach ($users as $u)
                <tr><td>{{ $u->name }}</td><td>{{ $u->email }}</td>
                    <td><span class="text-xs px-2 py-0.5 rounded {{ $u->status === 'active' ? 'bg-emerald-500/15 text-emerald-300' : 'bg-red-500/15 text-red-300' }}">{{ $u->status }}</span></td>
                    <td>{!! $u->is_premium ? '✓' : '—' !!}</td>
                    <td>{{ $u->created_at->format('d M Y') }}</td>
                    <td><a href="{{ route('admin.users.show', $u) }}" class="text-violet-300">Manage</a></td></tr>
            @endforeach
        </table>
    </div>
    <div class="mt-4">{{ $users->links() }}</div>
@endsection
