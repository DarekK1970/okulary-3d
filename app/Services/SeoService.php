<?php

namespace App\Services;

use App\Models\ArchiveItem;
use App\Models\ArchiveItemTranslation;
use App\Models\Article;
use App\Models\ArticleTranslation;
use App\Models\Product;
use App\Models\ProductTranslation;
use App\Models\StereoGalleryItem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SeoService
{
    /**
     * @return array<string, mixed>
     */
    public function current(): array
    {
        $routeName = Route::currentRouteName();
        $robots = $this->robots(
            $routeName
        );

        $privateRoute =
            $this->isPrivateRoute(
                $routeName
            );

        $alternates =
            $privateRoute
                ? []
                : $this->genericAlternates(
                    $routeName
                );

        $canonical =
            $privateRoute
                ? route(
                    'home',
                    [
                        'locale' =>
                            app()->getLocale(),
                    ]
                )
                : $this->currentCanonical();

        return [
            'canonical' =>
                $canonical,
            'alternates' =>
                $alternates,
            'x_default' =>
                $this->xDefault(
                    $alternates
                ),
            'robots' => $robots,
            'type' => 'website',
            'image' => null,
            'og_locale' =>
                $this->ogLocale(
                    app()->getLocale()
                ),
            'og_locale_alternates' =>
                $this->ogLocaleAlternates(
                    array_keys(
                        $alternates
                    )
                ),
            'schemas' =>
                $this->baseSchemas(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function article(
        Article $article,
        ArticleTranslation $translation
    ): array {
        $article->loadMissing([
            'translations',
            'heroMedia',
            'category',
        ]);

        $alternates =
            $article->translations
                ->filter(
                    fn ($item): bool =>
                        $item->isPubliclyReady()
                )
                ->mapWithKeys(
                    fn ($item): array => [
                        $item->locale => route(
                            'articles.show',
                            [
                                'locale' =>
                                    $item->locale,
                                'slug' =>
                                    $item->slug,
                            ]
                        ),
                    ]
                )
                ->all();

        $canonical =
            route(
                'articles.show',
                [
                    'locale' =>
                        $translation->locale,
                    'slug' =>
                        $translation->slug,
                ]
            );

        $image =
            $this->absoluteUrl(
                $article->heroMedia?->url()
            );

        if (
            ! $image
            && filled(
                $article->hero_image_path
            )
        ) {
            $image =
                $this->absoluteUrl(
                    Storage::disk('public')
                        ->url(
                            $article
                                ->hero_image_path
                        )
                );
        }

        $description =
            $translation->seo_description
            ?: $translation->excerpt
            ?: __('site.meta_description');

        $schema = [
            '@context' =>
                'https://schema.org',
            '@type' => 'Article',
            'headline' =>
                $translation->title,
            'description' =>
                $description,
            'inLanguage' =>
                $translation->locale,
            'mainEntityOfPage' =>
                $canonical,
            'datePublished' =>
                $article->published_at
                    ?->toAtomString(),
            'dateModified' =>
                $article->updated_at
                    ?->toAtomString(),
            'author' => [
                '@type' =>
                    'Organization',
                'name' =>
                    config(
                        'seo.organization.name'
                    ),
                'url' =>
                    route(
                        'home',
                        [
                            'locale' =>
                                config(
                                    'locales.default',
                                    'pl'
                                ),
                        ]
                    ),
            ],
            'publisher' => [
                '@type' =>
                    'Organization',
                'name' =>
                    config(
                        'seo.organization.name'
                    ),
                'url' =>
                    route(
                        'home',
                        [
                            'locale' =>
                                config(
                                    'locales.default',
                                    'pl'
                                ),
                        ]
                    ),
            ],
        ];

        if ($image) {
            $schema['image'] =
                [$image];
        }

        if ($article->category?->name) {
            $schema['articleSection'] =
                $article->category->name;
        }

        return $this->pageData(
            canonical: $canonical,
            alternates: $alternates,
            type: 'article',
            image: $image,
            schemas: [$schema]
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function product(
        Product $product,
        ProductTranslation $translation
    ): array {
        $product->loadMissing([
            'translations',
            'activeVariants',
            'media',
        ]);

        $alternates =
            $product->translations
                ->filter(
                    fn ($item): bool =>
                        $item->isPubliclyReady()
                )
                ->mapWithKeys(
                    fn ($item): array => [
                        $item->locale => route(
                            'shop.show',
                            [
                                'locale' =>
                                    $item->locale,
                                'slug' =>
                                    $item->slug,
                            ]
                        ),
                    ]
                )
                ->all();

        $canonical =
            route(
                'shop.show',
                [
                    'locale' =>
                        $translation->locale,
                    'slug' =>
                        $translation->slug,
                ]
            );

        $image =
            $this->absoluteUrl(
                $product->primaryMedia()
                    ?->url()
            );

        $description =
            $translation->seo_description
            ?: $translation
                ->short_description
            ?: strip_tags(
                (string)
                $translation
                    ->description_html
            );

        $offers =
            $product->activeVariants
                ->map(
                    function ($variant) use (
                        $canonical
                    ): array {
                        return [
                            '@type' => 'Offer',
                            'url' => $canonical,
                            'priceCurrency' =>
                                $variant->currency,
                            'price' =>
                                number_format(
                                    (float)
                                    $variant
                                        ->price_gross,
                                    2,
                                    '.',
                                    ''
                                ),
                            'availability' =>
                                'https://schema.org/'
                                . (
                                    $variant->inStock()
                                        ? 'InStock'
                                        : 'OutOfStock'
                                ),
                            'sku' =>
                                $variant->sku,
                        ];
                    }
                )
                ->values()
                ->all();

        $schema = [
            '@context' =>
                'https://schema.org',
            '@type' => 'Product',
            'name' =>
                $translation->name,
            'description' =>
                Str::limit(
                    trim(
                        preg_replace(
                            '/\s+/',
                            ' ',
                            (string)
                            $description
                        ) ?? ''
                    ),
                    500,
                    ''
                ),
            'url' => $canonical,
            'inLanguage' =>
                $translation->locale,
            'offers' => $offers,
        ];

        if ($product->brand) {
            $schema['brand'] = [
                '@type' => 'Brand',
                'name' => $product->brand,
            ];
        }

        if ($image) {
            $schema['image'] =
                [$image];
        }

        return $this->pageData(
            canonical: $canonical,
            alternates: $alternates,
            type: 'product',
            image: $image,
            schemas: [$schema]
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function archive(
        ArchiveItem $archiveItem,
        ArchiveItemTranslation $translation
    ): array {
        $archiveItem->loadMissing(
            'translations'
        );

        $alternates =
            $archiveItem
                ->translations
                ->filter(
                    fn ($item): bool =>
                        $item->isPubliclyReady()
                )
                ->mapWithKeys(
                    fn ($item): array => [
                        $item->locale => route(
                            'archive.show',
                            [
                                'locale' =>
                                    $item->locale,
                                'slug' =>
                                    $item->slug,
                            ]
                        ),
                    ]
                )
                ->all();

        $canonical =
            route(
                'archive.show',
                [
                    'locale' =>
                        $translation->locale,
                    'slug' =>
                        $translation->slug,
                ]
            );

        $image =
            $this->absoluteUrl(
                $archiveItem
                    ->originalImageUrl()
            );

        $schema = [
            '@context' =>
                'https://schema.org',
            '@type' => 'CreativeWork',
            'name' =>
                $translation->title,
            'description' =>
                $translation
                    ->seo_description
                ?: $translation
                    ->description,
            'url' => $canonical,
            'image' => $image,
            'inLanguage' =>
                $translation->locale,
            'dateCreated' =>
                $archiveItem
                    ->year_from
                    ? (string)
                        $archiveItem
                            ->year_from
                    : null,
            'creator' =>
                $archiveItem->creator
                    ? [
                        '@type' =>
                            'Person',
                        'name' =>
                            $archiveItem
                                ->creator,
                    ]
                    : null,
            'publisher' =>
                $archiveItem
                    ->publisher
                    ?: null,
            'contentLocation' =>
                $archiveItem
                    ->country
                    ?: null,
        ];

        $schema = array_filter(
            $schema,
            static fn ($value): bool =>
                $value !== null
                && $value !== ''
        );

        return $this->pageData(
            canonical: $canonical,
            alternates: $alternates,
            type: 'article',
            image: $image,
            schemas: [$schema]
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function gallery(
        StereoGalleryItem $galleryItem,
        string $locale
    ): array {
        $canonical =
            route(
                'gallery.show',
                [
                    'locale' => $locale,
                    'galleryItem' =>
                        $galleryItem,
                ]
            );

        $alternates = [];

        foreach (
            array_keys(
                config(
                    'locales.supported',
                    []
                )
            ) as $alternateLocale
        ) {
            $alternates[
                $alternateLocale
            ] = route(
                'gallery.show',
                [
                    'locale' =>
                        $alternateLocale,
                    'galleryItem' =>
                        $galleryItem,
                ]
            );
        }

        $image =
            $this->absoluteUrl(
                $galleryItem
                    ->leftImageUrl()
            );

        $schema = [
            '@context' =>
                'https://schema.org',
            '@type' => 'ImageObject',
            'name' =>
                $galleryItem->title,
            'description' =>
                $galleryItem
                    ->description,
            'contentUrl' => $image,
            'url' => $canonical,
            'datePublished' =>
                $galleryItem
                    ->published_at
                    ?->toAtomString(),
            'creator' => [
                '@type' => 'Person',
                'name' =>
                    $galleryItem
                        ->author_name,
            ],
        ];

        return $this->pageData(
            canonical: $canonical,
            alternates: $alternates,
            type: 'article',
            image: $image,
            schemas: [$schema]
        );
    }

    private function isPrivateRoute(
        ?string $routeName
    ): bool {
        if (! $routeName) {
            return false;
        }

        foreach (
            config(
                'seo.noindex_routes',
                []
            ) as $pattern
        ) {
            if (
                Str::is(
                    $pattern,
                    $routeName
                )
            ) {
                return true;
            }
        }

        return false;
    }

    public function robots(
        ?string $routeName = null
    ): string {
        $routeName ??=
            Route::currentRouteName();

        foreach (
            config(
                'seo.noindex_routes',
                []
            ) as $pattern
        ) {
            if (
                $routeName
                && Str::is(
                    $pattern,
                    $routeName
                )
            ) {
                return 'noindex,nofollow';
            }
        }

        $queryKeys =
            array_keys(
                request()->query()
            );

        if ($queryKeys === []) {
            return 'index,follow';
        }

        $safeQueryKeys = ['page'];

        if (
            array_diff(
                $queryKeys,
                $safeQueryKeys
            ) === []
        ) {
            return 'index,follow';
        }

        return 'noindex,follow';
    }

    /**
     * @return array<string, string>
     */
    public function genericAlternates(
        ?string $routeName = null
    ): array {
        $routeName ??=
            Route::currentRouteName();

        if (
            ! $routeName
            || ! in_array(
                $routeName,
                config(
                    'seo.indexable_routes',
                    []
                ),
                true
            )
        ) {
            return [];
        }

        $parameters =
            Route::current()?->parameters()
            ?? [];

        unset($parameters['locale']);

        $alternates = [];

        foreach (
            array_keys(
                config(
                    'locales.supported',
                    []
                )
            ) as $locale
        ) {
            try {
                $url = route(
                    $routeName,
                    [
                        'locale' => $locale,
                        ...$parameters,
                    ]
                );
            } catch (\Throwable) {
                continue;
            }

            if (
                request()->query()
                && array_diff(
                    array_keys(
                        request()->query()
                    ),
                    ['page']
                ) === []
                && request()->integer(
                    'page'
                ) > 1
            ) {
                $url .= '?page='
                    . request()->integer(
                        'page'
                    );
            }

            $alternates[$locale] =
                $url;
        }

        return $alternates;
    }

    /**
     * @param array<string, string> $alternates
     */
    public function xDefault(
        array $alternates
    ): ?string {
        if ($alternates === []) {
            return null;
        }

        $default =
            config(
                'locales.default',
                'pl'
            );

        return $alternates[$default]
            ?? Arr::first(
                $alternates
            );
    }

    public function ogLocale(
        string $locale
    ): string {
        return config(
            'seo.og_locales.'
            . $locale,
            $locale
        );
    }

    /**
     * @return list<string>
     */
    public function ogLocaleAlternates(
        ?array $availableLocales = null
    ): array {
        $current =
            app()->getLocale();

        $availableLocales ??=
            array_keys(
                config(
                    'locales.supported',
                    []
                )
            );

        return collect(
            $availableLocales
        )
            ->reject(
                fn (string $locale): bool =>
                    $locale === $current
            )
            ->map(
                fn (string $locale): string =>
                    $this->ogLocale(
                        $locale
                    )
            )
            ->values()
            ->all();
    }

    private function currentCanonical(): string
    {
        $queryKeys =
            array_keys(
                request()->query()
            );

        if (
            $queryKeys !== []
            && array_diff(
                $queryKeys,
                ['page']
            ) === []
            && request()->integer(
                'page'
            ) > 1
        ) {
            return request()
                ->fullUrl();
        }

        return url()->current();
    }

    /**
     * @param array<string, string> $alternates
     * @param list<array<string, mixed>> $schemas
     * @return array<string, mixed>
     */
    private function pageData(
        string $canonical,
        array $alternates,
        string $type,
        ?string $image,
        array $schemas
    ): array {
        return [
            'canonical' => $canonical,
            'alternates' => $alternates,
            'x_default' =>
                $this->xDefault(
                    $alternates
                ),
            'robots' => 'index,follow',
            'type' => $type,
            'image' => $image,
            'og_locale' =>
                $this->ogLocale(
                    app()->getLocale()
                ),
            'og_locale_alternates' =>
                $this->ogLocaleAlternates(
                    array_keys(
                        $alternates
                    )
                ),
            'schemas' => [
                ...$this->baseSchemas(),
                ...$schemas,
            ],
        ];
    }

    private function absoluteUrl(
        ?string $url
    ): ?string {
        if (! filled($url)) {
            return null;
        }

        if (
            Str::startsWith(
                $url,
                ['http://', 'https://']
            )
        ) {
            return $url;
        }

        return url($url);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function baseSchemas(): array
    {
        $defaultLocale =
            config(
                'locales.default',
                'pl'
            );

        $homeUrl = route(
            'home',
            [
                'locale' =>
                    $defaultLocale,
            ]
        );

        return [
            [
                '@context' =>
                    'https://schema.org',
                '@type' =>
                    'Organization',
                'name' =>
                    config(
                        'seo.organization.name'
                    ),
                'url' => $homeUrl,
            ],
            [
                '@context' =>
                    'https://schema.org',
                '@type' => 'WebSite',
                'name' =>
                    config(
                        'seo.organization.name'
                    ),
                'url' => $homeUrl,
                'inLanguage' =>
                    array_keys(
                        config(
                            'locales.supported',
                            []
                        )
                    ),
            ],
        ];
    }
}
