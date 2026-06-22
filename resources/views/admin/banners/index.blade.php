@extends('admin.layout')
@section('title', 'Banners')
@section('heading', 'Banners')
@section('content')
    <form method="POST" action="{{ route('admin.banners.store') }}" enctype="multipart/form-data" class="glass rounded-xl p-4 grid md:grid-cols-3 gap-3 items-end mb-5">
        @csrf
        <div><label>Title</label><input name="title"></div>
        <div><label>Subtitle</label><input name="subtitle"></div>
        <div><label>Placement</label><select name="placement"><option value="hero">Hero</option><option value="promo">Promo</option><option value="sidebar">Sidebar</option></select></div>
        <div><label>Link URL</label><input name="link_url"></div>
        <div><label>CTA label</label><input name="cta_label"></div>
        <div><label>Image</label><input type="file" name="image" accept="image/*"></div>
        <label class="flex items-center gap-2 text-gray-300"><input type="checkbox" name="is_active" value="1" class="w-auto" checked> Active</label>
        <button class="btn">Add banner</button>
    </form>
    <div class="glass rounded-xl p-4">
        <table>
            <tr><th>Title</th><th>Placement</th><th>Active</th><th></th></tr>
            @foreach ($banners as $b)
                <tr><td>{{ $b->title }}</td><td>{{ $b->placement }}</td><td>{!! $b->is_active ? '✓' : '—' !!}</td>
                    <td><form method="POST" action="{{ route('admin.banners.destroy', $b) }}">@csrf @method('DELETE')<button class="text-red-300">Del</button></form></td></tr>
            @endforeach
        </table>
    </div>
@endsection
