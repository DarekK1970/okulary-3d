<?php

namespace App\Services;

use App\Models\AiTranslationRun;
use App\Models\Article;
use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class ArticleAiImageService
{
    public const CONTENT_TYPE =
        'article_image';

    private const DEFAULT_MODEL =
        'gpt-image-2';

    private const SIZE =
        '1536x1024';

    private const QUALITY =
        'medium';

    public function __construct(
        private readonly AiTranslationSettingsService $settings,
        private readonly MediaAssetService $mediaAssets
    ) {
    }

    public function generate(
        Article $article,
        User $user
    ): MediaAsset {
        $article->loadMissing([
            'translations',
            'heroMedia',
        ]);

        if (
            filled(
                $article
                    ->hero_media_id
            )
            || filled(
                $article
                    ->hero_image_path
            )
            || $article
                ->heroMedia
        ) {
            throw new RuntimeException(
                __(
                    'article_ai.errors.image_exists'
                )
            );
        }

        if (
            ! $this->settings
                ->enabled()
            || blank(
                $this->settings
                    ->apiKey(
                        'openai'
                    )
            )
        ) {
            throw new RuntimeException(
                __(
                    'article_ai.errors.openai_not_configured'
                )
            );
        }

        $source =
            $article
                ->sourceTranslation();

        if (! $source) {
            throw new RuntimeException(
                __(
                    'article_ai.errors.source_missing'
                )
            );
        }

        $model =
            trim(
                (string)
                $this->settings
                    ->get(
                        'openai.image_model',
                        self::DEFAULT_MODEL
                    )
            )
            ?: self::DEFAULT_MODEL;

        $prompt =
            $this->prompt(
                $article,
                $source->title,
                $source->excerpt,
                $source->body_html
            );

        $run =
            AiTranslationRun::create([
                'content_type' =>
                    self::CONTENT_TYPE,
                'content_id' =>
                    $article->id,
                'source_locale' =>
                    $article
                        ->source_locale,
                'target_locale' =>
                    $article
                        ->source_locale,
                'provider' =>
                    'openai',
                'model' =>
                    $model,
                'status' =>
                    'started',
                'request_chars' =>
                    mb_strlen(
                        $prompt
                    ),
                'initiated_by' =>
                    $user->id,
            ]);

        $media = null;

        try {
            $response =
                Http::withToken(
                    (string)
                    $this->settings
                        ->apiKey(
                            'openai'
                        )
                )
                    ->acceptJson()
                    ->timeout(
                        max(
                            120,
                            $this->settings
                                ->timeout()
                        )
                    )
                    ->post(
                        'https://api.openai.com/v1/images/generations',
                        [
                            'model' =>
                                $model,
                            'prompt' =>
                                $prompt,
                            'size' =>
                                self::SIZE,
                            'quality' =>
                                self::QUALITY,
                        ]
                    );

            $this->ensureSuccess(
                $response
            );

            $base64 =
                (string)
                data_get(
                    $response->json(),
                    'data.0.b64_json',
                    ''
                );

            if ($base64 === '') {
                throw new RuntimeException(
                    __(
                        'article_ai.errors.empty_image'
                    )
                );
            }

            $bytes =
                base64_decode(
                    $base64,
                    true
                );

            if (
                ! is_string($bytes)
                || $bytes === ''
            ) {
                throw new RuntimeException(
                    __(
                        'article_ai.errors.invalid_image'
                    )
                );
            }

            $slug =
                Str::slug(
                    $source->title
                )
                ?: (
                    'article-'
                    . $article->id
                );

            $media =
                $this->mediaAssets
                    ->storeGeneratedImage(
                        $bytes,
                        $user,
                        'ai-'
                            . $slug
                            . '.png',
                        'AI — '
                            . $source
                                ->title,
                        $source
                            ->title,
                        'article-heroes-ai'
                    );

            /*
             * Re-check immediately before association. This avoids
             * silently overwriting an image attached by another
             * editor while the AI request was running.
             */
            $article->refresh();

            if (
                filled(
                    $article
                        ->hero_media_id
                )
                || filled(
                    $article
                        ->hero_image_path
                )
            ) {
                $this->mediaAssets
                    ->delete(
                        $media
                    );

                throw new RuntimeException(
                    __(
                        'article_ai.errors.image_exists'
                    )
                );
            }

            $article->forceFill([
                'hero_media_id' =>
                    $media->id,
                'hero_image_path' =>
                    $media->path,
                'updated_by' =>
                    $user->id,
            ])->save();

            $json =
                $response->json();

            $run->update([
                'provider' =>
                    'openai',
                'model' =>
                    $model,
                'status' =>
                    'success',
                'input_tokens' =>
                    data_get(
                        $json,
                        'usage.input_tokens'
                    ),
                'output_tokens' =>
                    data_get(
                        $json,
                        'usage.output_tokens'
                    ),
                'total_tokens' =>
                    data_get(
                        $json,
                        'usage.total_tokens'
                    ),
                'response_chars' =>
                    strlen($bytes),
                'error_message' =>
                    null,
            ]);

            return $media;
        } catch (\Throwable $exception) {
            if (
                $media
                && ! $article
                    ->fresh()
                    ?->hero_media_id
            ) {
                try {
                    if (
                        $media->exists
                    ) {
                        $this->mediaAssets
                            ->delete(
                                $media
                            );
                    }
                } catch (\Throwable) {
                    // Do not hide the primary generation error.
                }
            }

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

    private function prompt(
        Article $article,
        string $title,
        ?string $excerpt,
        string $bodyHtml
    ): string {
        $body = trim(
            preg_replace(
                '/\s+/u',
                ' ',
                strip_tags(
                    $bodyHtml
                )
            ) ?? ''
        );

        $body = Str::limit(
            $body,
            3200,
            ''
        );

        $excerpt = trim(
            (string) $excerpt
        );

        return implode(
            "\n",
            array_filter([
                'Create a professional horizontal editorial illustration for an educational website about stereoscopy, 3D imaging, optics, photography and visual culture.',
                'The image will be the hero image of an article.',
                'Aspect and composition: landscape 3:2, strong central visual idea, clear subject, enough negative space for responsive crops.',
                'Style: realistic or refined editorial visualization appropriate to the article topic; visually sophisticated, technically credible, not a generic stock-photo look.',
                'Do not put any readable text, captions, labels, logos, watermarks or interface elements in the image.',
                'Avoid decorative 3D glasses unless they are genuinely relevant to the article.',
                'If the subject is historical, favor a museum/editorial atmosphere and period-appropriate objects without inventing readable markings.',
                '',
                'Article title: '
                    . $title,
                $excerpt !== ''
                    ? 'Article summary: '
                        . $excerpt
                    : null,
                $body !== ''
                    ? 'Article context: '
                        . $body
                    : null,
                '',
                'Generate one image that communicates the article topic immediately and works as a website hero image.',
            ])
        );
    }

    private function ensureSuccess(
        Response $response
    ): void {
        if ($response->successful()) {
            return;
        }

        $message =
            (string)
            (
                data_get(
                    $response->json(),
                    'error.message'
                )
                ?: data_get(
                    $response->json(),
                    'message'
                )
                ?: (
                    'OpenAI image generation failed with HTTP '
                    . $response->status()
                )
            );

        throw new RuntimeException(
            $message
        );
    }
}
