@extends('admin.layout')
@section('title', 'Roles')
@section('heading', 'Roles & permissions')
@section('content')
    <div class="space-y-6">
        @foreach ($roles as $role)
            <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="glass rounded-xl p-5">
                @csrf @method('PUT')
                <h2 class="font-semibold mb-3 capitalize">{{ str_replace('_', ' ', $role->name) }}</h2>
                @php($rolePerms = $role->permissions->pluck('name')->all())
                <div class="grid md:grid-cols-3 gap-4">
                    @foreach ($groups as $group => $perms)
                        <div>
                            <p class="text-xs text-gray-500 mb-1 capitalize">{{ $group }}</p>
                            @foreach ($perms as $perm)
                                <label class="flex items-center gap-2 text-sm text-gray-300"><input type="checkbox" name="permissions[]" value="{{ $perm }}" class="w-auto" @checked(in_array($perm, $rolePerms, true))> {{ $perm }}</label>
                            @endforeach
                        </div>
                    @endforeach
                </div>
                <button class="btn mt-3">Save {{ $role->name }}</button>
            </form>
        @endforeach
    </div>
@endsection
