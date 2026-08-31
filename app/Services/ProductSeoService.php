<?php

namespace App\Services;

use App\Models\AiTranslationRun;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ProductSeoService
{
    public const TYPE = 'product_seo';

    public function __construct(
        private AiTranslationSettingsService $settings
    ) {
    }

    public function generate(
        Product $product,
        User $user
    ): AiTranslationRun {
        if (! $this->settings->configured()) {
            throw new RuntimeException(
                __('product_ai.errors.not_configured')
            );
        }

        $product->loadMissing([
            'translations',
            'category.translations',
        ]);

        $sourceLocale = (string) $product->source_locale;
        $translation = $product->translation($sourceLocale);

        if (! $translation) {
            throw new RuntimeException(
                __('product_ai.errors.source_missing')
            );
        }

        $needsTitle = blank($translation->seo_title);
        $needsDescription = blank($translation->seo_description);

        if (! $needsTitle && ! $needsDescription) {
            throw new RuntimeException(
                __('product_ai.errors.seo_complete')
            );
        }

        $context = $this->context(
            $product,
            $sourceLocale
        );

        $requestText = json_encode(
            $context,
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_PRETTY_PRINT
        ) ?: '{}';

        $run = AiTranslationRun::create([
            // Reuse the product bucket so existing token/audit
            // reports continue to include the operation.
            'content_type' => 'product',
            'content_id' => $product->getKey(),
            'source_locale' => $sourceLocale,
            'target_locale' => $sourceLocale,
            'provider' => $this->settings->provider(),
            'model' => $this->settings->model(),
            'status' => 'started',
            'request_chars' => mb_strlen($requestText),
            'initiated_by' => $user->id,
        ]);

        try {
            $result = match ($this->settings->provider()) {
                'openai' => $this->openAi(
                    $context,
                    $sourceLocale
                ),
                'gemini' => $this->gemini(
                    $context,
                    $sourceLocale
                ),
                default => throw new RuntimeException(
                    __('product_ai.errors.provider')
                ),
            };

            $fields = $result['fields'];

            $updates = [];

            if ($needsTitle) {
                $updates['seo_title'] = $this->limit(
                    $fields['seo_title'],
                    70
                );
            }

            if ($needsDescription) {
                $updates['seo_description'] = $this->limit(
                    $fields['seo_description'],
                    180
                );
            }

            if (
                ($needsTitle && blank($updates['seo_title'] ?? null))
                || (
                    $needsDescription
                    && blank($updates['seo_description'] ?? null)
                )
            ) {
                throw new RuntimeException(
                    __('product_ai.errors.empty_response')
                );
            }

            $translation->update($updates);

            $product->forceFill([
                'updated_by' => $user->id,
            ])->save();

            $run->update([
                'provider' => $result['provider'],
                'model' => $result['model'],
                'status' => 'success',
                'input_tokens' => $result['input_tokens'],
                'output_tokens' => $result['output_tokens'],
                'total_tokens' => $result['total_tokens'],
                'response_chars' => mb_strlen(
                    $result['raw_text']
                ),
                'error_message' => null,
            ]);
        } catch (\Throwable $exception) {
            $run->update([
                'status' => 'failed',
                'error_message' => mb_substr(
                    $exception->getMessage(),
                    0,
                    2000
                ),
            ]);

            throw $exception;
        }

        return $run->fresh();
    }

    /**
     * @return array<string, string>
     */
    private function context(
        Product $product,
        string $locale
    ): array {
        $translation = $product->translation($locale);

        $category = $product->category
            ?->translation($locale)
            ?? $product->category?->sourceTranslation();

        $description = html_entity_decode(
            strip_tags(
                (string) ($translation?->description_html ?? '')
            ),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );

        return [
            'product_name' => trim(
                (string) ($translation?->name ?? '')
            ),
            'brand' => trim((string) ($product->brand ?? '')),
            'category' => trim(
                (string) ($category?->name ?? '')
            ),
            'short_description' => trim(
                (string) (
                    $translation?->short_description
                    ?? ''
                )
            ),
            'description' => mb_substr(
                trim($description),
                0,
                12000
            ),
            'existing_seo_title' => trim(
                (string) ($translation?->seo_title ?? '')
            ),
            'existing_seo_description' => trim(
                (string) (
                    $translation?->seo_description
                    ?? ''
                )
            ),
        ];
    }

    /**
     * @param array<string, string> $context
     * @return array{
     *   fields: array{seo_title:string,seo_description:string},
     *   provider:string,
     *   model:string,
     *   input_tokens:int|null,
     *   output_tokens:int|null,
     *   total_tokens:int|null,
     *   raw_text:string
     * }
     */
    private function openAi(
        array $context,
        string $locale
    ): array {
        $model = $this->settings->model('openai');

        $response = Http::withToken(
            (string) $this->settings->apiKey('openai')
        )
            ->acceptJson()
            ->timeout($this->settings->timeout())
            ->post(
                'https://api.openai.com/v1/responses',
                [
                    'model' => $model,
                    'input' => [
                        [
                            'role' => 'system',
                            'content' => $this->prompt(
                                $locale
                            ),
                        ],
                        [
                            'role' => 'user',
                            'content' => json_encode(
                                $context,
                                JSON_UNESCAPED_UNICODE
                                | JSON_UNESCAPED_SLASHES
                                | JSON_PRETTY_PRINT
                            ) ?: '{}',
                        ],
                    ],
                    'text' => [
                        'format' => [
                            'type' => 'json_schema',
                            'name' => 'product_seo',
                            'strict' => true,
                            'schema' => $this->schema(),
                        ],
                    ],
                ]
            );

        $this->ensureSuccess($response);

        $json = $response->json();
        $text = $this->extractOpenAiText($json);

        return [
            'fields' => $this->decode($text),
            'provider' => 'openai',
            'model' => $model,
            'input_tokens' => data_get(
                $json,
                'usage.input_tokens'
            ),
            'output_tokens' => data_get(
                $json,
                'usage.output_tokens'
            ),
            'total_tokens' => data_get(
                $json,
                'usage.total_tokens'
            ),
            'raw_text' => $text,
        ];
    }

    /**
     * @param array<string, string> $context
     * @return array{
     *   fields: array{seo_title:string,seo_description:string},
     *   provider:string,
     *   model:string,
     *   input_tokens:int|null,
     *   output_tokens:int|null,
     *   total_tokens:int|null,
     *   raw_text:string
     * }
     */
    private function gemini(
        array $context,
        string $locale
    ): array {
        $model = $this->settings->model('gemini');
        $modelName = str_starts_with(
            $model,
            'models/'
        ) ? substr($model, 7) : $model;

        $response = Http::withHeaders([
            'x-goog-api-key' =>
                (string) $this->settings->apiKey(
                    'gemini'
                ),
        ])
            ->acceptJson()
            ->timeout($this->settings->timeout())
            ->post(
                'https://generativelanguage.googleapis.com/v1beta/models/'
                . rawurlencode($modelName)
                . ':generateContent',
                [
                    'systemInstruction' => [
                        'parts' => [
                            [
                                'text' => $this->prompt(
                                    $locale
                                ),
                            ],
                        ],
                    ],
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                [
                                    'text' => json_encode(
                                        $context,
                                        JSON_UNESCAPED_UNICODE
                                        | JSON_UNESCAPED_SLASHES
                                        | JSON_PRETTY_PRINT
                                    ) ?: '{}',
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'responseMimeType' =>
                            'application/json',
                        'responseJsonSchema' =>
                            $this->schema(),
                    ],
                ]
            );

        $this->ensureSuccess($response);

        $json = $response->json();

        $text = collect(
            data_get(
                $json,
                'candidates.0.content.parts',
                []
            )
        )
            ->pluck('text')
            ->filter()
            ->implode('');

        if ($text === '') {
            throw new RuntimeException(
                __('product_ai.errors.empty_response')
            );
        }

        return [
            'fields' => $this->decode($text),
            'provider' => 'gemini',
            'model' => $model,
            'input_tokens' => data_get(
                $json,
                'usageMetadata.promptTokenCount'
            ),
            'output_tokens' => data_get(
                $json,
                'usageMetadata.candidatesTokenCount'
            ),
            'total_tokens' => data_get(
                $json,
                'usageMetadata.totalTokenCount'
            ),
            'raw_text' => $text,
        ];
    }

    private function prompt(string $locale): string
    {
        $language = $locale === 'pl'
            ? 'Polish'
            : 'English';

        return <<<PROMPT
You create search-engine metadata for a specialist e-commerce portal about stereoscopy, 3D imaging, optical filters and lenticular printing.

Generate SEO metadata in {$language} for the supplied product.

Rules:
- Return only facts supported by the product data.
- Do not invent certifications, standards, compatibility, materials, dimensions, performance claims or technical parameters.
- Preserve brand names, product codes and specialist terminology.
- seo_title: concise, natural, commercially useful, maximum 70 characters.
- seo_description: useful search snippet, maximum 180 characters.
- Do not use quotation marks around the result.
- Avoid clickbait and excessive superlatives.
- If an existing SEO field is supplied, preserve its meaning instead of contradicting it.
- Return only the JSON object required by the schema.
PROMPT;
    }

    /**
     * @return array<string, mixed>
     */
    private function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'seo_title' => [
                    'type' => 'string',
                ],
                'seo_description' => [
                    'type' => 'string',
                ],
            ],
            'required' => [
                'seo_title',
                'seo_description',
            ],
            'additionalProperties' => false,
        ];
    }

    private function ensureSuccess(
        Response $response
    ): void {
        if ($response->successful()) {
            return;
        }

        throw new RuntimeException(
            __('product_ai.errors.http', [
                'status' => $response->status(),
            ])
        );
    }

    /**
     * @param array<string, mixed> $json
     */
    private function extractOpenAiText(
        array $json
    ): string {
        foreach ((array) ($json['output'] ?? []) as $item) {
            foreach (
                (array) ($item['content'] ?? [])
                as $content
            ) {
                if (
                    ($content['type'] ?? null)
                        === 'output_text'
                    && filled($content['text'] ?? null)
                ) {
                    return (string) $content['text'];
                }
            }
        }

        throw new RuntimeException(
            __('product_ai.errors.empty_response')
        );
    }

    /**
     * @return array{seo_title:string,seo_description:string}
     */
    private function decode(string $text): array
    {
        $trimmed = trim($text);

        if (str_starts_with($trimmed, '```')) {
            $trimmed = preg_replace(
                '/^```(?:json)?\s*|\s*```$/i',
                '',
                $trimmed
            ) ?? $trimmed;
        }

        try {
            $decoded = json_decode(
                $trimmed,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException $exception) {
            throw new RuntimeException(
                __('product_ai.errors.invalid_json'),
                previous: $exception
            );
        }

        if (
            ! is_array($decoded)
            || ! array_key_exists(
                'seo_title',
                $decoded
            )
            || ! array_key_exists(
                'seo_description',
                $decoded
            )
        ) {
            throw new RuntimeException(
                __('product_ai.errors.invalid_json')
            );
        }

        return [
            'seo_title' => (string) $decoded['seo_title'],
            'seo_description' =>
                (string) $decoded['seo_description'],
        ];
    }

    private function limit(
        string $value,
        int $max
    ): string {
        return mb_substr(
            trim($value),
            0,
            $max
        );
    }
}
