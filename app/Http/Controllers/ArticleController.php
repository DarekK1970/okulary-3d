<?php

namespace App\Http\Controllers;

use App\Enums\ArticleTranslationStatus;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\ArticleTranslation;
use App\Services\ContextualRecommendationService;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function index(
        Request $request,
        string $locale
    ): View {
        $publicStatuses =
            ArticleTranslationStatus
                ::publicValues();

        $selectedCategory = null;

        if (
            $request->filled(
                'category'
            )
        ) {
            $selectedCategory =
                ArticleCategory::query()
                    ->where(
                        'slug',
                        trim(
                            $request
                                ->string(
                                    'category'
                                )
                                ->toString()
                        )
                    )
                    ->where(
                        'is_active',
                        true
                    )
                    ->first();
        }

        $articles = Article::query()
            ->published()
            ->whereHas(
                'translations',
                function (
                    $query
                ) use (
                    $locale,
                    $publicStatuses
                ): void {
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
            ->when(
                $selectedCategory,
                fn ($query) =>
                    $query->where(
                        'category_id',
                        $selectedCategory
                            ->id
                    )
            )
            ->when(
                $request->filled('q'),
                function (
                    $query
                ) use (
                    $request,
                    $locale,
                    $publicStatuses
                ): void {
                    $search = trim(
                        $request
                            ->string('q')
                            ->toString()
                    );

                    if ($search === '') {
                        return;
                    }

                    $query->whereHas(
                        'translations',
                        function (
                            $translationQuery
                        ) use (
                            $locale,
                            $publicStatuses,
                            $search
                        ): void {
                            $translationQuery
                                ->where(
                                    'locale',
                                    $locale
                                )
                                ->whereIn(
                                    'translation_status',
                                    $publicStatuses
                                )
                                ->where(
                                    function (
                                        $textQuery
                                    ) use (
                                        $search
                                    ): void {
                                        $textQuery
                                            ->where(
                                                'title',
                                                'like',
                                                '%'
                                                . $search
                                                . '%'
                                            )
                                            ->orWhere(
                                                'excerpt',
                                                'like',
                                                '%'
                                                . $search
                                                . '%'
                                            )
                                            ->orWhere(
                                                'body_html',
                                                'like',
                                                '%'
                                                . $search
                                                . '%'
                                            );
                                    }
                                );
                        }
                    );
                }
            )
            ->with([
                'category',
                'heroMedia',
                'translations' =>
                    function (
                        $query
                    ) use (
                        $locale,
                        $publicStatuses
                    ): void {
                        $query
                            ->where(
                                'locale',
                                $locale
                            )
                            ->whereIn(
                                'translation_status',
                                $publicStatuses
                            );
                    },
            ])
            ->orderByDesc(
                'published_at'
            )
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        $categories =
            ArticleCategory::query()
                ->where(
                    'is_active',
                    true
                )
                ->whereHas(
                    'articles',
                    function (
                        $articleQuery
                    ) use (
                        $locale,
                        $publicStatuses
                    ): void {
                        $articleQuery
                            ->published()
                            ->whereHas(
                                'translations',
                                function (
                                    $translationQuery
                                ) use (
                                    $locale,
                                    $publicStatuses
                                ): void {
                                    $translationQuery
                                        ->where(
                                            'locale',
                                            $locale
                                        )
                                        ->whereIn(
                                            'translation_status',
                                            $publicStatuses
                                        );
                                }
                            );
                    }
                )
                ->orderBy(
                    'sort_order'
                )
                ->orderBy('name')
                ->get();

        return view(
            'articles.index',
            [
                'articles' =>
                    $articles,
                'categories' =>
                    $categories,
                'selectedCategory' =>
                    $selectedCategory,
            ]
        );
    }

    public function show(
        string $locale,
        string $slug,
        ContextualRecommendationService $recommendations,
        SeoService $seo
    ): View {
        $translation = ArticleTranslation::query()
            ->with([
                'article.category',
                'article.heroMedia',
                'article.contextRecommendations.product.translations',
                'article.contextRecommendations.product.activeVariants',
                'article.contextRecommendations.product.media',
                'article.contextRecommendations.product.category.translations',
                'article.translations' => fn ($query) => $query
                    ->whereIn(
                        'translation_status',
                        ArticleTranslationStatus::publicValues()
                    ),
            ])
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->whereIn(
                'translation_status',
                ArticleTranslationStatus::publicValues()
            )
            ->whereHas(
                'article',
                fn ($query) => $query->published()
            )
            ->firstOrFail();

        $article = $translation->article;

        return view('articles.show', [
            'translation' => $translation,
            'article' => $article,
            'contextualRecommendations' =>
                $recommendations->resolve(
                    $article,
                    $locale
                ),
            'pageSeo' =>
                $seo->article(
                    $article,
                    $translation
                ),
        ]);
    }
}
