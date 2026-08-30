<?php

namespace App\Http\Controllers;

use App\Enums\CatalogTranslationStatus;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductCategoryTranslation;
use App\Models\ProductTranslation;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function index(Request $request, string $locale): View
    {
        $publicStatuses = CatalogTranslationStatus::publicValues();

        $categories = ProductCategory::query()
            ->where('is_active', true)
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
            ->with([
                'translations' => fn ($query) => $query
                    ->where('locale', $locale)
                    ->whereIn(
                        'translation_status',
                        $publicStatuses
                    ),
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $categoryTree = ProductCategory::flattenTree($categories);
        $selectedCategory = null;
        $selectedCategoryModel = null;
        $categoryBreadcrumbs = collect();
        $branchCategoryIds = [];

        if ($request->filled('category')) {
            $selectedCategory = ProductCategoryTranslation::query()
                ->where('locale', $locale)
                ->where('slug', $request->input('category'))
                ->whereIn(
                    'translation_status',
                    $publicStatuses
                )
                ->whereHas(
                    'category',
                    fn ($query) => $query
                        ->where('is_active', true)
                )
                ->first();

            if ($selectedCategory) {
                $selectedCategoryModel = $categories->firstWhere(
                    'id',
                    $selectedCategory->product_category_id
                );

                if ($selectedCategoryModel) {
                    $branchCategoryIds =
                        $selectedCategoryModel->descendantIdsFrom(
                            $categories
                        );

                    $categoryBreadcrumbs =
                        $selectedCategoryModel->pathFrom($categories);
                }
            }
        }

        $products = Product::query()
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
                $selectedCategoryModel,
                fn ($query) => $query->whereIn(
                    'category_id',
                    $branchCategoryIds
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

        return view('shop.index', compact(
            'products',
            'categories',
            'categoryTree',
            'selectedCategory',
            'categoryBreadcrumbs'
        ));
    }

    public function show(
        string $locale,
        string $slug,
        SeoService $seo
    ): View {
        $publicStatuses = CatalogTranslationStatus::publicValues();

        $translation = ProductTranslation::query()
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
            ->firstOrFail();

        $product = $translation->product;
        $categoryBreadcrumbs = collect();

        if ($product->category) {
            $breadcrumbCategories = ProductCategory::query()
                ->where('is_active', true)
                ->whereHas(
                    'translations',
                    function ($query) use (
                        $locale,
                        $publicStatuses
                    ) {
                        $query
                            ->where('locale', $locale)
                            ->whereIn(
                                'translation_status',
                                $publicStatuses
                            );
                    }
                )
                ->with([
                    'translations' => fn ($query) => $query
                        ->where('locale', $locale)
                        ->whereIn(
                            'translation_status',
                            $publicStatuses
                        ),
                ])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            $categoryBreadcrumbs =
                $product->category->pathFrom(
                    $breadcrumbCategories
                );
        }

        return view('shop.show', [
            'translation' => $translation,
            'product' => $product,
            'categoryBreadcrumbs' => $categoryBreadcrumbs,
            'pageSeo' => $seo->product(
                $product,
                $translation
            ),
        ]);
    }
}
