<?php

namespace App\Http\Controllers;

use App\Enums\ArchiveTranslationStatus;
use App\Models\ArchiveItem;
use App\Models\ArchiveItemTranslation;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArchiveController extends Controller
{
    public function index(
        Request $request,
        string $locale
    ): View {
        $query = ArchiveItem::query()
            ->published()
            ->with([
                'translations' => function ($translationQuery) use ($locale) {
                    $translationQuery
                        ->where('locale', $locale)
                        ->whereIn(
                            'translation_status',
                            ArchiveTranslationStatus::publicValues()
                        );
                },
            ])
            ->whereHas(
                'translations',
                function ($translationQuery) use ($locale) {
                    $translationQuery
                        ->where('locale', $locale)
                        ->whereIn(
                            'translation_status',
                            ArchiveTranslationStatus::publicValues()
                        );
                }
            );

        if ($request->filled('q')) {
            $search = trim(
                $request->string('q')->toString()
            );

            $query->whereHas(
                'translations',
                function ($translationQuery) use (
                    $locale,
                    $search
                ) {
                    $translationQuery
                        ->where('locale', $locale)
                        ->where(function ($textQuery) use ($search) {
                            $textQuery
                                ->where(
                                    'title',
                                    'like',
                                    '%' . $search . '%'
                                )
                                ->orWhere(
                                    'description',
                                    'like',
                                    '%' . $search . '%'
                                )
                                ->orWhere(
                                    'historical_note',
                                    'like',
                                    '%' . $search . '%'
                                );
                        });
                }
            );
        }

        if ($request->filled('technique')) {
            $query->where(
                'technique',
                $request->string('technique')
            );
        }

        if ($request->filled('country')) {
            $query->where(
                'country',
                $request->string('country')
            );
        }

        if ($request->filled('year_from')) {
            $query->where(
                'year_from',
                '>=',
                (int) $request->integer('year_from')
            );
        }

        if ($request->filled('year_to')) {
            $query->where(
                'year_from',
                '<=',
                (int) $request->integer('year_to')
            );
        }

        $items = $query
            ->orderBy('year_from')
            ->orderBy('id')
            ->paginate(18)
            ->withQueryString();

        $countries = ArchiveItem::query()
            ->published()
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->distinct()
            ->orderBy('country')
            ->pluck('country');

        return view(
            'archive.index',
            [
                'items' => $items,
                'countries' => $countries,
            ]
        );
    }

    public function show(
        string $locale,
        string $slug,
        SeoService $seo
    ): View {
        $translation = ArchiveItemTranslation::query()
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->whereIn(
                'translation_status',
                ArchiveTranslationStatus::publicValues()
            )
            ->whereHas(
                'archiveItem',
                fn ($query) => $query->published()
            )
            ->with([
                'archiveItem.translations',
            ])
            ->firstOrFail();

        return view(
            'archive.show',
            [
                'archiveItem' =>
                    $translation->archiveItem,
                'translation' => $translation,
                'pageSeo' =>
                    $seo->archive(
                        $translation->archiveItem,
                        $translation
                    ),
            ]
        );
    }
}
