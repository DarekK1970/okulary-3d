<?php

namespace App\Http\Controllers;

use App\Enums\ArticleTranslationStatus;
use App\Models\ArticleTranslation;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function show(string $locale, string $slug): View
    {
        $translation = ArticleTranslation::query()
            ->with([
                'article.category',
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

        return view('articles.show', [
            'translation' => $translation,
            'article' => $translation->article,
        ]);
    }
}
