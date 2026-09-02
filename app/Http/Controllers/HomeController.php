<?php

namespace App\Http\Controllers;

use App\Enums\ArticleTranslationStatus;
use App\Models\Article;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(
        string $locale
    ): View {
        $latestArticles = $this
            ->latestArticles(
                $locale
            );

        return view(
            'home',
            [
                'latestArticles' =>
                    $latestArticles,
            ]
        );
    }

    /**
     * @return Collection<int, Article>
     */
    private function latestArticles(
        string $locale
    ): Collection {
        /*
         * Keeps the homepage usable during a fresh install before
         * migrations, and keeps old layout-only tests independent
         * from the content database.
         */
        if (
            ! Schema::hasTable(
                'articles'
            )
            || ! Schema::hasTable(
                'article_translations'
            )
        ) {
            return collect();
        }

        $publicStatuses =
            ArticleTranslationStatus
                ::publicValues();

        return Article::query()
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
            ->limit(3)
            ->get();
    }
}
