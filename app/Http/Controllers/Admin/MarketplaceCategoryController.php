<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MarketplaceCategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.marketplace.categories.index', ['categories' => MarketplaceCategory::query()->withCount('products')->orderBy('sort_order')->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        MarketplaceCategory::query()->create($this->validated($request));

        return back()->with('status', __('marketplace.admin.categories.created'));
    }

    public function update(Request $request, MarketplaceCategory $category): RedirectResponse
    {
        $category->update($this->validated($request, $category));

        return back()->with('status', __('marketplace.admin.categories.updated'));
    }

    public function destroy(MarketplaceCategory $category): RedirectResponse
    {
        abort_if($category->products()->exists(), 422, __('marketplace.admin.categories.not_empty'));
        $category->delete();

        return back()->with('status', __('marketplace.admin.categories.deleted'));
    }

    private function validated(Request $request, ?MarketplaceCategory $category = null): array
    {
        $request->merge(['slug' => $request->filled('slug') ? Str::slug($request->string('slug')) : Str::slug($request->string('name'))]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:170', Rule::unique('marketplace_categories')->ignore($category)],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] ??= 0;

        return $validated;
    }
}
