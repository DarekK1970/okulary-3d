<?php

namespace App\Services;

use App\Models\AiTranslationRun;
use App\Models\StaticPage;
use App\Models\StaticPageTranslation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StaticPageTranslationService
{
    public const CONTENT_TYPE =
        'static_page';

    public function __construct(
        private readonly AiTranslationProviderService $provider,
        private readonly AiTranslationSettingsService $settings,
        private readonly ArticleHtmlSanitizer $sanitizer
    ) {
    }

    /**
     * Create every missing/incomplete language version,
     * without overwriting manually completed translations.
     *
     * @return list<string>
     */
    public function translateMissing(
        StaticPage $page,
        User $user
    ): array {
        $page->loadMissing(
            'translations'
        );

        $source = $page
            ->sourceTranslation();

        if (! $source) {
            throw new RuntimeException(
                __(
                    'static_pages.errors.source_missing'
                )
            );
        }

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

        $translatedLocales = [];

        foreach (
            $supportedLocales
            as $targetLocale
        ) {
            if (
                $targetLocale
                === $page->source_locale
            ) {
                continue;
            }

            $existing = $page
                ->translation(
                    $targetLocale
                );

            if (
                $existing
                && $existing
                    ->isComplete()
            ) {
                continue;
            }

            $this->translateOne(
                $page,
                $source,
                $existing,
                $targetLocale,
                $user
            );

            $translatedLocales[] =
                $targetLocale;

            $page->unsetRelation(
                'translations'
            );

            $page->load(
                'translations'
            );
        }

        return $translatedLocales;
    }

    private function translateOne(
        StaticPage $page,
        StaticPageTranslation $source,
        ?StaticPageTranslation $existing,
        string $targetLocale,
        User $user
    ): void {
        $fields = [
            'title' =>
                (string)
                $source->title,

            'body_html' =>
                (string)
                ($source->body_html
                    ?? ''),

            'seo_title' =>
                (string)
                ($source->seo_title
                    ?? ''),

            'seo_description' =>
                (string)
                ($source->seo_description
                    ?? ''),
        ];

        $run = AiTranslationRun::create([
            'content_type' =>
                self::CONTENT_TYPE,
            'content_id' =>
                $page->id,
            'source_locale' =>
                $page->source_locale,
            'target_locale' =>
                $targetLocale,
            'provider' =>
                $this->settings
                    ->provider(),
            'model' =>
                $this->settings
                    ->model(),
            'status' => 'started',
            'request_chars' =>
                mb_strlen(
                    json_encode(
                        $fields,
                        JSON_UNESCAPED_UNICODE
                        | JSON_UNESCAPED_SLASHES
                    ) ?: ''
                ),
            'initiated_by' =>
                $user->id,
        ]);

        try {
            $result =
                $this->provider
                    ->translate(
                        $fields,
                        $page
                            ->source_locale,
                        $targetLocale,
                        self::CONTENT_TYPE
                    );

            $generated =
                $result['fields'];

            DB::transaction(
                function () use (
                    $page,
                    $existing,
                    $targetLocale,
                    $generated
                ): void {
                    $values = [
                        'title' =>
                            $this
                                ->preserveOrGenerated(
                                    $existing?->title,
                                    $generated[
                                        'title'
                                    ] ?? ''
                                ),

                        'body_html' =>
                            $this->sanitizer
                                ->sanitize(
                                    $this
                                        ->preserveOrGenerated(
                                            $existing
                                                ?->body_html,
                                            $generated[
                                                'body_html'
                                            ] ?? ''
                                        )
                                ),

                        'seo_title' =>
                            $this
                                ->preserveOrGenerated(
                                    $existing
                                        ?->seo_title,
                                    $generated[
                                        'seo_title'
                                    ] ?? ''
                                ),

                        'seo_description' =>
                            $this
                                ->preserveOrGenerated(
                                    $existing
                                        ?->seo_description,
                                    $generated[
                                        'seo_description'
                                    ] ?? ''
                                ),

                        'translation_status' =>
                            'ready',
                    ];

                    $page
                        ->translations()
                        ->updateOrCreate(
                            [
                                'locale' =>
                                    $targetLocale,
                            ],
                            $values
                        );
                }
            );

            $run->update([
                'provider' =>
                    $result['provider'],
                'model' =>
                    $result['model'],
                'status' =>
                    'success',
                'input_tokens' =>
                    $result[
                        'input_tokens'
                    ],
                'output_tokens' =>
                    $result[
                        'output_tokens'
                    ],
                'total_tokens' =>
                    $result[
                        'total_tokens'
                    ],
                'response_chars' =>
                    mb_strlen(
                        $result[
                            'raw_text'
                        ]
                    ),
                'error_message' =>
                    null,
            ]);
        } catch (\Throwable $exception) {
            $run->update([
                'status' => 'failed',
                'error_message' =>
                    mb_substr(
                        $exception
                            ->getMessage(),
                        0,
                        2000
                    ),
            ]);

            throw $exception;
        }
    }

    private function preserveOrGenerated(
        ?string $existing,
        string $generated
    ): string {
        return filled($existing)
            ? (string) $existing
            : $generated;
    }
}
