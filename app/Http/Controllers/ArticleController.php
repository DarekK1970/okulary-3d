<?php

namespace App\Http\Controllers;

use App\Enums\ArticleTranslationStatus;
use App\Models\ArticleTranslation;
use App\Services\ContextualRecommendationService;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function show(
        string $locale,
        string $slug,
        ContextualRecommendationService $recommendations
    ): View {
        $translation = ArticleTranslation::query()
            ->with([
                'article.category',
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
        ]);
    }
}
