<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BannerController extends Controller
{
    public function index(): View
    {
        return view('admin.banners.index', ['banners' => Banner::orderBy('sort_order')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Banner::create($this->data($request));

        return back()->with('status', 'Banner added.');
    }

    public function update(Request $request, Banner $banner): RedirectResponse
    {
        $banner->update($this->data($request));

        return back()->with('status', 'Banner updated.');
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        $banner->delete();

        return back()->with('status', 'Banner removed.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function data(Request $request): array
    {
        $v = $request->validate([
            'title' => ['nullable', 'string', 'max:160'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'cta_label' => ['nullable', 'string', 'max:60'],
            'link_url' => ['nullable', 'string', 'max:255'],
            'placement' => ['required', 'in:hero,promo,sidebar'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        if ($request->hasFile('image')) {
            $v['image'] = $request->file('image')->store('banners', 'public');
        } else {
            unset($v['image']);
        }

        $v['is_active'] = $request->boolean('is_active');

        return $v;
    }
}
