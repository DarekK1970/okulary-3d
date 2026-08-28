<?php

namespace App\Http\Controllers;

use App\Enums\CatalogTranslationStatus;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductCategoryTranslation;
use App\Models\ProductTranslation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function index(Request $request, string $locale): View
    {
        $publicStatuses = CatalogTranslationStatus::publicValues();
        $selectedCategory = null;

        if ($request->filled('category')) {
            $selectedCategory = ProductCategoryTranslation::query()
                ->where('locale', $locale)
                ->where('slug', $request->input('category'))
                ->whereIn('translation_status', $publicStatuses)
                ->whereHas(
                    'category',
                    fn ($query) => $query->where('is_active', true)
                )
                ->first();
        }

        $products = Product::query()
            ->active()
            ->whereHas('category', function ($query) use (
                $locale,
                $publicStatuses
            ) {
                $query
                    ->where('is_active', true)
                    ->whereHas('translations', function ($translationQuery) use (
                        $locale,
                        $publicStatuses
                    ) {
                        $translationQuery
                            ->where('locale', $locale)
                            ->whereIn('translation_status', $publicStatuses);
                    });
            })
            ->when(
                $selectedCategory,
                fn ($query) => $query->where(
                    'category_id',
                    $selectedCategory->product_category_id
                )
            )
            ->whereHas('translations', function ($query) use (
                $locale,
                $publicStatuses
            ) {
                $query
                    ->where('locale', $locale)
                    ->whereIn('translation_status', $publicStatuses);
            })
            ->whereHas('activeVariants')
            ->with([
                'translations' => fn ($query) => $query
                    ->where('locale', $locale)
                    ->whereIn('translation_status', $publicStatuses),
                'activeVariants',
                'media',
                'category.translations' => fn ($query) => $query
                    ->where('locale', $locale)
                    ->whereIn('translation_status', $publicStatuses),
            ])
            ->orderByDesc('is_featured')
            ->latest('id')
            ->paginate(24)
            ->withQueryString();

        $categories = ProductCategory::query()
            ->where('is_active', true)
            ->whereHas('translations', function ($query) use (
                $locale,
                $publicStatuses
            ) {
                $query
                    ->where('locale', $locale)
                    ->whereIn('translation_status', $publicStatuses);
            })
            ->with([
                'translations' => fn ($query) => $query
                    ->where('locale', $locale)
                    ->whereIn('translation_status', $publicStatuses),
            ])
            ->orderBy('sort_order')
            ->get();

        return view('shop.index', compact(
            'products',
            'categories',
            'selectedCategory'
        ));
    }

    public function show(string $locale, string $slug): View
    {
        $publicStatuses = CatalogTranslationStatus::publicValues();

        $translation = ProductTranslation::query()
            ->with([
                'product.activeVariants',
                'product.media',
                'product.translations' => fn ($query) => $query
                    ->whereIn('translation_status', $publicStatuses),
                'product.category.translations' => fn ($query) => $query
                    ->whereIn('translation_status', $publicStatuses),
            ])
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->whereIn('translation_status', $publicStatuses)
            ->whereHas('product', function ($query) {
                $query->active()->whereHas('activeVariants');
            })
            ->firstOrFail();

        return view('shop.show', [
            'translation' => $translation,
            'product' => $translation->product,
        ]);
    }
}
