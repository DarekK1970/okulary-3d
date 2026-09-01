<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StaticPage;
use App\Services\ArticleHtmlSanitizer;
use App\Services\StaticPageTranslationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StaticPageController extends Controller
{
    public function index(): View
    {
        $pages = StaticPage::query()
            ->with('translations')
            ->orderByRaw(
                "CASE `group` "
                . "WHEN 'content' THEN 1 "
                . "WHEN 'shop' THEN 2 "
                . "ELSE 9 END"
            )
            ->orderBy('sort_order')
            ->get();

        return view(
            'admin.static-pages.index',
            [
                'contentPages' =>
                    $pages->where(
                        'group',
                        StaticPage::GROUP_CONTENT
                    ),
                'shopPages' =>
                    $pages->where(
                        'group',
                        StaticPage::GROUP_SHOP
                    ),
                'supportedLocales' =>
                    config(
                        'locales.supported',
                        []
                    ),
            ]
        );
    }

    public function edit(
        StaticPage $staticPage
    ): View {
        $staticPage->load(
            'translations'
        );

        return view(
            'admin.static-pages.edit',
            [
                'page' =>
                    $staticPage,
                'supportedLocales' =>
                    config(
                        'locales.supported',
                        []
                    ),
            ]
        );
    }

    public function update(
        Request $request,
        StaticPage $staticPage,
        ArticleHtmlSanitizer $sanitizer
    ): RedirectResponse {
        $validated = $request->validate([
            'translations' => [
                'required',
                'array',
            ],
            'translations.*.title' => [
                'nullable',
                'string',
                'max:220',
            ],
            'translations.*.body_html' => [
                'nullable',
                'string',
            ],
            'translations.*.seo_title' => [
                'nullable',
                'string',
                'max:180',
            ],
            'translations.*.seo_description' => [
                'nullable',
                'string',
                'max:320',
            ],
        ]);

        $translations =
            $validated['translations'];

        $sourceLocale =
            $staticPage
                ->source_locale;

        $sourceTitle = trim(
            (string) (
                $translations[
                    $sourceLocale
                ]['title'] ?? ''
            )
        );

        if ($sourceTitle === '') {
            throw ValidationException
                ::withMessages([
                    "translations."
                    . $sourceLocale
                    . ".title" =>
                        __(
                            'static_pages.errors.source_title_required'
                        ),
                ]);
        }

        foreach (
            config(
                'locales.supported',
                []
            )
            as $locale => $language
        ) {
            $data =
                $translations[
                    $locale
                ] ?? [];

            $title = trim(
                (string)
                ($data['title']
                    ?? '')
            );

            $body = trim(
                (string)
                ($data['body_html']
                    ?? '')
            );

            $seoTitle = trim(
                (string)
                ($data['seo_title']
                    ?? '')
            );

            $seoDescription = trim(
                (string)
                ($data[
                    'seo_description'
                ] ?? '')
            );

            $hasAnyValue =
                $title !== ''
                || $body !== ''
                || $seoTitle !== ''
                || $seoDescription !== '';

            if (
                ! $hasAnyValue
                && $locale
                    !== $sourceLocale
            ) {
                continue;
            }

            if (
                $title === ''
                && $locale
                    !== $sourceLocale
            ) {
                throw ValidationException
                    ::withMessages([
                        "translations."
                        . $locale
                        . ".title" =>
                            __(
                                'static_pages.errors.translation_title_required'
                            ),
                    ]);
            }

            $staticPage
                ->translations()
                ->updateOrCreate(
                    [
                        'locale' =>
                            $locale,
                    ],
                    [
                        'title' =>
                            $title,
                        'body_html' =>
                            $sanitizer
                                ->sanitize(
                                    $body
                                ),
                        'seo_title' =>
                            $seoTitle
                                ?: null,
                        'seo_description' =>
                            $seoDescription
                                ?: null,
                        'translation_status' =>
                            $locale
                                === $sourceLocale
                                ? 'source'
                                : 'ready',
                    ]
                );
        }

        return redirect()
            ->route(
                'admin.static-pages.edit',
                $staticPage
            )
            ->with(
                'status',
                __(
                    'static_pages.messages.saved'
                )
            );
    }

    public function translate(
        Request $request,
        StaticPage $staticPage,
        StaticPageTranslationService $translator
    ): RedirectResponse {
        try {
            $locales =
                $translator
                    ->translateMissing(
                        $staticPage,
                        $request->user()
                    );
        } catch (\Throwable $exception) {
            return back()->withErrors([
                'static_page_translation' =>
                    $exception
                        ->getMessage(),
            ]);
        }

        if ($locales === []) {
            return back()->with(
                'status',
                __(
                    'static_pages.messages.no_missing_translations'
                )
            );
        }

        return back()->with(
            'status',
            __(
                'static_pages.messages.translated',
                [
                    'locales' =>
                        strtoupper(
                            implode(
                                ', ',
                                $locales
                            )
                        ),
                ]
            )
        );
    }
}
