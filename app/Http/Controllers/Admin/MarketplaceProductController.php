<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CatalogTranslationStatus;
use App\Http\Controllers\Controller;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceProduct;
use App\Models\MarketplaceProductTranslation;
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
        return view('admin.marketplace.products.index', ['products' => MarketplaceProduct::query()->with(['category.translations', 'translations'])->orderBy('sort_order')->latest('id')->paginate(20)]);
    }

    public function create(): View
    {
        return $this->form(new MarketplaceProduct(['is_active' => true]));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $product = MarketplaceProduct::query()->create([
            ...$validated['product'],
            'name' => $validated['translation']['name'],
            'slug' => $validated['translation']['slug'],
            'short_description' => $validated['translation']['short_description'],
            'description' => $validated['translation']['description'],
        ]);
        $product->translations()->create($validated['translation']);

        return redirect()->route('admin.marketplace.products.edit', $product)->with('status', __('marketplace.admin.products.created'));
    }

    public function edit(MarketplaceProduct $product): View
    {
        return $this->form($product);
    }

    public function update(Request $request, MarketplaceProduct $product): RedirectResponse
    {
        $validated = $this->validated($request, $product);
        $oldImage = $product->image_path;
        $product->update($validated['product']);
        foreach ($validated['translations'] as $locale => $translation) {
            if (blank($translation['name'] ?? null)) {
                continue;
            }
            $status = $locale === $validated['product']['source_locale'] ? CatalogTranslationStatus::Source->value : $translation['translation_status'];
            $slug = $this->uniqueTranslationSlug($locale, $translation['slug'] ?: $translation['name'], $product->translation($locale)?->id);
            $product->translations()->updateOrCreate(['locale' => $locale], [
                ...$translation,
                'slug' => $slug,
                'translation_status' => $status,
            ]);
        }
        $source = $product->fresh()->translation($validated['product']['source_locale']);
        if ($source) {
            $product->update([
                'name' => $source->name,
                'slug' => $source->slug,
                'short_description' => $source->short_description,
                'description' => $source->description,
            ]);
        }
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
            'supportedLocales' => config('locales.supported', []),
            'translationStatuses' => CatalogTranslationStatus::cases(),
        ]);
    }

    private function validated(Request $request, ?MarketplaceProduct $product = null): array
    {
        if (! $product?->exists) {
            $request->merge(['slug' => $request->filled('slug') ? Str::slug($request->string('slug')) : Str::slug($request->string('name'))]);
        }
        $rules = [
            'marketplace_category_id' => ['required', Rule::exists('marketplace_categories', 'id')->where('is_active', true)],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'print_size' => ['required', Rule::in(MarketplaceProduct::PRINT_SIZES)],
            'token_cost' => ['required', 'integer', 'min:1', 'max:100000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
        if ($product?->exists) {
            $locales = array_keys(config('locales.supported', ['pl' => [], 'en' => []]));
            $rules += [
                'source_locale' => ['required', Rule::in($locales)],
                'translations' => ['required', 'array'],
                'translations.*.name' => ['nullable', 'string', 'max:180'],
                'translations.*.slug' => ['nullable', 'string', 'max:200'],
                'translations.*.short_description' => ['nullable', 'string', 'max:500'],
                'translations.*.description' => ['nullable', 'string', 'max:10000'],
                'translations.*.translation_status' => ['required', Rule::in(CatalogTranslationStatus::values())],
            ];
        } else {
            $rules += [
                'name' => ['required', 'string', 'max:180'],
                'slug' => ['required', 'string', 'max:200', Rule::unique('marketplace_products')],
                'short_description' => ['required', 'string', 'max:500'],
                'description' => ['required', 'string', 'max:10000'],
            ];
        }
        $validated = $request->validate($rules);
        unset($validated['image']);
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('marketplace/products', 'public');
        }

        $base = [
            'marketplace_category_id' => $validated['marketplace_category_id'],
            'source_locale' => $validated['source_locale'] ?? config('locales.default', 'pl'),
            'print_size' => $validated['print_size'],
            'token_cost' => $validated['token_cost'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ];
        if ($imagePath) {
            $base['image_path'] = $imagePath;
        }

        if ($product?->exists) {
            return ['product' => $base, 'translations' => $validated['translations']];
        }

        return ['product' => $base, 'translation' => [
            'locale' => $base['source_locale'],
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'short_description' => $validated['short_description'],
            'description' => $validated['description'],
            'translation_status' => CatalogTranslationStatus::Source->value,
        ]];
    }

    private function uniqueTranslationSlug(string $locale, string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'product';
        $slug = $base;
        $number = 2;
        while (MarketplaceProductTranslation::query()->where('locale', $locale)->where('slug', $slug)->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$number++;
        }

        return $slug;
    }
}
