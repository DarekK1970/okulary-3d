<?php

namespace App\Http\Controllers;

use App\Models\StaticPage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StaticPageController extends Controller
{
    public function show(
        string $locale,
        string $key
    ): View {
        $page = StaticPage::query()
            ->with('translations')
            ->where(
                'key',
                $key
            )
            ->where(
                'is_active',
                true
            )
            ->firstOrFail();

        $translation =
            $page
                ->translationOrSource(
                    $locale
                );

        abort_unless(
            $translation,
            404
        );

        $supportedLocales =
            array_keys(
                config(
                    'locales.supported',
                    [
                        'pl' => [],
                        'en' => [],
                    ]
                )
            );

        $alternates = [];

        foreach (
            $supportedLocales
            as $alternateLocale
        ) {
            $alternates[
                $alternateLocale
            ] = route(
                'static-pages.show',
                [
                    'locale' =>
                        $alternateLocale,
                    'key' =>
                        $page->key,
                ]
            );
        }

        $canonical = route(
            'static-pages.show',
            [
                'locale' => $locale,
                'key' => $page->key,
            ]
        );

        $description =
            $translation
                ->seo_description
            ?: Str::limit(
                trim(
                    preg_replace(
                        '/\s+/',
                        ' ',
                        strip_tags(
                            (string)
                            $translation
                                ->body_html
                        )
                    ) ?? ''
                ),
                180,
                ''
            )
            ?: __(
                'site.meta_description'
            );

        $defaultLocale =
            config(
                'locales.default',
                'pl'
            );

        $pageSeo = [
            'canonical' =>
                $canonical,
            'alternates' =>
                $alternates,
            'x_default' =>
                $alternates[
                    $defaultLocale
                ] ?? $canonical,
            'robots' =>
                'index,follow',
            'type' => 'website',
            'image' => null,
            'og_locale' =>
                $this->ogLocale(
                    $locale
                ),
            'og_locale_alternates' =>
                collect(
                    $supportedLocales
                )
                    ->reject(
                        fn (string $item) =>
                            $item
                            === $locale
                    )
                    ->map(
                        fn (string $item) =>
                            $this
                                ->ogLocale(
                                    $item
                                )
                    )
                    ->values()
                    ->all(),
            'schemas' => [
                [
                    '@context' =>
                        'https://schema.org',
                    '@type' =>
                        'WebPage',
                    'name' =>
                        $translation
                            ->title,
                    'description' =>
                        $description,
                    'url' =>
                        $canonical,
                    'inLanguage' =>
                        $locale,
                ],
            ],
        ];

        return view(
            'static-pages.show',
            [
                'page' => $page,
                'translation' =>
                    $translation,
                'pageSeo' =>
                    $pageSeo,
            ]
        );
    }

    private function ogLocale(
        string $locale
    ): string {
        return match ($locale) {
            'pl' => 'pl_PL',
            'en' => 'en_US',
            default =>
                str_replace(
                    '-',
                    '_',
                    $locale
                ),
        };
    }
}
