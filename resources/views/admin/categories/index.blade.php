@extends('admin.layout')
@section('title', 'Categories')
@section('heading', 'Categories')
@section('content')
    <div class="flex justify-end mb-4"><a href="{{ route('admin.categories.create') }}" class="btn">New category</a></div>
    <div class="glass rounded-xl p-4">
        <table>
            <tr><th>Name</th><th>Access</th><th>Price</th><th>Videos</th><th>Active</th><th></th></tr>
            @foreach ($categories as $c)
                <tr>
                    <td>{{ $c->name }}</td>
                    <td>{{ ucfirst($c->access_type) }}</td>
                    <td>{{ $c->access_type === 'free' ? '—' : money($c->price, $c->currency) }}</td>
                    <td>{{ $c->videos_count }}</td>
                    <td>{!! $c->is_active ? '<span class="text-emerald-400">Yes</span>' : '<span class="text-gray-500">No</span>' !!}</td>
                    <td><a href="{{ route('admin.categories.edit', $c) }}" class="text-violet-300">Edit</a></td>
                </tr>
            @endforeach
        </table>
    </div>
    <div class="mt-4">{{ $categories->links() }}</div>
@endsection
