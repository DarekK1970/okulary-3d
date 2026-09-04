<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CatalogTranslationStatus;
use App\Http\Controllers\Controller;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceCategoryTranslation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MarketplaceCategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.marketplace.categories.index', [
            'categories' => MarketplaceCategory::query()
                ->with('translations')
                ->withCount('products')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $category = MarketplaceCategory::query()->create([
            ...$validated['category'],
            'name' => $validated['translation']['name'],
            'slug' => $validated['translation']['slug'],
            'description' => $validated['translation']['description'],
        ]);
        $category->translations()->create($validated['translation']);

        return back()->with('status', __('marketplace.admin.categories.created'));
    }

    public function edit(MarketplaceCategory $category): View
    {
        $category->load('translations')->loadCount('products');

        return view('admin.marketplace.categories.edit', [
            'category' => $category,
            'supportedLocales' => config('locales.supported', []),
            'translationStatuses' => CatalogTranslationStatus::cases(),
        ]);
    }

    public function update(Request $request, MarketplaceCategory $category): RedirectResponse
    {
        $validated = $this->validatedTranslations($request);
        $sourceLocale = $validated['source_locale'];

        $category->update([
            'source_locale' => $sourceLocale,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        foreach ($validated['translations'] as $locale => $translation) {
            if (blank($translation['name'] ?? null)) {
                continue;
            }

            $status = $locale === $sourceLocale
                ? CatalogTranslationStatus::Source->value
                : $translation['translation_status'];
            $slug = $this->uniqueTranslationSlug(
                $locale,
                $translation['slug'] ?: $translation['name'],
                $category->translation($locale)?->id
            );

            $category->translations()->updateOrCreate(['locale' => $locale], [
                'name' => $translation['name'],
                'slug' => $slug,
                'description' => $translation['description'] ?? null,
                'translation_status' => $status,
            ]);
        }

        $source = $category->fresh()->translation($sourceLocale);
        if ($source) {
            $category->update([
                'name' => $source->name,
                'slug' => $source->slug,
                'description' => $source->description,
            ]);
        }

        return redirect()->route('admin.marketplace.categories.index')->with('status', __('marketplace.admin.categories.updated'));
    }

    public function destroy(MarketplaceCategory $category): RedirectResponse
    {
        abort_if($category->products()->exists(), 422, __('marketplace.admin.categories.not_empty'));
        $category->delete();

        return back()->with('status', __('marketplace.admin.categories.deleted'));
    }

    private function validated(Request $request): array
    {
        $request->merge(['slug' => $request->filled('slug') ? Str::slug($request->string('slug')) : Str::slug($request->string('name'))]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:170', Rule::unique('marketplace_categories')],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        return [
            'category' => [
                'source_locale' => config('locales.default', 'pl'),
                'is_active' => $request->boolean('is_active'),
                'sort_order' => $validated['sort_order'] ?? 0,
            ],
            'translation' => [
                'locale' => config('locales.default', 'pl'),
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'description' => $validated['description'] ?? null,
                'translation_status' => CatalogTranslationStatus::Source->value,
            ],
        ];
    }

    private function validatedTranslations(Request $request): array
    {
        $locales = array_keys(config('locales.supported', ['pl' => [], 'en' => []]));

        return $request->validate([
            'source_locale' => ['required', Rule::in($locales)],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'translations' => ['required', 'array'],
            'translations.*.name' => ['nullable', 'string', 'max:150'],
            'translations.*.slug' => ['nullable', 'string', 'max:170'],
            'translations.*.description' => ['nullable', 'string', 'max:2000'],
            'translations.*.translation_status' => ['required', Rule::in(CatalogTranslationStatus::values())],
        ]);
    }

    private function uniqueTranslationSlug(string $locale, string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'category';
        $slug = $base;
        $number = 2;

        while (MarketplaceCategoryTranslation::query()
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$number++;
        }

        return $slug;
    }
}
