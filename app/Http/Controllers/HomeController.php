<?php

namespace App\Http\Controllers;

use App\Enums\ArchiveTranslationStatus;
use App\Enums\ArticlePortalSection;
use App\Enums\ArticleTranslationStatus;
use App\Models\ArchiveItem;
use App\Models\Article;
use App\Models\StereoGalleryItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(string $locale): View
    {
        return view('home', [
            'latestArticles' => $this->latestArticles($locale),
            'techniqueArticles' => $this->articlesForSection(
                $locale,
                ArticlePortalSection::Techniques,
                3
            ),
            'archiveItems' => $this->latestArchiveItems($locale),
            'homeGalleryItems' => $this->homeGalleryItems(),
        ]);
    }

    /**
     * @return Collection<int, Article>
     */
    private function latestArticles(string $locale): Collection
    {
        if (! $this->articleTablesReady()) {
            return collect();
        }

        return $this->articleQueryForLocale($locale)
            ->whereDoesntHave(
                'category',
                fn ($query) => $query->where(
                    'portal_section',
                    ArticlePortalSection::HistoryCuriosities->value
                )
            )
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(3)
            ->get();
    }

    /**
     * @return Collection<int, Article>
     */
    private function articlesForSection(
        string $locale,
        ArticlePortalSection $section,
        int $limit
    ): Collection {
        if (! $this->articleTablesReady()) {
            return collect();
        }

        return $this->articleQueryForLocale($locale)
            ->whereHas(
                'category',
                fn ($query) => $query
                    ->where('is_active', true)
                    ->where('portal_section', $section->value)
            )
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    private function articleQueryForLocale(string $locale)
    {
        $publicStatuses = ArticleTranslationStatus::publicValues();

        return Article::query()
            ->published()
            ->whereHas(
                'translations',
                function ($query) use ($locale, $publicStatuses): void {
                    $query
                        ->where('locale', $locale)
                        ->whereIn('translation_status', $publicStatuses);
                }
            )
            ->with([
                'category',
                'heroMedia',
                'translations' => function ($query) use (
                    $locale,
                    $publicStatuses
                ): void {
                    $query
                        ->where('locale', $locale)
                        ->whereIn('translation_status', $publicStatuses);
                },
            ]);
    }

    private function articleTablesReady(): bool
    {
        return Schema::hasTable('articles')
            && Schema::hasTable('article_translations')
            && Schema::hasTable('article_categories')
            && Schema::hasColumn('article_categories', 'portal_section');
    }

    /**
     * @return Collection<int, ArchiveItem>
     */
    private function latestArchiveItems(string $locale): Collection
    {
        if (
            ! Schema::hasTable('archive_items')
            || ! Schema::hasTable('archive_item_translations')
        ) {
            return collect();
        }

        $publicStatuses = ArchiveTranslationStatus::publicValues();

        return ArchiveItem::query()
            ->published()
            ->where('published_at', '<=', now())
            ->whereNotNull('original_image_path')
            ->whereHas(
                'translations',
                function ($query) use ($locale, $publicStatuses): void {
                    $query
                        ->where('locale', $locale)
                        ->whereIn('translation_status', $publicStatuses);
                }
            )
            ->with([
                'translations' => function ($query) use (
                    $locale,
                    $publicStatuses
                ): void {
                    $query
                        ->where('locale', $locale)
                        ->whereIn('translation_status', $publicStatuses);
                },
            ])
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get();
    }

    /**
     * @return Collection<int, StereoGalleryItem>
     */
    private function homeGalleryItems(): Collection
    {
        if (! Schema::hasTable('stereo_gallery_items')) {
            return collect();
        }

        $query = StereoGalleryItem::query()
            ->published()
            ->inRandomOrder();

        if (Schema::hasTable('stereo_gallery_ratings')) {
            $query
                ->withCount('ratings')
                ->withAvg('ratings', 'rating');
        }

        if (Auth::check() && Schema::hasTable('stereo_gallery_ratings')) {
            $query->with([
                'ratings' => fn ($ratings) => $ratings
                    ->where('user_id', Auth::id()),
            ]);
        }

        return $query
            ->limit(6)
            ->get();
    }
}
