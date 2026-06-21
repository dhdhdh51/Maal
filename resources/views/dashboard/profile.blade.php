@extends('layouts.app')
@section('title', 'Profile')
@section('content')
    <div class="max-w-xl">
        <h1 class="text-2xl font-bold mb-5">Profile</h1>

        <form method="POST" action="{{ route('profile.update') }}" class="glass rounded-xl p-5 space-y-4 mb-6">
            @csrf @method('PUT')
            <div>
                <label class="block text-sm mb-1.5 text-gray-300">Name</label>
                <input name="name" value="{{ old('name', $user->name) }}" class="w-full rounded-lg bg-white/5 border border-white/10 px-3 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-sm mb-1.5 text-gray-300">Phone</label>
                <input name="phone" value="{{ old('phone', $user->phone) }}" class="w-full rounded-lg bg-white/5 border border-white/10 px-3 py-2.5 text-sm">
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-300">
                <input type="checkbox" name="newsletter_opt_in" value="1" @checked($user->newsletter_opt_in)> Subscribe to newsletter
            </label>
            <button class="bg-violet-600 hover:bg-violet-500 px-4 py-2.5 rounded-lg text-sm font-medium text-white">Save profile</button>
        </form>

        <form method="POST" action="{{ route('password.change') }}" class="glass rounded-xl p-5 space-y-4">
            @csrf @method('PUT')
            <h2 class="font-semibold">Change password</h2>
            <input name="current_password" type="password" placeholder="Current password" class="w-full rounded-lg bg-white/5 border border-white/10 px-3 py-2.5 text-sm">
            <input name="password" type="password" placeholder="New password" class="w-full rounded-lg bg-white/5 border border-white/10 px-3 py-2.5 text-sm">
            <input name="password_confirmation" type="password" placeholder="Confirm new password" class="w-full rounded-lg bg-white/5 border border-white/10 px-3 py-2.5 text-sm">
            <button class="bg-white/5 border border-white/10 hover:bg-white/10 px-4 py-2.5 rounded-lg text-sm">Update password</button>
        </form>
    </div>
@endsection
