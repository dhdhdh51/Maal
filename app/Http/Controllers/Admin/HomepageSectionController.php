<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\HomepageSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomepageSectionController extends Controller
{
    public function index(): View
    {
        return view('admin.homepage.index', [
            'sections' => HomepageSection::orderBy('sort_order')->get(),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        HomepageSection::create($this->data($request));

        return back()->with('status', 'Section added.');
    }

    public function update(Request $request, HomepageSection $section): RedirectResponse
    {
        $section->update($this->data($request));

        return back()->with('status', 'Section updated.');
    }

    public function destroy(HomepageSection $section): RedirectResponse
    {
        $section->delete();

        return back()->with('status', 'Section removed.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function data(Request $request): array
    {
        $v = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'type' => ['required', 'string', 'max:40'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'item_limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'layout' => ['nullable', 'in:carousel,grid,hero'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $v['is_active'] = $request->boolean('is_active');

        return $v;
    }
}
