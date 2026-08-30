<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CatalogTranslationStatus;
use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Models\ProductCategoryTranslation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductCategoryController extends Controller
{
    public function index(): View
    {
        $categories = ProductCategory::query()
            ->with('translations')
            ->withCount(['products', 'children'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('admin.product-categories.index', [
            'categories' => $categories,
            'categoryTree' => ProductCategory::flattenTree($categories),
            'supportedLocales' => config('locales.supported', []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateCategory($request);

        $category = ProductCategory::create([
            'parent_id' => $validated['parent_id'],
            'source_locale' => $validated['source_locale'],
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        $this->syncTranslations($category, $validated);

        return back()->with(
            'status',
            __('catalog.admin.categories.messages.created')
        );
    }

    public function update(
        Request $request,
        ProductCategory $productCategory
    ): RedirectResponse {
        $validated = $this->validateCategory(
            $request,
            $productCategory
        );

        $productCategory->update([
            'parent_id' => $validated['parent_id'],
            'source_locale' => $validated['source_locale'],
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        $this->syncTranslations($productCategory, $validated);

        return back()->with(
            'status',
            __('catalog.admin.categories.messages.updated')
        );
    }

    public function destroy(
        ProductCategory $productCategory
    ): RedirectResponse {
        if ($productCategory->products()->exists()) {
            return back()->withErrors([
                'category_delete' => __(
                    'catalog.admin.categories.messages.in_use'
                ),
            ]);
        }

        if ($productCategory->children()->exists()) {
            return back()->withErrors([
                'category_delete' => __(
                    'catalog.admin.categories.messages.has_children'
                ),
            ]);
        }

        $productCategory->delete();

        return back()->with(
            'status',
            __('catalog.admin.categories.messages.deleted')
        );
    }

    private function validateCategory(
        Request $request,
        ?ProductCategory $category = null
    ): array {
        $supported = array_keys(
            config('locales.supported', ['pl' => []])
        );

        $sourceLocale = (string) $request->input(
            'source_locale',
            config('locales.default', 'pl')
        );

        $rules = [
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('product_categories', 'id'),
            ],
            'source_locale' => ['required', Rule::in($supported)],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'translations' => ['required', 'array'],
        ];

        foreach ($supported as $locale) {
            $required = $locale === $sourceLocale
                ? 'required'
                : 'nullable';

            $rules["translations.{$locale}.name"] = [
                $required,
                'string',
                'max:160',
            ];

            $rules["translations.{$locale}.slug"] = [
                'nullable',
                'string',
                'max:180',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            ];

            $rules["translations.{$locale}.description"] = [
                'nullable',
                'string',
                'max:3000',
            ];
            $rules["translations.{$locale}.content_html"] = [
                'nullable',
                'string',
                'max:50000',
            ];

            $rules["translations.{$locale}.seo_title"] = [
                'nullable',
                'string',
                'max:180',
            ];

            $rules["translations.{$locale}.seo_description"] = [
                'nullable',
                'string',
                'max:320',
            ];

            $rules["translations.{$locale}.translation_status"] = [
                'nullable',
                Rule::in(CatalogTranslationStatus::values()),
            ];
        }

        $validated = $request->validate($rules);

        $parentId = isset($validated['parent_id'])
            ? (int) $validated['parent_id']
            : null;

        $validated['parent_id'] = $parentId ?: null;

        if (
            $category
            && $validated['parent_id'] !== null
            && in_array(
                $validated['parent_id'],
                $category->descendantIds(),
                true
            )
        ) {
            throw ValidationException::withMessages([
                'parent_id' => __(
                    'catalog.validation.category_parent_cycle'
                ),
            ]);
        }

        return $validated;
    }

    private function syncTranslations(
        ProductCategory $category,
        array $validated
    ): void {
        foreach (
            array_keys(config('locales.supported', ['pl' => []]))
            as $locale
        ) {
            $data = $validated['translations'][$locale] ?? [];
            $name = trim((string) ($data['name'] ?? ''));

            $existing = $category->translations()
                ->where('locale', $locale)
                ->first();

            if (
                $locale !== $category->source_locale
                && $name === ''
            ) {
                $existing?->delete();
                continue;
            }

            $status = $locale === $category->source_locale
                ? CatalogTranslationStatus::Source
                : CatalogTranslationStatus::from(
                    $data['translation_status']
                        ?? CatalogTranslationStatus::Draft->value
                );

            $category->translations()->updateOrCreate(
                ['locale' => $locale],
                [
                    'name' => $name,
                    'slug' => $this->uniqueSlug(
                        $locale,
                        ($data['slug'] ?? null) ?: $name,
                        $existing
                    ),
                    'description' =>
                        ($data['description'] ?? null) ?: null,
                    'content_html' =>
                        ($data['content_html'] ?? null) ?: null,
                    'seo_title' =>
                        ($data['seo_title'] ?? null) ?: null,
                    'seo_description' =>
                        ($data['seo_description'] ?? null) ?: null,
                    'translation_status' => $status,
                ]
            );
        }
    }

    private function uniqueSlug(
        string $locale,
        string $source,
        ?ProductCategoryTranslation $ignore = null
    ): string {
        $base = Str::slug($source) ?: 'category';
        $slug = $base;
        $counter = 2;

        while (
            ProductCategoryTranslation::query()
                ->where('locale', $locale)
                ->where('slug', $slug)
                ->when(
                    $ignore,
                    fn ($query) => $query->whereKeyNot(
                        $ignore->getKey()
                    )
                )
                ->exists()
        ) {
            $slug = $base . '-' . $counter++;
        }

        return $slug;
    }
}
