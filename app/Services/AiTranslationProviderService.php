<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AiTranslationProviderService
{
    public function __construct(
        private AiTranslationSettingsService $settings
    ) {
    }

    /**
     * @param array<string, string> $fields
     * @return array{
     *     fields: array<string, string>,
     *     provider: string,
     *     model: string,
     *     input_tokens: int|null,
     *     output_tokens: int|null,
     *     total_tokens: int|null,
     *     raw_text: string
     * }
     */
    public function translate(
        array $fields,
        string $sourceLocale,
        string $targetLocale,
        string $contentType
    ): array {
        if (! $this->settings->configured()) {
            throw new RuntimeException(
                __('ai_translator.errors.not_configured')
            );
        }

        return match ($this->settings->provider()) {
            'openai' => $this->openAi(
                $fields,
                $sourceLocale,
                $targetLocale,
                $contentType
            ),
            'gemini' => $this->gemini(
                $fields,
                $sourceLocale,
                $targetLocale,
                $contentType
            ),
            default => throw new RuntimeException(
                __('ai_translator.errors.provider')
            ),
        };
    }

    /** @param array<string, string> $fields */
    private function openAi(
        array $fields,
        string $sourceLocale,
        string $targetLocale,
        string $contentType
    ): array {
        $model = $this->settings->model('openai');
        $schema = $this->schema(array_keys($fields));

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
                            'content' => $this->systemPrompt(
                                $sourceLocale,
                                $targetLocale
                            ),
                        ],
                        [
                            'role' => 'user',
                            'content' => $this->userPayload(
                                $fields,
                                $sourceLocale,
                                $targetLocale,
                                $contentType
                            ),
                        ],
                    ],
                    'text' => [
                        'format' => [
                            'type' => 'json_schema',
                            'name' => 'translation_result',
                            'strict' => true,
                            'schema' => $schema,
                        ],
                    ],
                ]
            );

        $this->ensureSuccess($response);

        $json = $response->json();
        $text = $this->extractOpenAiText($json);
        $translated = $this->decodeFields(
            $text,
            array_keys($fields)
        );

        return [
            'fields' => $translated,
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

    /** @param array<string, string> $fields */
    private function gemini(
        array $fields,
        string $sourceLocale,
        string $targetLocale,
        string $contentType
    ): array {
        $model = $this->settings->model('gemini');
        $modelName = str_starts_with($model, 'models/')
            ? substr($model, 7)
            : $model;

        $response = Http::withHeaders([
            'x-goog-api-key' =>
                (string) $this->settings->apiKey('gemini'),
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
                                'text' => $this->systemPrompt(
                                    $sourceLocale,
                                    $targetLocale
                                ),
                            ],
                        ],
                    ],
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                [
                                    'text' => $this->userPayload(
                                        $fields,
                                        $sourceLocale,
                                        $targetLocale,
                                        $contentType
                                    ),
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'responseJsonSchema' =>
                            $this->schema(array_keys($fields)),
                    ],
                ]
            );

        $this->ensureSuccess($response);

        $json = $response->json();
        $parts = data_get(
            $json,
            'candidates.0.content.parts',
            []
        );

        $text = collect($parts)
            ->pluck('text')
            ->filter()
            ->implode('');

        if ($text === '') {
            throw new RuntimeException(
                __('ai_translator.errors.empty_response')
            );
        }

        $translated = $this->decodeFields(
            $text,
            array_keys($fields)
        );

        return [
            'fields' => $translated,
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

    private function ensureSuccess(
        Response $response
    ): void {
        if ($response->successful()) {
            return;
        }

        throw new RuntimeException(
            __('ai_translator.errors.http', [
                'status' => $response->status(),
            ])
        );
    }

    /**
     * @param list<string> $keys
     * @return array<string, mixed>
     */
    private function schema(array $keys): array
    {
        $properties = [];

        foreach ($keys as $key) {
            $properties[$key] = [
                'type' => 'string',
            ];
        }

        return [
            'type' => 'object',
            'properties' => $properties,
            'required' => $keys,
            'additionalProperties' => false,
        ];
    }

    private function systemPrompt(
        string $sourceLocale,
        string $targetLocale
    ): string {
        $source = $sourceLocale === 'pl'
            ? 'Polish'
            : 'English';

        $target = $targetLocale === 'pl'
            ? 'Polish'
            : 'English';

        $prompt = <<<PROMPT
You are a professional translation engine for a specialist portal about stereoscopy, 3D imaging, lenticular printing, historical optical media and 3D products.
Translate from {$source} to {$target}.
Preserve meaning exactly. Do not add facts, explanations or marketing claims that are absent from the source.
Preserve HTML structure, tags, attributes, URLs, measurements, dates, product codes, model numbers and technical notation.
Translate natural-language text inside HTML while keeping valid HTML.
Keep empty source fields empty.
Use terminology natural for the target language while preserving specialist stereoscopic vocabulary.
Return only the structured JSON requested by the API schema.
PROMPT;

        if (filled($this->settings->glossary())) {
            $prompt .= "\n\nProject glossary / terminology rules:\n"
                . trim((string) $this->settings->glossary());
        }

        return $prompt;
    }

    /** @param array<string, string> $fields */
    private function userPayload(
        array $fields,
        string $sourceLocale,
        string $targetLocale,
        string $contentType
    ): string {
        return json_encode(
            [
                'content_type' => $contentType,
                'source_locale' => $sourceLocale,
                'target_locale' => $targetLocale,
                'fields' => $fields,
            ],
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_PRETTY_PRINT
        ) ?: '{}';
    }

    /**
     * @param array<string, mixed> $json
     */
    private function extractOpenAiText(
        array $json
    ): string {
        foreach ((array) ($json['output'] ?? []) as $item) {
            foreach ((array) ($item['content'] ?? []) as $content) {
                if (
                    ($content['type'] ?? null) === 'output_text'
                    && filled($content['text'] ?? null)
                ) {
                    return (string) $content['text'];
                }
            }
        }

        throw new RuntimeException(
            __('ai_translator.errors.empty_response')
        );
    }

    /**
     * @param list<string> $keys
     * @return array<string, string>
     */
    private function decodeFields(
        string $text,
        array $keys
    ): array {
        $trimmed = trim($text);

        if (str_starts_with($trimmed, '```')) {
            $trimmed = preg_replace(
                '/^```(?:json)?\\s*|\\s*```$/i',
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
                __('ai_translator.errors.invalid_json'),
                previous: $exception
            );
        }

        if (! is_array($decoded)) {
            throw new RuntimeException(
                __('ai_translator.errors.invalid_json')
            );
        }

        $result = [];

        foreach ($keys as $key) {
            if (! array_key_exists($key, $decoded)) {
                throw new RuntimeException(
                    __('ai_translator.errors.invalid_json')
                );
            }

            $result[$key] = (string) $decoded[$key];
        }

        return $result;
    }
}
