<?php

namespace App\Services;

use App\Enums\CatalogTranslationStatus;
use App\Enums\ContextRecommendationType;
use App\Enums\ProductStatus;
use App\Models\Article;
use App\Models\ArticleContextRecommendation;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContextualRecommendationService
{
    private const MAX_TOOLS = 2;
    private const MAX_PRODUCTS = 4;

    /**
     * @return array<string, array{
     *     route: string,
     *     keywords: list<string>
     * }>
     */
    public function toolDefinitions(): array
    {
        return [
            'anaglyph' => [
                'route' => 'lab.anaglyph',
                'keywords' => [
                    'anaglif',
                    'anaglyph',
                    'red cyan',
                    'red-cyan',
                    'czerwono cyjan',
                    'czerwono-cyjan',
                ],
            ],
            'stereo_alignment' => [
                'route' => 'lab.stereo-alignment',
                'keywords' => [
                    'stereo pair',
                    'stereopara',
                    'para stereo',
                    'cross eye',
                    'cross-eye',
                    'parallel',
                    'alignment',
                    'wyrównanie stereo',
                    'fotografia stereoskopowa',
                    'stereoscopic photography',
                ],
            ],
            'lenticular' => [
                'route' => 'lab.lenticular',
                'keywords' => [
                    'lenticular',
                    'lentikular',
                    'lentikularny',
                    'lentikularna',
                    'folia soczewkowa',
                    'soczewkowa',
                    'lpi',
                    'interlacer',
                    'przeplatanie obrazu',
                ],
            ],
            'mpo' => [
                'route' => 'lab.mpo',
                'keywords' => [
                    'mpo',
                    'multi picture object',
                    '3d camera',
                    'aparat 3d',
                    'kamera stereoskopowa',
                ],
            ],
            'wigglegram' => [
                'route' => 'lab.wigglegram',
                'keywords' => [
                    'wiggle',
                    'wigglegram',
                    'wiggle 3d',
                    'animacja stereo',
                    'animated stereo',
                ],
            ],
        ];
    }

    /**
     * @return array<string, array{
     *     key: string,
     *     route: string,
     *     title: string,
     *     description: string
     * }>
     */
    public function toolCatalog(
        ?string $locale = null
    ): array {
        $locale ??= app()->getLocale();

        return collect(
            $this->toolDefinitions()
        )
            ->mapWithKeys(
                function (
                    array $definition,
                    string $key
                ) use ($locale): array {
                    return [
                        $key => [
                            'key' => $key,
                            'route' =>
                                $definition['route'],
                            'title' => __(
                                'recommendations.tools.'
                                . $key
                                . '.title',
                                [],
                                $locale
                            ),
                            'description' => __(
                                'recommendations.tools.'
                                . $key
                                . '.description',
                                [],
                                $locale
                            ),
                        ],
                    ];
                }
            )
            ->all();
    }

    /**
     * @return array{
     *     tools: list<string>,
     *     products: list<int>
     * }
     */
    public function manualSelection(
        Article $article
    ): array {
        $article->loadMissing(
            'contextRecommendations'
        );

        $tools = $article
            ->contextRecommendations
            ->filter(
                fn (
                    ArticleContextRecommendation $item
                ): bool =>
                    $item->is_active
                    && $item->target_type
                        === ContextRecommendationType::Tool
                    && filled($item->tool_key)
            )
            ->pluck('tool_key')
            ->values()
            ->all();

        $products = $article
            ->contextRecommendations
            ->filter(
                fn (
                    ArticleContextRecommendation $item
                ): bool =>
                    $item->is_active
                    && $item->target_type
                        === ContextRecommendationType::Product
                    && filled($item->product_id)
            )
            ->pluck('product_id')
            ->map(
                static fn ($id): int =>
                    (int) $id
            )
            ->values()
            ->all();

        return [
            'tools' => $tools,
            'products' => $products,
        ];
    }

    /**
     * @param list<string> $toolKeys
     * @param list<int|string> $productIds
     */
    public function syncManual(
        Article $article,
        array $toolKeys,
        array $productIds,
        ?int $userId
    ): void {
        $allowedTools = array_keys(
            $this->toolDefinitions()
        );

        $toolKeys = collect($toolKeys)
            ->map(
                static fn ($key): string =>
                    trim((string) $key)
            )
            ->filter(
                static fn (string $key): bool =>
                    in_array(
                        $key,
                        $allowedTools,
                        true
                    )
            )
            ->unique()
            ->take(self::MAX_TOOLS)
            ->values();

        $productIds = collect(
            $productIds
        )
            ->map(
                static fn ($id): int =>
                    (int) $id
            )
            ->filter(
                static fn (int $id): bool =>
                    $id > 0
            )
            ->unique()
            ->take(self::MAX_PRODUCTS)
            ->values();

        DB::transaction(
            function () use (
                $article,
                $toolKeys,
                $productIds,
                $userId
            ): void {
                $article
                    ->contextRecommendations()
                    ->delete();

                $position = 1;

                foreach (
                    $toolKeys as $toolKey
                ) {
                    $article
                        ->contextRecommendations()
                        ->create([
                            'target_type' =>
                                ContextRecommendationType::Tool,
                            'tool_key' =>
                                $toolKey,
                            'product_id' =>
                                null,
                            'position' =>
                                $position++,
                            'is_active' =>
                                true,
                            'created_by' =>
                                $userId,
                        ]);
                }

                foreach (
                    $productIds as $productId
                ) {
                    $article
                        ->contextRecommendations()
                        ->create([
                            'target_type' =>
                                ContextRecommendationType::Product,
                            'tool_key' =>
                                null,
                            'product_id' =>
                                $productId,
                            'position' =>
                                $position++,
                            'is_active' =>
                                true,
                            'created_by' =>
                                $userId,
                        ]);
                }
            }
        );

        $article->unsetRelation(
            'contextRecommendations'
        );
    }

    /**
     * @return array{
     *     tools: list<array{
     *         key: string,
     *         route: string,
     *         title: string,
     *         description: string,
     *         automatic: bool
     *     }>,
     *     products: list<array{
     *         product: Product,
     *         translation: mixed,
     *         media: mixed,
     *         price: string|null,
     *         currency: string|null,
     *         automatic: bool
     *     }>
     * }
     */
    public function resolve(
        Article $article,
        string $locale
    ): array {
        $article->loadMissing([
            'translations',
            'contextRecommendations.product.translations',
            'contextRecommendations.product.activeVariants',
            'contextRecommendations.product.media',
            'contextRecommendations.product.category.translations',
        ]);

        $catalog =
            $this->toolCatalog($locale);

        $manualTools = [];
        $manualProducts = [];

        foreach (
            $article->contextRecommendations
                ->where('is_active', true)
            as $recommendation
        ) {
            if (
                $recommendation->target_type
                    === ContextRecommendationType::Tool
                && isset(
                    $catalog[
                        $recommendation->tool_key
                    ]
                )
            ) {
                $manualTools[] = [
                    ...$catalog[
                        $recommendation->tool_key
                    ],
                    'automatic' => false,
                ];

                continue;
            }

            if (
                $recommendation->target_type
                    === ContextRecommendationType::Product
                && $recommendation->product
            ) {
                $productCard =
                    $this->publicProductCard(
                        $recommendation->product,
                        $locale,
                        false
                    );

                if ($productCard) {
                    $manualProducts[] =
                        $productCard;
                }
            }
        }

        $tools = collect(
            $manualTools
        )
            ->unique('key')
            ->take(self::MAX_TOOLS)
            ->values();

        $products = collect(
            $manualProducts
        )
            ->unique(
                fn (array $card): int =>
                    $card['product']->id
            )
            ->take(self::MAX_PRODUCTS)
            ->values();

        if ($article->recommendation_auto) {
            $context =
                $this->articleContext(
                    $article,
                    $locale
                );

            if (
                $tools->count()
                < self::MAX_TOOLS
            ) {
                $manualToolKeys =
                    $tools->pluck('key')
                        ->all();

                foreach (
                    $this->automaticToolKeys(
                        $context
                    ) as $key
                ) {
                    if (
                        in_array(
                            $key,
                            $manualToolKeys,
                            true
                        )
                    ) {
                        continue;
                    }

                    $tools->push([
                        ...$catalog[$key],
                        'automatic' => true,
                    ]);

                    $manualToolKeys[] =
                        $key;

                    if (
                        $tools->count()
                        >= self::MAX_TOOLS
                    ) {
                        break;
                    }
                }
            }

            if (
                $products->count()
                < self::MAX_PRODUCTS
            ) {
                $excludedProductIds =
                    $products
                        ->map(
                            fn (array $card): int =>
                                $card[
                                    'product'
                                ]->id
                        )
                        ->all();

                $autoProducts =
                    $this->automaticProducts(
                        $context,
                        $locale,
                        $excludedProductIds,
                        self::MAX_PRODUCTS
                            - $products->count()
                    );

                $products = $products
                    ->concat(
                        $autoProducts
                    )
                    ->take(
                        self::MAX_PRODUCTS
                    )
                    ->values();
            }
        }

        return [
            'tools' => $tools->all(),
            'products' =>
                $products->all(),
        ];
    }

    /**
     * @return list<string>
     */
    private function automaticToolKeys(
        string $context
    ): array {
        $scores = [];

        foreach (
            $this->toolDefinitions()
            as $key => $definition
        ) {
            $score = 0;

            foreach (
                $definition['keywords']
                as $keyword
            ) {
                $normalizedKeyword =
                    $this->normalize(
                        $keyword
                    );

                if (
                    $normalizedKeyword !== ''
                    && str_contains(
                        $context,
                        $normalizedKeyword
                    )
                ) {
                    $score +=
                        str_contains(
                            $normalizedKeyword,
                            ' '
                        )
                            ? 3
                            : 2;
                }
            }

            if ($score > 0) {
                $scores[$key] = $score;
            }
        }

        arsort($scores);

        return array_keys($scores);
    }

    /**
     * @param list<int> $excludedIds
     * @return list<array<string, mixed>>
     */
    private function automaticProducts(
        string $context,
        string $locale,
        array $excludedIds,
        int $limit
    ): array {
        if ($limit < 1) {
            return [];
        }

        $publicStatuses =
            CatalogTranslationStatus::publicValues();

        $products = Product::query()
            ->active()
            ->whereNotIn(
                'id',
                $excludedIds
            )
            ->whereHas(
                'activeVariants'
            )
            ->whereHas(
                'translations',
                function ($query) use (
                    $locale,
                    $publicStatuses
                ) {
                    $query
                        ->where(
                            'locale',
                            $locale
                        )
                        ->whereIn(
                            'translation_status',
                            $publicStatuses
                        );
                }
            )
            ->with([
                'translations' =>
                    fn ($query) => $query
                        ->where(
                            'locale',
                            $locale
                        )
                        ->whereIn(
                            'translation_status',
                            $publicStatuses
                        ),
                'activeVariants',
                'media',
                'category.translations' =>
                    fn ($query) => $query
                        ->where(
                            'locale',
                            $locale
                        )
                        ->whereIn(
                            'translation_status',
                            $publicStatuses
                        ),
            ])
            ->limit(80)
            ->get();

        $contextTokens =
            $this->tokens($context);

        return $products
            ->map(
                function (
                    Product $product
                ) use (
                    $locale,
                    $context,
                    $contextTokens
                ): ?array {
                    $translation =
                        $product->publicTranslation(
                            $locale
                        );

                    if (! $translation) {
                        return null;
                    }

                    $category =
                        $product->category
                            ?->publicTranslation(
                                $locale
                            );

                    $productText =
                        $this->normalize(
                            implode(' ', [
                                $translation->name,
                                $translation
                                    ->short_description,
                                strip_tags(
                                    (string)
                                    $translation
                                        ->description_html
                                ),
                                $category?->name,
                                $product->brand,
                            ])
                        );

                    $productTokens =
                        $this->tokens(
                            $productText
                        );

                    $shared =
                        count(
                            array_intersect(
                                $contextTokens,
                                $productTokens
                            )
                        );

                    $topicBonus = 0;

                    foreach (
                        $this->toolDefinitions()
                        as $definition
                    ) {
                        foreach (
                            $definition['keywords']
                            as $keyword
                        ) {
                            $keyword =
                                $this->normalize(
                                    $keyword
                                );

                            if (
                                $keyword !== ''
                                && str_contains(
                                    $context,
                                    $keyword
                                )
                                && str_contains(
                                    $productText,
                                    $keyword
                                )
                            ) {
                                $topicBonus += 4;
                            }
                        }
                    }

                    $score =
                        $shared
                        + $topicBonus
                        + (
                            $product->is_featured
                                ? 1
                                : 0
                        );

                    if ($score < 2) {
                        return null;
                    }

                    $card =
                        $this->publicProductCard(
                            $product,
                            $locale,
                            true
                        );

                    if (! $card) {
                        return null;
                    }

                    return [
                        ...$card,
                        '_score' => $score,
                    ];
                }
            )
            ->filter(
                static fn ($item): bool =>
                    is_array($item)
            )
            ->sortByDesc('_score')
            ->take($limit)
            ->map(
                function (array $item): array {
                    unset($item['_score']);

                    return $item;
                }
            )
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function publicProductCard(
        Product $product,
        string $locale,
        bool $automatic
    ): ?array {
        if (
            $product->status
                !== ProductStatus::Active
        ) {
            return null;
        }

        $translation =
            $product->publicTranslation(
                $locale
            );

        if (
            ! $translation
            || $product
                ->activeVariants
                ->isEmpty()
        ) {
            return null;
        }

        $variant =
            $product
                ->activeVariants
                ->sortBy(
                    fn ($item) =>
                        (float)
                        $item->price_gross
                )
                ->first();

        return [
            'product' => $product,
            'translation' =>
                $translation,
            'media' =>
                $product->primaryMedia(),
            'price' =>
                $variant
                    ? number_format(
                        (float)
                        $variant->price_gross,
                        2,
                        ',',
                        ' '
                    )
                    : null,
            'currency' =>
                $variant?->currency,
            'automatic' =>
                $automatic,
        ];
    }

    private function articleContext(
        Article $article,
        string $locale
    ): string {
        $translation =
            $article->publicTranslation(
                $locale
            )
            ?? $article->translation(
                $locale
            )
            ?? $article->sourceTranslation();

        if (! $translation) {
            return '';
        }

        return $this->normalize(
            implode(' ', [
                $translation->title,
                $translation->excerpt,
                strip_tags(
                    (string)
                    $translation->body_html
                ),
                $article->category?->name,
            ])
        );
    }

    /**
     * @return list<string>
     */
    private function tokens(
        string $text
    ): array {
        $stopwords = [
            'about',
            'after',
            'also',
            'and',
            'are',
            'article',
            'been',
            'being',
            'czyli',
            'dla',
            'from',
            'have',
            'jest',
            'ktore',
            'ktory',
            'moze',
            'oraz',
            'przez',
            'that',
            'the',
            'this',
            'with',
            'which',
            'will',
            'www',
            'sie',
            'oraz',
            'jego',
            'jej',
            'jako',
            'tych',
            'tego',
            'their',
            'into',
            'more',
            'than',
        ];

        preg_match_all(
            '/[a-z0-9]{4,}/',
            $this->normalize($text),
            $matches
        );

        return collect(
            $matches[0] ?? []
        )
            ->reject(
                static fn (string $token): bool =>
                    in_array(
                        $token,
                        $stopwords,
                        true
                    )
            )
            ->unique()
            ->values()
            ->all();
    }

    private function normalize(
        mixed $value
    ): string {
        return trim(
            preg_replace(
                '/\s+/',
                ' ',
                Str::lower(
                    Str::ascii(
                        (string) $value
                    )
                )
            ) ?? ''
        );
    }
}
