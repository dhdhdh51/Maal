<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Video;
use App\Services\AccessService;
use App\Services\CatalogService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function __construct(
        protected CatalogService $catalog,
        protected AccessService $access,
    ) {}

    public function categories(): View
    {
        $categories = Category::where('is_active', true)
            ->withCount(['videos' => fn ($q) => $q->where('is_published', true)->where('is_disabled', false)])
            ->orderBy('sort_order')
            ->get();

        return view('catalog.categories', compact('categories'));
    }

    public function category(Request $request, Category $category): View
    {
        abort_unless($category->is_active, 404);
        abort_unless($category->availableInCountry($request->header('CF-IPCountry') ?? $request->user()?->country), 451);

        $videos = $this->catalog->playableQuery()
            ->where('category_id', $category->id)
            ->latest('published_at')
            ->paginate(24);

        $this->catalog->decorate($videos->getCollection(), $request->user());

        $hasAccess = $this->access->userHasCategoryAccess($request->user(), $category->id)
            || $this->access->categoryIsFree($category);

        return view('catalog.category', compact('category', 'videos', 'hasAccess'));
    }

    public function browse(Request $request): View
    {
        $filters = $request->only(['q', 'category', 'sort', 'free_preview']);
        $results = $this->catalog->browse($filters, $request->user());
        $categories = Category::where('is_active', true)->orderBy('name')->get(['name', 'slug']);

        return view('catalog.browse', compact('results', 'filters', 'categories'));
    }

    public function video(Request $request, Video $video): View
    {
        abort_if($video->is_disabled, 404);
        $video->loadMissing(['category', 'preview']);

        $this->catalog->decorate(collect([$video]), $request->user());

        $related = $this->catalog->playableQuery()
            ->where('category_id', $video->category_id)
            ->where('id', '!=', $video->id)
            ->limit(8)->get();
        $this->catalog->decorate($related, $request->user());

        return view('catalog.video', compact('video', 'related'));
    }
}
