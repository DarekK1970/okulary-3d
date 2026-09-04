<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceProduct;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MarketplaceProductController extends Controller
{
    public function index(): View
    {
        return view('admin.marketplace.products.index', ['products' => MarketplaceProduct::query()->with('category')->orderBy('sort_order')->latest('id')->paginate(20)]);
    }

    public function create(): View
    {
        return $this->form(new MarketplaceProduct(['is_active' => true]));
    }

    public function store(Request $request): RedirectResponse
    {
        $product = MarketplaceProduct::query()->create($this->validated($request));

        return redirect()->route('admin.marketplace.products.edit', $product)->with('status', __('marketplace.admin.products.created'));
    }

    public function edit(MarketplaceProduct $product): View
    {
        return $this->form($product);
    }

    public function update(Request $request, MarketplaceProduct $product): RedirectResponse
    {
        $oldImage = $product->image_path;
        $product->update($this->validated($request, $product));
        if ($request->hasFile('image') && $oldImage) {
            Storage::disk('public')->delete($oldImage);
        }

        return back()->with('status', __('marketplace.admin.products.updated'));
    }

    public function destroy(MarketplaceProduct $product): RedirectResponse
    {
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }
        $product->delete();

        return redirect()->route('admin.marketplace.products.index')->with('status', __('marketplace.admin.products.deleted'));
    }

    private function form(MarketplaceProduct $product): View
    {
        return view('admin.marketplace.products.form', [
            'product' => $product,
            'categories' => MarketplaceCategory::query()->with('translations')->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'printSizes' => MarketplaceProduct::PRINT_SIZES,
        ]);
    }

    private function validated(Request $request, ?MarketplaceProduct $product = null): array
    {
        $request->merge(['slug' => $request->filled('slug') ? Str::slug($request->string('slug')) : Str::slug($request->string('name'))]);
        $validated = $request->validate([
            'marketplace_category_id' => ['required', Rule::exists('marketplace_categories', 'id')->where('is_active', true)],
            'name' => ['required', 'string', 'max:180'],
            'slug' => ['required', 'string', 'max:200', Rule::unique('marketplace_products')->ignore($product)],
            'short_description' => ['required', 'string', 'max:500'],
            'description' => ['required', 'string', 'max:10000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'print_size' => ['required', Rule::in(MarketplaceProduct::PRINT_SIZES)],
            'token_cost' => ['required', 'integer', 'min:1', 'max:100000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
        unset($validated['image']);
        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('marketplace/products', 'public');
        }
        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] ??= 0;

        return $validated;
    }
}
