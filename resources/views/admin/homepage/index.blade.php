@extends('admin.layout')
@section('title', 'Homepage')
@section('heading', 'Homepage sections')
@section('content')
    <form method="POST" action="{{ route('admin.homepage.store') }}" class="glass rounded-xl p-4 grid md:grid-cols-5 gap-3 items-end mb-5">
        @csrf
        <div><label>Title</label><input name="title" required></div>
        <div><label>Type</label><select name="type">@foreach (['featured_categories','trending','newest','recently_added','recommended','because_you_watched','continue_watching','category_row','manual'] as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach</select></div>
        <div><label>Limit</label><input name="item_limit" type="number" value="12"></div>
        <div><label>Layout</label><select name="layout"><option value="carousel">Carousel</option><option value="grid">Grid</option><option value="hero">Hero</option></select></div>
        <button class="btn">Add</button>
    </form>
    <div class="glass rounded-xl p-4">
        <table>
            <tr><th>Order</th><th>Title</th><th>Type</th><th>Active</th><th></th></tr>
            @foreach ($sections as $s)
                <tr>
                    <td>{{ $s->sort_order }}</td><td>{{ $s->title }}</td><td>{{ $s->type }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.homepage.update', $s) }}" class="flex items-center gap-2">@csrf @method('PUT')
                            <input type="hidden" name="title" value="{{ $s->title }}"><input type="hidden" name="type" value="{{ $s->type }}">
                            <input type="number" name="sort_order" value="{{ $s->sort_order }}" class="w-16">
                            <label class="flex items-center gap-1 text-gray-300"><input type="checkbox" name="is_active" value="1" class="w-auto" @checked($s->is_active)> on</label>
                            <button class="text-xs text-violet-300">Save</button>
                        </form>
                    </td>
                    <td><form method="POST" action="{{ route('admin.homepage.destroy', $s) }}">@csrf @method('DELETE')<button class="text-red-300">Del</button></form></td>
                </tr>
            @endforeach
        </table>
    </div>
@endsection
