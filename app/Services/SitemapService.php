<?php

namespace App\Services;

use App\Enums\ArchiveTranslationStatus;
use App\Enums\ArticleTranslationStatus;
use App\Enums\CatalogTranslationStatus;
use App\Models\ArchiveItem;
use App\Models\Article;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StereoGalleryItem;

class SitemapService
{
    /**
     * @return list<array{
     *     loc: string,
     *     lastmod: string|null,
     *     alternates: array<string, string>
     * }>
     */
    public function entries(): array
    {
        return [
            ...$this->staticEntries(),
            ...$this->articleEntries(),
            ...$this->categoryEntries(),
            ...$this->productEntries(),
            ...$this->archiveEntries(),
            ...$this->galleryEntries(),
        ];
    }

    /**
     * @return list<array{
     *     loc: string,
     *     lastmod: string|null,
     *     alternates: array<string, string>
     * }>
     */
    private function staticEntries(): array
    {
        $entries = [];
        $locales = array_keys(
            config(
                'locales.supported',
                []
            )
        );

        foreach (
            config(
                'seo.sitemap_static_routes',
                []
            ) as $routeName
        ) {
            $alternates = [];

            foreach (
                $locales as $locale
            ) {
                $alternates[$locale] =
                    route(
                        $routeName,
                        ['locale' => $locale]
                    );
            }

            foreach (
                $alternates as $url
            ) {
                $entries[] = [
                    'loc' => $url,
                    'lastmod' => null,
                    'alternates' =>
                        $alternates,
                ];
            }
        }

        return $entries;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function articleEntries(): array
    {
        $publicStatuses =
            ArticleTranslationStatus::publicValues();

        $entries = [];

        Article::query()
            ->published()
            ->with([
                'translations' =>
                    fn ($query) => $query
                        ->whereIn(
                            'translation_status',
                            $publicStatuses
                        ),
            ])
            ->orderBy('id')
            ->get()
            ->each(
                function (
                    Article $article
                ) use (&$entries): void {
                    $alternates =
                        $article
                            ->translations
                            ->mapWithKeys(
                                fn ($translation): array => [
                                    $translation
                                        ->locale => route(
                                            'articles.show',
                                            [
                                                'locale' =>
                                                    $translation
                                                        ->locale,
                                                'slug' =>
                                                    $translation
                                                        ->slug,
                                            ]
                                        ),
                                ]
                            )
                            ->all();

                    foreach (
                        $alternates as $url
                    ) {
                        $entries[] = [
                            'loc' => $url,
                            'lastmod' =>
                                $article
                                    ->updated_at
                                    ?->toDateString(),
                            'alternates' =>
                                $alternates,
                        ];
                    }
                }
            );

        return $entries;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function categoryEntries(): array
    {
        $publicStatuses =
            CatalogTranslationStatus::publicValues();

        $categories = ProductCategory::query()
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

        $entries = [];

        foreach ($categories as $category) {
            $alternates = [];

            foreach (
                array_keys(config('locales.supported', []))
                as $locale
            ) {
                if (! $category->publicTranslation($locale)) {
                    continue;
                }

                $url = $category->publicUrlFrom(
                    $categories,
                    $locale
                );

                if ($url) {
                    $alternates[$locale] = $url;
                }
            }

            $lastTranslation = $category->translations
                ->sortByDesc('updated_at')
                ->first();
            $lastmod = (
                $lastTranslation?->updated_at
                ?? $category->updated_at
            )?->toDateString();

            foreach ($alternates as $url) {
                $entries[] = [
                    'loc' => $url,
                    'lastmod' => $lastmod,
                    'alternates' => $alternates,
                ];
            }
        }

        return $entries;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function productEntries(): array
    {
        $publicStatuses =
            CatalogTranslationStatus::publicValues();

        $entries = [];

        Product::query()
            ->active()
            ->whereHas('activeVariants')
            ->with([
                'translations' =>
                    fn ($query) => $query
                        ->whereIn(
                            'translation_status',
                            $publicStatuses
                        ),
            ])
            ->orderBy('id')
            ->get()
            ->each(
                function (
                    Product $product
                ) use (&$entries): void {
                    $alternates =
                        $product
                            ->translations
                            ->mapWithKeys(
                                fn ($translation): array => [
                                    $translation
                                        ->locale => route(
                                            'shop.show',
                                            [
                                                'locale' =>
                                                    $translation
                                                        ->locale,
                                                'slug' =>
                                                    $translation
                                                        ->slug,
                                            ]
                                        ),
                                ]
                            )
                            ->all();

                    foreach (
                        $alternates as $url
                    ) {
                        $entries[] = [
                            'loc' => $url,
                            'lastmod' =>
                                $product
                                    ->updated_at
                                    ?->toDateString(),
                            'alternates' =>
                                $alternates,
                        ];
                    }
                }
            );

        return $entries;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function archiveEntries(): array
    {
        $publicStatuses =
            ArchiveTranslationStatus::publicValues();

        $entries = [];

        ArchiveItem::query()
            ->published()
            ->with([
                'translations' =>
                    fn ($query) => $query
                        ->whereIn(
                            'translation_status',
                            $publicStatuses
                        ),
            ])
            ->orderBy('id')
            ->get()
            ->each(
                function (
                    ArchiveItem $item
                ) use (&$entries): void {
                    $alternates =
                        $item
                            ->translations
                            ->mapWithKeys(
                                fn ($translation): array => [
                                    $translation
                                        ->locale => route(
                                            'archive.show',
                                            [
                                                'locale' =>
                                                    $translation
                                                        ->locale,
                                                'slug' =>
                                                    $translation
                                                        ->slug,
                                            ]
                                        ),
                                ]
                            )
                            ->all();

                    foreach (
                        $alternates as $url
                    ) {
                        $entries[] = [
                            'loc' => $url,
                            'lastmod' =>
                                $item
                                    ->updated_at
                                    ?->toDateString(),
                            'alternates' =>
                                $alternates,
                        ];
                    }
                }
            );

        return $entries;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function galleryEntries(): array
    {
        $entries = [];
        $locales = array_keys(
            config(
                'locales.supported',
                []
            )
        );

        StereoGalleryItem::query()
            ->published()
            ->orderBy('id')
            ->get()
            ->each(
                function (
                    StereoGalleryItem $item
                ) use (
                    &$entries,
                    $locales
                ): void {
                    $alternates = [];

                    foreach (
                        $locales as $locale
                    ) {
                        $alternates[
                            $locale
                        ] = route(
                            'gallery.show',
                            [
                                'locale' =>
                                    $locale,
                                'galleryItem' =>
                                    $item,
                            ]
                        );
                    }

                    foreach (
                        $alternates as $url
                    ) {
                        $entries[] = [
                            'loc' => $url,
                            'lastmod' =>
                                $item
                                    ->updated_at
                                    ?->toDateString(),
                            'alternates' =>
                                $alternates,
                        ];
                    }
                }
            );

        return $entries;
    }
}
