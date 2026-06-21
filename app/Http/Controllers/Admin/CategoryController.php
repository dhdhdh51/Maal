<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategoryAccessPlan;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::withCount('videos')->orderBy('sort_order')->paginate(30);

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.categories.form', ['category' => new Category]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['slug'] = Str::slug($data['slug'] ?? $data['name']);
        $category = Category::create($data);

        AuditLogger::log('category.created', $category, "Category {$category->name} created");

        return redirect()->route('admin.categories.edit', $category)->with('status', 'Category created.');
    }

    public function edit(Category $category): View
    {
        $category->load('plans');

        return view('admin.categories.form', compact('category'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $this->validateData($request, $category->id);
        $data['slug'] = Str::slug($data['slug'] ?? $data['name']);
        $category->update($data);

        AuditLogger::log('category.updated', $category, "Category {$category->name} updated");

        return back()->with('status', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();
        AuditLogger::log('category.deleted', $category, "Category {$category->name} deleted");

        return redirect()->route('admin.categories.index')->with('status', 'Category deleted.');
    }

    // ---- Plans (nested) ---------------------------------------------------

    public function storePlan(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', 'in:one_time,monthly,quarterly,yearly,lifetime,bundle'],
            'price' => ['required', 'numeric', 'min:0'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'validity_days' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['category_id'] = $category->id;
        $data['currency'] = $category->currency;
        $data['is_active'] = $request->boolean('is_active', true);
        CategoryAccessPlan::create($data);

        return back()->with('status', 'Plan added.');
    }

    public function destroyPlan(Category $category, CategoryAccessPlan $plan): RedirectResponse
    {
        abort_unless($plan->category_id === $category->id, 404);
        $plan->delete();

        return back()->with('status', 'Plan removed.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateData(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string'],
            'access_type' => ['required', 'in:free,paid,subscription,lifetime'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'validity_days' => ['nullable', 'integer', 'min:1'],
            'preview_seconds' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
            'watermark_enabled' => ['nullable', 'boolean'],
            'seo_title' => ['nullable', 'string', 'max:160'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ]) + [
            'is_active' => $request->boolean('is_active'),
            'watermark_enabled' => $request->has('watermark_enabled') ? $request->boolean('watermark_enabled') : null,
        ];
    }
}
