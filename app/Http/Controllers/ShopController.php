<?php

namespace App\Http\Controllers;

use App\Enums\CatalogTranslationStatus;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductCategoryTranslation;
use App\Models\ProductTranslation;
use App\Services\SeoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function index(
        Request $request,
        string $locale
    ): View|RedirectResponse {
        $categories = $this->publicCategories();

        if ($request->filled('category')) {
            $legacyTranslation = ProductCategoryTranslation::query()
                ->where('locale', $locale)
                ->where('slug', $request->input('category'))
                ->whereIn(
                    'translation_status',
                    CatalogTranslationStatus::publicValues()
                )
                ->whereHas(
                    'category',
                    fn ($query) => $query->where('is_active', true)
                )
                ->first();

            $legacyCategory = $legacyTranslation
                ? $categories->firstWhere(
                    'id',
                    $legacyTranslation->product_category_id
                )
                : null;

            $canonical = $legacyCategory?->publicUrlFrom(
                $categories,
                $locale
            );

            if ($canonical) {
                return redirect()->to($canonical, 301);
            }
        }

        return view('shop.index', [
            'products' => $this->products($locale),
            'categories' => $categories,
            'categoryTree' => ProductCategory::flattenTree($categories),
            'selectedCategory' => null,
            'selectedCategoryModel' => null,
            'categoryBreadcrumbs' => collect(),
            'isCategoryPage' => false,
        ]);
    }

    public function category(
        string $locale,
        string $path,
        SeoService $seo
    ): View {
        return $this->categoryView(
            $locale,
            $path,
            $seo
        ) ?? abort(404);
    }

    public function show(
        string $locale,
        string $slug,
        SeoService $seo
    ): View {
        $translation = $this->productTranslation(
            $locale,
            $slug
        );

        // Keep established product URLs authoritative when a root
        // category happens to use the same single-segment slug.
        if ($translation) {
            return $this->productView(
                $locale,
                $translation,
                $seo
            );
        }

        if ($this->categorySegment($locale) === 'shop') {
            $categoryView = $this->categoryView(
                $locale,
                $slug,
                $seo
            );

            if ($categoryView) {
                return $categoryView;
            }
        }

        abort(404);
    }

    private function categoryView(
        string $locale,
        string $path,
        SeoService $seo
    ): ?View {
        $categories = $this->publicCategories();
        $path = trim($path, '/');

        $category = $categories->first(
            fn (ProductCategory $candidate): bool =>
                $candidate->localizedPathFrom(
                    $categories,
                    $locale
                ) === $path
        );

        if (! $category) {
            return null;
        }

        $translation = $category->publicTranslation($locale);

        if (! $translation) {
            return null;
        }

        $branchCategoryIds = $category->descendantIdsFrom(
            $categories
        );

        return view('shop.index', [
            'products' => $this->products(
                $locale,
                $branchCategoryIds
            ),
            'categories' => $categories,
            'categoryTree' => ProductCategory::flattenTree($categories),
            'selectedCategory' => $translation,
            'selectedCategoryModel' => $category,
            'categoryBreadcrumbs' => $category->pathFrom($categories),
            'isCategoryPage' => true,
            'pageSeo' => $seo->productCategory(
                $category,
                $translation,
                $categories
            ),
        ]);
    }

    private function productView(
        string $locale,
        ProductTranslation $translation,
        SeoService $seo
    ): View {
        $product = $translation->product;
        $categoryBreadcrumbs = collect();
        $categoryUrlCategories = collect();

        if ($product->category) {
            $categoryUrlCategories = $this->publicCategories();
            $categoryBreadcrumbs = $product->category->pathFrom(
                $categoryUrlCategories
            );
        }

        return view('shop.show', [
            'translation' => $translation,
            'product' => $product,
            'categoryBreadcrumbs' => $categoryBreadcrumbs,
            'categoryUrlCategories' => $categoryUrlCategories,
            'pageSeo' => $seo->product(
                $product,
                $translation
            ),
        ]);
    }

    private function productTranslation(
        string $locale,
        string $slug
    ): ?ProductTranslation {
        $publicStatuses = CatalogTranslationStatus::publicValues();

        return ProductTranslation::query()
            ->with([
                'product.activeVariants',
                'product.media',
                'product.translations' => fn ($query) => $query
                    ->whereIn(
                        'translation_status',
                        $publicStatuses
                    ),
                'product.category.translations' => fn ($query) => $query
                    ->whereIn(
                        'translation_status',
                        $publicStatuses
                    ),
            ])
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->whereIn(
                'translation_status',
                $publicStatuses
            )
            ->whereHas('product', function ($query) {
                $query
                    ->active()
                    ->whereHas('activeVariants');
            })
            ->first();
    }

    private function publicCategories(): Collection
    {
        $publicStatuses = CatalogTranslationStatus::publicValues();

        return ProductCategory::query()
            ->where('is_active', true)
            ->whereHas(
                'translations',
                fn ($query) => $query->whereIn(
                    'translation_status',
                    $publicStatuses
                )
            )
            ->with([
                'translations' => fn ($query) => $query->whereIn(
                    'translation_status',
                    $publicStatuses
                ),
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    private function products(
        string $locale,
        ?array $categoryIds = null
    ) {
        $publicStatuses = CatalogTranslationStatus::publicValues();

        return Product::query()
            ->active()
            ->whereHas('category', function ($query) use (
                $locale,
                $publicStatuses
            ) {
                $query
                    ->where('is_active', true)
                    ->whereHas(
                        'translations',
                        function ($translationQuery) use (
                            $locale,
                            $publicStatuses
                        ) {
                            $translationQuery
                                ->where('locale', $locale)
                                ->whereIn(
                                    'translation_status',
                                    $publicStatuses
                                );
                        }
                    );
            })
            ->when(
                $categoryIds !== null,
                fn ($query) => $query->whereIn(
                    'category_id',
                    $categoryIds
                )
            )
            ->whereHas('translations', function ($query) use (
                $locale,
                $publicStatuses
            ) {
                $query
                    ->where('locale', $locale)
                    ->whereIn(
                        'translation_status',
                        $publicStatuses
                    );
            })
            ->whereHas('activeVariants')
            ->with([
                'translations' => fn ($query) => $query
                    ->where('locale', $locale)
                    ->whereIn(
                        'translation_status',
                        $publicStatuses
                    ),
                'activeVariants',
                'media',
                'category.translations' => fn ($query) => $query
                    ->where('locale', $locale)
                    ->whereIn(
                        'translation_status',
                        $publicStatuses
                    ),
            ])
            ->orderByDesc('is_featured')
            ->latest('id')
            ->paginate(24)
            ->withQueryString();
    }

    private function categorySegment(string $locale): string
    {
        return trim(
            (string) config(
                'locales.supported.'
                . $locale
                . '.shop_category_segment',
                'shop'
            ),
            '/'
        ) ?: 'shop';
    }
}
