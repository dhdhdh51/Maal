@extends('admin.layout')
@section('title', $user->name)
@section('heading', 'User: '.$user->name)
@section('content')
    <div class="grid lg:grid-cols-2 gap-6">
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="glass rounded-xl p-5 space-y-4">
            @csrf @method('PUT')
            <p class="text-sm text-gray-400">{{ $user->email }}</p>
            <div><label>Status</label><select name="status">@foreach (['active','suspended','banned'] as $s)<option value="{{ $s }}" @selected($user->status === $s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
            <div><label>Device limit (0=unlimited, blank=global)</label><input name="device_limit" type="number" value="{{ $user->device_limit }}"></div>
            <div><label>Roles</label>
                @foreach ($roles as $role)
                    <label class="flex items-center gap-2 text-gray-300"><input type="checkbox" name="roles[]" value="{{ $role }}" class="w-auto" @checked($user->hasRole($role))> {{ $role }}</label>
                @endforeach
            </div>
            <button class="btn">Save user</button>
        </form>

        <div class="space-y-4">
            <form method="POST" action="{{ route('admin.users.force_logout', $user) }}" class="glass rounded-xl p-4">@csrf
                <h2 class="font-semibold mb-2">Sessions</h2>
                <p class="text-sm text-gray-400 mb-2">{{ $user->devices->count() }} devices on record.</p>
                <button class="btn-ghost btn">Force logout all</button>
            </form>

            <div class="glass rounded-xl p-4">
                <h2 class="font-semibold mb-2">Category access</h2>
                @foreach ($user->categoryAccess as $a)
                    <div class="flex items-center justify-between text-sm py-1">
                        <span>{{ $a->category?->name }} ({{ $a->status }})</span>
                        <form method="POST" action="{{ route('admin.users.revoke', [$user, $a]) }}">@csrf @method('DELETE')<button class="text-red-300 text-xs">Revoke</button></form>
                    </div>
                @endforeach
                <form method="POST" action="{{ route('admin.users.grant', $user) }}" class="mt-3 grid grid-cols-3 gap-2 items-end">
                    @csrf
                    <div class="col-span-1"><label>Category</label><select name="category_id">@foreach ($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
                    <div><label>Days</label><input name="days" type="number" placeholder="∞"></div>
                    <button class="btn">Grant</button>
                </form>
            </div>
        </div>
    </div>
@endsection
