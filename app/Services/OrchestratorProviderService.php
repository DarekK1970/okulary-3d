<?php

namespace App\Services;

use App\Models\DiscoveryCandidate;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OrchestratorProviderService
{
    public function __construct(
        private OrchestratorSettingsService $settings
    ) {
    }

    /**
     * @param list<array<string, mixed>> $candidates
     * @return array{
     *     items: list<array<string, mixed>>,
     *     summary: string,
     *     provider: string,
     *     model: string,
     *     input_tokens: int|null,
     *     output_tokens: int|null,
     *     total_tokens: int|null,
     *     raw_text: string
     * }
     */
    public function plan(
        array $candidates,
        int $limit
    ): array {
        if (! $this->settings->configured()) {
            throw new RuntimeException(
                __('orchestrator.errors.not_configured')
            );
        }

        $payload = [
            'maximum_items' => $limit,
            'candidates' => $candidates,
        ];

        return $this->structuredRequest(
            'plan',
            $this->planSystemPrompt(),
            $payload,
            $this->planSchema()
        );
    }

    /**
     * @return array{
     *     article: array<string, string>,
     *     provider: string,
     *     model: string,
     *     input_tokens: int|null,
     *     output_tokens: int|null,
     *     total_tokens: int|null,
     *     raw_text: string
     * }
     */
    public function draft(
        DiscoveryCandidate $candidate,
        string $locale,
        int $targetWords,
        ?string $plannedTitle = null,
        ?string $editorialAngle = null
    ): array {
        if (! $this->settings->configured()) {
            throw new RuntimeException(
                __('orchestrator.errors.not_configured')
            );
        }

        $candidate->loadMissing('sources');

        $payload = [
            'language' => $locale,
            'target_words' => $targetWords,
            'editorial_plan' => [
                'planned_title' =>
                    $plannedTitle,
                'editorial_angle' =>
                    $editorialAngle,
            ],
            'candidate' => [
                'id' => $candidate->id,
                'title' => $candidate->title,
                'angle' => $candidate->angle,
                'summary' => $candidate->summary,
                'suggested_section' =>
                    $candidate->suggested_section,
                'keywords' => $candidate->keywords ?? [],
                'facts' => $candidate->facts ?? [],
                'editorial_note' =>
                    $candidate->decision_note,
                'sources' => $candidate->sources
                    ->map(
                        static fn ($source): array => [
                            'url' => $source->url,
                            'title' => $source->title,
                            'domain' => $source->domain,
                            'published_at' =>
                                $source->published_at
                                    ?->toDateString(),
                            'source_type' =>
                                $source->source_type,
                            'credibility_score' =>
                                $source->credibility_score,
                            'excerpt' =>
                                $source->excerpt,
                        ]
                    )
                    ->values()
                    ->all(),
            ],
        ];

        $result = $this->structuredRequest(
            'article',
            $this->articleSystemPrompt(
                $locale,
                $targetWords
            ),
            $payload,
            $this->articleSchema()
        );

        return [
            'article' =>
                (array) (
                    $result['article']
                    ?? []
                ),
            'provider' =>
                $result['provider'],
            'model' =>
                $result['model'],
            'input_tokens' =>
                $result['input_tokens'],
            'output_tokens' =>
                $result['output_tokens'],
            'total_tokens' =>
                $result['total_tokens'],
            'raw_text' =>
                $result['raw_text'],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $schema
     * @return array<string, mixed>
     */
    private function structuredRequest(
        string $name,
        string $systemPrompt,
        array $payload,
        array $schema
    ): array {
        return match (
            $this->settings->provider()
        ) {
            'openai' => $this->openAi(
                $name,
                $systemPrompt,
                $payload,
                $schema
            ),
            'gemini' => $this->gemini(
                $systemPrompt,
                $payload,
                $schema
            ),
            default =>
                throw new RuntimeException(
                    __('orchestrator.errors.provider')
                ),
        };
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $schema
     * @return array<string, mixed>
     */
    private function openAi(
        string $name,
        string $systemPrompt,
        array $payload,
        array $schema
    ): array {
        $model =
            $this->settings->model(
                'openai'
            );

        $response = Http::withToken(
            (string) $this->settings
                ->apiKey('openai')
        )
            ->acceptJson()
            ->timeout(
                $this->settings->timeout()
            )
            ->post(
                'https://api.openai.com/v1/responses',
                [
                    'model' => $model,
                    'input' => [
                        [
                            'role' => 'system',
                            'content' =>
                                $systemPrompt,
                        ],
                        [
                            'role' => 'user',
                            'content' =>
                                $this->json(
                                    $payload
                                ),
                        ],
                    ],
                    'text' => [
                        'format' => [
                            'type' =>
                                'json_schema',
                            'name' =>
                                'orchestrator_'
                                . $name,
                            'strict' => true,
                            'schema' => $schema,
                        ],
                    ],
                ]
            );

        $this->ensureSuccess($response);

        $json = $response->json();
        $text =
            $this->extractOpenAiText(
                $json
            );

        $decoded =
            $this->decodeJson($text);

        return [
            ...$decoded,
            'provider' => 'openai',
            'model' => $model,
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
            'raw_text' => $text,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $schema
     * @return array<string, mixed>
     */
    private function gemini(
        string $systemPrompt,
        array $payload,
        array $schema
    ): array {
        $model =
            $this->settings->model(
                'gemini'
            );

        $modelName =
            str_starts_with(
                $model,
                'models/'
            )
                ? substr($model, 7)
                : $model;

        $response = Http::withHeaders([
            'x-goog-api-key' =>
                (string) $this->settings
                    ->apiKey('gemini'),
        ])
            ->acceptJson()
            ->timeout(
                $this->settings->timeout()
            )
            ->post(
                'https://generativelanguage.googleapis.com/v1beta/models/'
                . rawurlencode($modelName)
                . ':generateContent',
                [
                    'systemInstruction' => [
                        'parts' => [
                            [
                                'text' =>
                                    $systemPrompt,
                            ],
                        ],
                    ],
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                [
                                    'text' =>
                                        $this->json(
                                            $payload
                                        ),
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'responseMimeType' =>
                            'application/json',
                        'responseJsonSchema' =>
                            $schema,
                    ],
                ]
            );

        $this->ensureSuccess($response);

        $json = $response->json();

        $text = collect(
            (array) data_get(
                $json,
                'candidates.0.content.parts',
                []
            )
        )
            ->map(
                static fn ($part) =>
                    is_array($part)
                        ? ($part['text'] ?? null)
                        : null
            )
            ->filter()
            ->implode('');

        if ($text === '') {
            throw new RuntimeException(
                __('orchestrator.errors.empty_response')
            );
        }

        $decoded =
            $this->decodeJson($text);

        return [
            ...$decoded,
            'provider' => 'gemini',
            'model' => $model,
            'input_tokens' =>
                data_get(
                    $json,
                    'usageMetadata.promptTokenCount'
                ),
            'output_tokens' =>
                data_get(
                    $json,
                    'usageMetadata.candidatesTokenCount'
                ),
            'total_tokens' =>
                data_get(
                    $json,
                    'usageMetadata.totalTokenCount'
                ),
            'raw_text' => $text,
        ];
    }

    private function planSystemPrompt(): string
    {
        $prompt = <<<'PROMPT'
You are the editorial Content Orchestrator for a specialist portal about stereoscopy, 3D imaging, lenticular printing, spatial photography/video, historical stereo media, 3D cinema and related optical technologies.

You receive only candidates that were previously researched and manually accepted by the editorial team.

Your task is planning, not additional research:
- select the strongest candidates for the editorial week,
- prioritize reader value, novelty, factual confidence and diversity of sections,
- avoid selecting two candidates that are essentially the same story,
- never invent a candidate ID,
- never add factual claims or sources,
- proposed titles must be original editorial titles, not copied source headlines,
- editorial angles must explain what the final article should help the reader understand,
- return no publication dates; the application assigns approved schedule slots,
- return only the JSON required by the schema.
PROMPT;

        if (
            filled(
                $this->settings
                    ->extraInstructions()
            )
        ) {
            $prompt .=
                "\n\nAdditional editorial rules:\n"
                . trim(
                    (string) $this->settings
                        ->extraInstructions()
                );
        }

        return $prompt;
    }

    private function articleSystemPrompt(
        string $locale,
        int $targetWords
    ): string {
        $language =
            $locale === 'en'
                ? 'English'
                : 'Polish';

        $prompt = <<<PROMPT
You are writing an original editorial article for a specialist portal about stereoscopy and 3D imaging.

Write in {$language}. Aim for approximately {$targetWords} words.

Hard rules:
- use only the supplied research package,
- do not browse the web,
- do not invent facts, dates, quotations, specifications, people, products or source claims,
- synthesize facts from multiple supplied sources instead of rewriting one article,
- do not imitate source wording,
- do not copy excerpts,
- if a detail is not supported by the supplied facts, omit it,
- separate verified facts from interpretation,
- keep the tone informative, technically accurate and readable,
- avoid clickbait and unsupported superlatives,
- do not create a Sources / Źródła section; the application appends the verified source list itself,
- body_html may use only: p, h2, h3, strong, em, ul, ol, li, blockquote,
- do not include script, style, iframe or external HTML,
- seo_title should be concise,
- seo_description should be a short factual summary,
- return only the JSON required by the schema.
PROMPT;

        if (
            filled(
                $this->settings
                    ->extraInstructions()
            )
        ) {
            $prompt .=
                "\n\nAdditional editorial rules:\n"
                . trim(
                    (string) $this->settings
                        ->extraInstructions()
                );
        }

        return $prompt;
    }

    /**
     * @return array<string, mixed>
     */
    private function planSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'summary' => [
                    'type' => 'string',
                ],
                'items' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'candidate_id' => [
                                'type' => 'integer',
                            ],
                            'planned_title' => [
                                'type' => 'string',
                            ],
                            'editorial_angle' => [
                                'type' => 'string',
                            ],
                            'rationale' => [
                                'type' => 'string',
                            ],
                            'suggested_section' => [
                                'type' => 'string',
                                'enum' => [
                                    'technology',
                                    'photography',
                                    'lenticular',
                                    'history',
                                    'cinema',
                                    'spatial',
                                    'hardware',
                                    'science',
                                    'culture',
                                ],
                            ],
                        ],
                        'required' => [
                            'candidate_id',
                            'planned_title',
                            'editorial_angle',
                            'rationale',
                            'suggested_section',
                        ],
                        'additionalProperties' =>
                            false,
                    ],
                ],
            ],
            'required' => [
                'summary',
                'items',
            ],
            'additionalProperties' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function articleSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'article' => [
                    'type' => 'object',
                    'properties' => [
                        'title' => [
                            'type' => 'string',
                        ],
                        'excerpt' => [
                            'type' => 'string',
                        ],
                        'body_html' => [
                            'type' => 'string',
                        ],
                        'seo_title' => [
                            'type' => 'string',
                        ],
                        'seo_description' => [
                            'type' => 'string',
                        ],
                    ],
                    'required' => [
                        'title',
                        'excerpt',
                        'body_html',
                        'seo_title',
                        'seo_description',
                    ],
                    'additionalProperties' => false,
                ],
            ],
            'required' => ['article'],
            'additionalProperties' => false,
        ];
    }

    private function ensureSuccess(
        Response $response
    ): void {
        if ($response->successful()) {
            return;
        }

        $message = trim(
            (string) data_get(
                $response->json(),
                'error.message',
                ''
            )
        );

        throw new RuntimeException(
            __('orchestrator.errors.http', [
                'status' =>
                    $response->status(),
                'message' =>
                    $message !== ''
                        ? ' ' . $message
                        : '',
            ])
        );
    }

    /**
     * @param array<string, mixed> $json
     */
    private function extractOpenAiText(
        array $json
    ): string {
        foreach (
            (array) ($json['output'] ?? [])
            as $item
        ) {
            if (! is_array($item)) {
                continue;
            }

            foreach (
                (array) ($item['content'] ?? [])
                as $content
            ) {
                if (
                    is_array($content)
                    && ($content['type'] ?? null)
                        === 'output_text'
                    && filled(
                        $content['text'] ?? null
                    )
                ) {
                    return (string)
                        $content['text'];
                }
            }
        }

        throw new RuntimeException(
            __('orchestrator.errors.empty_response')
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(
        string $text
    ): array {
        $trimmed = trim($text);

        if (
            str_starts_with(
                $trimmed,
                '```'
            )
        ) {
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
                __('orchestrator.errors.invalid_json'),
                previous: $exception
            );
        }

        if (! is_array($decoded)) {
            throw new RuntimeException(
                __('orchestrator.errors.invalid_json')
            );
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function json(
        array $payload
    ): string {
        return json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_PRETTY_PRINT
        ) ?: '{}';
    }
}
