<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CatalogTranslationStatus;
use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductTranslation;
use App\Models\ProductVariant;
use App\Services\ArticleHtmlSanitizer;
use App\Services\MediaAssetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::query()
            ->with([
                'translations',
                'category.translations',
                'variants',
                'media',
            ])
            ->latest('id');

        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));

            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('brand', 'like', '%' . $search . '%')
                    ->orWhereHas('translations', function ($translationQuery) use ($search) {
                        $translationQuery->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('variants', function ($variantQuery) use ($search) {
                        $variantQuery->where('sku', 'like', '%' . $search . '%');
                    });
            });
        }

        if (
            $request->filled('status')
            && in_array($request->input('status'), ProductStatus::values(), true)
        ) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('category')) {
            $query->where('category_id', (int) $request->input('category'));
        }

        return view('admin.products.index', [
            'products' => $query->paginate(20)->withQueryString(),
            'categories' => $this->categories(),
            'statuses' => ProductStatus::cases(),
            'supportedLocales' => config('locales.supported', []),
        ]);
    }

    public function create(): View
    {
        return view('admin.products.create', [
            'product' => new Product([
                'status' => ProductStatus::Draft,
                'source_locale' => config('locales.default', 'pl'),
            ]),
            'categories' => $this->categories(),
            'statuses' => ProductStatus::cases(),
            'translationStatuses' => CatalogTranslationStatus::cases(),
            'supportedLocales' => config('locales.supported', []),
            'mediaAssets' => $this->mediaAssets(),
        ]);
    }

    public function store(
        Request $request,
        ArticleHtmlSanitizer $sanitizer,
        MediaAssetService $mediaService
    ): RedirectResponse {
        $validated = $this->validateProduct($request);

        $product = DB::transaction(function () use (
            $request,
            $validated,
            $sanitizer,
            $mediaService
        ) {
            $product = Product::create([
                'category_id' => (int) $validated['category_id'],
                'source_locale' => $validated['source_locale'],
                'status' => ProductStatus::from($validated['status']),
                'brand' => ($validated['brand'] ?? null) ?: null,
                'is_featured' => $request->boolean('is_featured'),
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            $this->syncTranslations($product, $validated, $sanitizer);
            $this->syncVariants($product, $validated['variants']);
            $this->syncMedia($request, $product, $validated, $mediaService);

            return $product;
        });

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('status', __('catalog.admin.products.messages.created'));
    }

    public function edit(Product $product): View
    {
        $product->load(['translations', 'variants', 'media']);

        return view('admin.products.edit', [
            'product' => $product,
            'categories' => $this->categories(),
            'statuses' => ProductStatus::cases(),
            'translationStatuses' => CatalogTranslationStatus::cases(),
            'supportedLocales' => config('locales.supported', []),
            'mediaAssets' => $this->mediaAssets(),
        ]);
    }

    public function update(
        Request $request,
        Product $product,
        ArticleHtmlSanitizer $sanitizer,
        MediaAssetService $mediaService
    ): RedirectResponse {
        $validated = $this->validateProduct($request, $product);

        DB::transaction(function () use (
            $request,
            $product,
            $validated,
            $sanitizer,
            $mediaService
        ) {
            $product->update([
                'category_id' => (int) $validated['category_id'],
                'source_locale' => $validated['source_locale'],
                'status' => ProductStatus::from($validated['status']),
                'brand' => ($validated['brand'] ?? null) ?: null,
                'is_featured' => $request->boolean('is_featured'),
                'updated_by' => $request->user()->id,
            ]);

            $this->syncTranslations($product, $validated, $sanitizer);
            $this->syncVariants($product, $validated['variants']);
            $this->syncMedia($request, $product, $validated, $mediaService);
        });

        return back()->with(
            'status',
            __('catalog.admin.products.messages.updated')
        );
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('status', __('catalog.admin.products.messages.deleted'));
    }

    private function validateProduct(
        Request $request,
        ?Product $product = null
    ): array {
        $supported = array_keys(
            config('locales.supported', ['pl' => []])
        );

        $sourceLocale = (string) $request->input(
            'source_locale',
            config('locales.default', 'pl')
        );

        $rules = [
            'category_id' => [
                'required',
                'integer',
                Rule::exists('product_categories', 'id')
                    ->where(fn ($query) => $query->where('is_active', true)),
            ],
            'source_locale' => ['required', Rule::in($supported)],
            'status' => ['required', Rule::in(ProductStatus::values())],
            'brand' => ['nullable', 'string', 'max:120'],
            'is_featured' => ['nullable', 'boolean'],
            'translations' => ['required', 'array'],
            'variants' => ['required', 'array', 'min:1'],
            'media_ids' => ['nullable', 'array', 'max:20'],
            'media_ids.*' => ['integer', 'distinct', Rule::exists('media_assets', 'id')],
            'primary_media_id' => ['nullable', 'integer', Rule::exists('media_assets', 'id')],
            'new_media' => ['nullable', 'array', 'max:5'],
            'new_media.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];

        foreach ($supported as $locale) {
            $required = $locale === $sourceLocale ? 'required' : 'nullable';

            $rules["translations.{$locale}.name"] = [$required, 'string', 'max:220'];
            $rules["translations.{$locale}.slug"] = [
                'nullable',
                'string',
                'max:240',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            ];
            $rules["translations.{$locale}.short_description"] = [
                'nullable',
                'string',
                'max:1200',
            ];
            $rules["translations.{$locale}.description_html"] = [$required, 'string'];
            $rules["translations.{$locale}.seo_title"] = ['nullable', 'string', 'max:70'];
            $rules["translations.{$locale}.seo_description"] = ['nullable', 'string', 'max:180'];
            $rules["translations.{$locale}.translation_status"] = [
                'nullable',
                Rule::in(CatalogTranslationStatus::values()),
            ];
        }

        foreach ((array) $request->input('variants', []) as $index => $variant) {
            $variantId = ! empty($variant['id']) ? (int) $variant['id'] : null;

            $rules["variants.{$index}.id"] = ['nullable', 'integer'];
            $rules["variants.{$index}.sku"] = [
                'required',
                'string',
                'max:100',
                Rule::unique('product_variants', 'sku')->ignore($variantId),
            ];
            $rules["variants.{$index}.name"] = ['nullable', 'string', 'max:140'];
            $rules["variants.{$index}.price_gross"] = ['required', 'numeric', 'min:0'];
            $rules["variants.{$index}.vat_rate"] = ['required', 'numeric', 'min:0', 'max:100'];
            $rules["variants.{$index}.currency"] = ['required', Rule::in(['PLN', 'EUR'])];
            $rules["variants.{$index}.stock_quantity"] = ['required', 'integer', 'min:0'];
            $rules["variants.{$index}.track_stock"] = ['nullable', 'boolean'];
            $rules["variants.{$index}.is_active"] = ['nullable', 'boolean'];
            $rules["variants.{$index}.sort_order"] = ['nullable', 'integer', 'min:0', 'max:9999'];
        }

        $validated = $request->validate($rules);

        foreach ($supported as $locale) {
            if ($locale === $sourceLocale) {
                continue;
            }

            $translation = $validated['translations'][$locale] ?? [];
            $name = trim((string) ($translation['name'] ?? ''));
            $description = trim((string) ($translation['description_html'] ?? ''));

            if (($name === '') xor ($description === '')) {
                throw ValidationException::withMessages([
                    "translations.{$locale}.name" => __(
                        'catalog.validation.translation_complete'
                    ),
                ]);
            }
        }

        if ($product) {
            foreach ($validated['variants'] as $variant) {
                if (
                    ! empty($variant['id'])
                    && ! $product->variants()->whereKey((int) $variant['id'])->exists()
                ) {
                    throw ValidationException::withMessages([
                        'variants' => __('catalog.validation.variant_not_owned'),
                    ]);
                }
            }
        }

        $mediaIds = array_map('intval', $validated['media_ids'] ?? []);
        $primary = isset($validated['primary_media_id'])
            ? (int) $validated['primary_media_id']
            : null;

        if ($primary && ! in_array($primary, $mediaIds, true)) {
            throw ValidationException::withMessages([
                'primary_media_id' => __(
                    'catalog.validation.primary_must_be_selected'
                ),
            ]);
        }

        return $validated;
    }

    private function syncTranslations(
        Product $product,
        array $validated,
        ArticleHtmlSanitizer $sanitizer
    ): void {
        foreach (
            array_keys(config('locales.supported', ['pl' => []]))
            as $locale
        ) {
            $data = $validated['translations'][$locale] ?? [];
            $name = trim((string) ($data['name'] ?? ''));
            $description = trim((string) ($data['description_html'] ?? ''));

            $existing = $product->translations()
                ->where('locale', $locale)
                ->first();

            if (
                $locale !== $product->source_locale
                && $name === ''
                && $description === ''
            ) {
                $existing?->delete();
                continue;
            }

            $status = $locale === $product->source_locale
                ? CatalogTranslationStatus::Source
                : CatalogTranslationStatus::from(
                    $data['translation_status']
                        ?? CatalogTranslationStatus::Draft->value
                );

            $product->translations()->updateOrCreate(
                ['locale' => $locale],
                [
                    'name' => $name,
                    'slug' => $this->uniqueSlug(
                        $locale,
                        ($data['slug'] ?? null) ?: $name,
                        $existing
                    ),
                    'short_description' =>
                        ($data['short_description'] ?? null) ?: null,
                    'description_html' =>
                        $sanitizer->sanitize($description),
                    'seo_title' => ($data['seo_title'] ?? null) ?: null,
                    'seo_description' =>
                        ($data['seo_description'] ?? null) ?: null,
                    'translation_status' => $status,
                ]
            );
        }
    }

    private function syncVariants(
        Product $product,
        array $variants
    ): void {
        $keptIds = [];

        foreach ($variants as $index => $data) {
            $variant = ! empty($data['id'])
                ? $product->variants()->findOrFail((int) $data['id'])
                : new ProductVariant();

            $variant->fill([
                'sku' => trim($data['sku']),
                'name' => ($data['name'] ?? null) ?: null,
                'price_gross' => $data['price_gross'],
                'vat_rate' => $data['vat_rate'],
                'currency' => $data['currency'],
                'stock_quantity' => (int) $data['stock_quantity'],
                'track_stock' => (bool) ($data['track_stock'] ?? false),
                'is_active' => (bool) ($data['is_active'] ?? false),
                'sort_order' => (int) ($data['sort_order'] ?? $index),
            ]);

            $variant->product_id = $product->id;
            $variant->save();

            $keptIds[] = $variant->id;
        }

        $product->variants()->whereNotIn('id', $keptIds)->delete();
    }

    private function syncMedia(
        Request $request,
        Product $product,
        array $validated,
        MediaAssetService $mediaService
    ): void {
        $mediaIds = array_values(array_unique(array_map(
            'intval',
            $validated['media_ids'] ?? []
        )));

        foreach ($request->file('new_media', []) as $file) {
            $media = $mediaService->storeImage(
                $file,
                $request->user(),
                'products'
            );

            $mediaIds[] = $media->id;
        }

        $mediaIds = array_values(array_unique($mediaIds));

        $primaryId = isset($validated['primary_media_id'])
            ? (int) $validated['primary_media_id']
            : null;

        if (! $primaryId && $mediaIds !== []) {
            $primaryId = $mediaIds[0];
        }

        $sync = [];

        foreach ($mediaIds as $index => $mediaId) {
            $sync[$mediaId] = [
                'is_primary' => $mediaId === $primaryId,
                'sort_order' => $index,
            ];
        }

        $product->media()->sync($sync);
    }

    private function categories()
    {
        return ProductCategory::query()
            ->where('is_active', true)
            ->with('translations')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    private function mediaAssets()
    {
        return MediaAsset::query()
            ->latest('id')
            ->limit(100)
            ->get();
    }

    private function uniqueSlug(
        string $locale,
        string $source,
        ?ProductTranslation $ignore = null
    ): string {
        $base = Str::slug($source) ?: 'product';
        $slug = $base;
        $counter = 2;

        while (
            ProductTranslation::query()
                ->where('locale', $locale)
                ->where('slug', $slug)
                ->when(
                    $ignore,
                    fn ($query) => $query->whereKeyNot($ignore->getKey())
                )
                ->exists()
        ) {
            $slug = $base . '-' . $counter++;
        }

        return $slug;
    }
}
