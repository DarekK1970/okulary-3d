<?php

namespace App\Services;

use App\Enums\ArticleStatus;
use App\Enums\ArticleTranslationStatus;
use App\Enums\DiscoveryDecision;
use App\Enums\OrchestratorItemStatus;
use App\Enums\OrchestratorPlanStatus;
use App\Models\AiTranslationRun;
use App\Models\Article;
use App\Models\ArticleTranslation;
use App\Models\DiscoveryCandidate;
use App\Models\DiscoveryRun;
use App\Models\OrchestratorPlan;
use App\Models\OrchestratorPlanItem;
use App\Models\OrchestratorRun;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class OrchestratorService
{
    public function __construct(
        private OrchestratorSettingsService $settings,
        private OrchestratorProviderService $provider,
        private ArticleHtmlSanitizer $sanitizer
    ) {
    }

    public function availableAcceptedCount(): int
    {
        return $this->availableCandidates()
            ->count();
    }

    /**
     * @return array{
     *     translation: int,
     *     discovery: int,
     *     orchestrator: int,
     *     total: int
     * }
     */
    public function usageSummary(
        int $days = 7
    ): array {
        $since = now()->subDays(
            max(1, $days)
        );

        $translation =
            (int) AiTranslationRun::query()
                ->where(
                    'created_at',
                    '>=',
                    $since
                )
                ->sum('total_tokens');

        $discovery =
            (int) DiscoveryRun::query()
                ->where(
                    'created_at',
                    '>=',
                    $since
                )
                ->sum('total_tokens');

        $orchestrator =
            (int) OrchestratorRun::query()
                ->where(
                    'created_at',
                    '>=',
                    $since
                )
                ->sum('total_tokens');

        return [
            'translation' => $translation,
            'discovery' => $discovery,
            'orchestrator' => $orchestrator,
            'total' =>
                $translation
                + $discovery
                + $orchestrator,
        ];
    }

    public function createPlan(
        string $week,
        int $requestedLimit,
        User $user
    ): OrchestratorPlan {
        if (! $this->settings->configured()) {
            throw new RuntimeException(
                __('orchestrator.errors.not_configured')
            );
        }

        $weekStart =
            CarbonImmutable::parse(
                $week,
                config(
                    'app.timezone',
                    'UTC'
                )
            )->startOfWeek(
                CarbonInterface::MONDAY
            );

        if (
            OrchestratorPlan::query()
                ->whereDate(
                    'week_start',
                    $weekStart->toDateString()
                )
                ->exists()
        ) {
            throw new RuntimeException(
                __('orchestrator.errors.week_exists')
            );
        }

        $slots =
            $this->settings
                ->scheduleSlots();

        $limit = min(
            max(1, $requestedLimit),
            $this->settings
                ->weeklyArticleLimit(),
            count($slots)
        );

        $candidates =
            $this->availableCandidates()
                ->take(30)
                ->get();

        if ($candidates->isEmpty()) {
            throw new RuntimeException(
                __('orchestrator.errors.no_candidates')
            );
        }

        $limit = min(
            $limit,
            $candidates->count()
        );

        $candidatePayload =
            $this->candidatePayload(
                $candidates
            );

        $run =
            OrchestratorRun::create([
                'user_id' => $user->id,
                'action' => 'plan',
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
                            $candidatePayload,
                            JSON_UNESCAPED_UNICODE
                            | JSON_UNESCAPED_SLASHES
                        ) ?: ''
                    ),
                'started_at' => now(),
            ]);

        try {
            $result =
                $this->provider->plan(
                    $candidatePayload,
                    $limit
                );

            $items =
                $this->normalizePlanItems(
                    (array) (
                        $result['items']
                        ?? []
                    ),
                    $candidates,
                    $limit
                );

            if ($items === []) {
                throw new RuntimeException(
                    __('orchestrator.errors.empty_plan')
                );
            }

            $plan = DB::transaction(
                function () use (
                    $weekStart,
                    $slots,
                    $result,
                    $items,
                    $user
                ): OrchestratorPlan {
                    $plan =
                        OrchestratorPlan::create([
                            'week_start' =>
                                $weekStart
                                    ->toDateString(),
                            'week_end' =>
                                $weekStart
                                    ->addDays(6)
                                    ->toDateString(),
                            'status' =>
                                OrchestratorPlanStatus::Draft,
                            'provider' =>
                                $result['provider'],
                            'model' =>
                                $result['model'],
                            'editorial_summary' =>
                                Str::limit(
                                    trim(
                                        (string) (
                                            $result['summary']
                                            ?? ''
                                        )
                                    ),
                                    8000,
                                    ''
                                ),
                            'created_by' =>
                                $user->id,
                        ]);

                    foreach (
                        $items as $position => $item
                    ) {
                        $slot =
                            $slots[$position];

                        $plannedFor =
                            $weekStart
                                ->addDays(
                                    $slot['day'] - 1
                                )
                                ->setTimeFromTimeString(
                                    $slot['time']
                                );

                        $plan->items()->create([
                            'discovery_candidate_id' =>
                                $item[
                                    'candidate_id'
                                ],
                            'position' =>
                                $position + 1,
                            'planned_for' =>
                                $plannedFor,
                            'planned_title' =>
                                Str::limit(
                                    $item[
                                        'planned_title'
                                    ],
                                    255,
                                    ''
                                ),
                            'editorial_angle' =>
                                $this->nullableText(
                                    $item[
                                        'editorial_angle'
                                    ]
                                    ?? null,
                                    12000
                                ),
                            'rationale' =>
                                $this->nullableText(
                                    $item[
                                        'rationale'
                                    ]
                                    ?? null,
                                    12000
                                ),
                            'suggested_section' =>
                                $this->section(
                                    (string) (
                                        $item[
                                            'suggested_section'
                                        ]
                                        ?? ''
                                    )
                                ),
                            'status' =>
                                OrchestratorItemStatus::Planned,
                        ]);
                    }

                    return $plan;
                }
            );

            $run->update([
                'orchestrator_plan_id' =>
                    $plan->id,
                'provider' =>
                    $result['provider'],
                'model' =>
                    $result['model'],
                'status' => 'success',
                'input_tokens' =>
                    $result['input_tokens'],
                'output_tokens' =>
                    $result['output_tokens'],
                'total_tokens' =>
                    $result['total_tokens'],
                'response_chars' =>
                    mb_strlen(
                        $result['raw_text']
                    ),
                'raw_response' =>
                    $result['raw_text'],
                'completed_at' => now(),
            ]);

            return $plan->fresh([
                'items.candidate.sources',
                'creator',
            ]);
        } catch (Throwable $exception) {
            $run->update([
                'status' => 'failed',
                'error_message' =>
                    Str::limit(
                        $exception->getMessage(),
                        65000,
                        ''
                    ),
                'completed_at' => now(),
            ]);

            throw $exception;
        }
    }

    public function approvePlan(
        OrchestratorPlan $plan,
        User $user
    ): OrchestratorPlan {
        if (
            $plan->status
            !== OrchestratorPlanStatus::Draft
        ) {
            throw new RuntimeException(
                __('orchestrator.errors.plan_not_draft')
            );
        }

        if (
            ! $plan->items()
                ->exists()
        ) {
            throw new RuntimeException(
                __('orchestrator.errors.empty_plan')
            );
        }

        $plan->update([
            'status' =>
                OrchestratorPlanStatus::Approved,
            'approved_by' =>
                $user->id,
            'approved_at' =>
                now(),
        ]);

        return $plan->fresh([
            'items.candidate.sources',
            'approver',
        ]);
    }

    public function deleteDraftPlan(
        OrchestratorPlan $plan
    ): void {
        if (
            $plan->status
            !== OrchestratorPlanStatus::Draft
            || $plan->items()
                ->whereNotNull('article_id')
                ->exists()
        ) {
            throw new RuntimeException(
                __('orchestrator.errors.plan_delete_locked')
            );
        }

        $plan->delete();
    }

    public function generateDraft(
        OrchestratorPlanItem $item,
        User $user
    ): Article {
        $item->loadMissing([
            'plan',
            'candidate.sources',
        ]);

        if (
            $item->plan->status
            !== OrchestratorPlanStatus::Approved
        ) {
            throw new RuntimeException(
                __('orchestrator.errors.plan_not_approved')
            );
        }

        if (
            $item->status
            !== OrchestratorItemStatus::Planned
            || $item->article_id
        ) {
            throw new RuntimeException(
                __('orchestrator.errors.item_already_generated')
            );
        }

        if (
            $item->candidate->decision
            !== DiscoveryDecision::Accepted
        ) {
            throw new RuntimeException(
                __('orchestrator.errors.candidate_not_accepted')
            );
        }

        if (! $this->settings->configured()) {
            throw new RuntimeException(
                __('orchestrator.errors.not_configured')
            );
        }

        $category =
            $this->settings
                ->defaultCategory();

        if (! $category) {
            throw new RuntimeException(
                __('orchestrator.errors.category_missing')
            );
        }

        $sourceLocale =
            $this->settings
                ->sourceLocale();

        $requestPayload = [
            'candidate_id' =>
                $item->candidate->id,
            'planned_title' =>
                $item->planned_title,
            'planned_angle' =>
                $item->editorial_angle,
            'candidate' => [
                'title' =>
                    $item->candidate->title,
                'summary' =>
                    $item->candidate->summary,
                'facts' =>
                    $item->candidate->facts,
                'sources' =>
                    $item->candidate->sources
                        ->pluck('url')
                        ->values()
                        ->all(),
            ],
        ];

        $run =
            OrchestratorRun::create([
                'user_id' => $user->id,
                'orchestrator_plan_id' =>
                    $item->plan->id,
                'orchestrator_plan_item_id' =>
                    $item->id,
                'action' => 'draft',
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
                            $requestPayload,
                            JSON_UNESCAPED_UNICODE
                            | JSON_UNESCAPED_SLASHES
                        ) ?: ''
                    ),
                'started_at' => now(),
            ]);

        try {
            $result =
                $this->provider->draft(
                    $item->candidate,
                    $sourceLocale,
                    $this->settings
                        ->targetWords(),
                    $item->planned_title,
                    $item->editorial_angle
                );

            $fields =
                $this->validateArticleFields(
                    $result['article']
                );

            $article = DB::transaction(
                function () use (
                    $fields,
                    $item,
                    $category,
                    $sourceLocale,
                    $user
                ): Article {
                    $body =
                        $this->sanitizer
                            ->sanitize(
                                $this->stripGeneratedLinks(
                                    $fields['body_html']
                                )
                                . $this->sourcesHtml(
                                    $item->candidate,
                                    $sourceLocale
                                )
                            );

                    $translationSlug =
                        $this->uniqueTranslationSlug(
                            $sourceLocale,
                            $fields['title']
                        );

                    $legacySlug =
                        $this->uniqueLegacySlug(
                            $translationSlug
                        );

                    $article =
                        Article::create([
                            'category_id' =>
                                $category->id,
                            'source_locale' =>
                                $sourceLocale,
                            'title' =>
                                $fields['title'],
                            'slug' =>
                                $legacySlug,
                            'excerpt' =>
                                $fields['excerpt']
                                ?: null,
                            'body_html' =>
                                $body,
                            'status' =>
                                ArticleStatus::Draft,
                            'published_at' =>
                                null,
                            'created_by' =>
                                $user->id,
                            'updated_by' =>
                                $user->id,
                        ]);

                    $article->translations()
                        ->create([
                            'locale' =>
                                $sourceLocale,
                            'title' =>
                                $fields['title'],
                            'slug' =>
                                $translationSlug,
                            'excerpt' =>
                                $fields['excerpt']
                                ?: null,
                            'body_html' =>
                                $body,
                            'seo_title' =>
                                $fields['seo_title']
                                ?: null,
                            'seo_description' =>
                                $fields[
                                    'seo_description'
                                ]
                                ?: null,
                            'translation_status' =>
                                ArticleTranslationStatus::Source,
                        ]);

                    $item->update([
                        'article_id' =>
                            $article->id,
                        'status' =>
                            OrchestratorItemStatus::DraftCreated,
                        'generated_at' =>
                            now(),
                    ]);

                    if (
                        ! $item->plan
                            ->items()
                            ->where(
                                'status',
                                OrchestratorItemStatus::Planned->value
                            )
                            ->exists()
                    ) {
                        $item->plan->update([
                            'status' =>
                                OrchestratorPlanStatus::Completed,
                        ]);
                    }

                    return $article;
                }
            );

            $run->update([
                'provider' =>
                    $result['provider'],
                'model' =>
                    $result['model'],
                'status' => 'success',
                'input_tokens' =>
                    $result['input_tokens'],
                'output_tokens' =>
                    $result['output_tokens'],
                'total_tokens' =>
                    $result['total_tokens'],
                'response_chars' =>
                    mb_strlen(
                        $result['raw_text']
                    ),
                'raw_response' =>
                    $result['raw_text'],
                'completed_at' =>
                    now(),
            ]);

            return $article->fresh(
                'translations'
            );
        } catch (Throwable $exception) {
            $run->update([
                'status' => 'failed',
                'error_message' =>
                    Str::limit(
                        $exception->getMessage(),
                        65000,
                        ''
                    ),
                'completed_at' =>
                    now(),
            ]);

            throw $exception;
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<DiscoveryCandidate>
     */
    private function availableCandidates()
    {
        return DiscoveryCandidate::query()
            ->where(
                'decision',
                DiscoveryDecision::Accepted->value
            )
            ->where(
                'relevance_score',
                '>=',
                $this->settings
                    ->minRelevance()
            )
            ->whereDoesntHave(
                'orchestratorPlanItems'
            )
            ->with('sources')
            ->orderByDesc(
                'relevance_score'
            )
            ->orderByDesc(
                'novelty_score'
            )
            ->orderByDesc(
                'confidence_score'
            )
            ->orderBy('id');
    }

    /**
     * @param Collection<int, DiscoveryCandidate> $candidates
     * @return list<array<string, mixed>>
     */
    private function candidatePayload(
        Collection $candidates
    ): array {
        return $candidates
            ->map(
                static fn (
                    DiscoveryCandidate $candidate
                ): array => [
                    'candidate_id' =>
                        $candidate->id,
                    'title' =>
                        $candidate->title,
                    'angle' =>
                        $candidate->angle,
                    'summary' =>
                        $candidate->summary,
                    'suggested_section' =>
                        $candidate
                            ->suggested_section,
                    'relevance_score' =>
                        $candidate
                            ->relevance_score,
                    'novelty_score' =>
                        $candidate
                            ->novelty_score,
                    'confidence_score' =>
                        $candidate
                            ->confidence_score,
                    'keywords' =>
                        $candidate->keywords
                        ?? [],
                    'fact_count' =>
                        count(
                            $candidate->facts
                            ?? []
                        ),
                    'source_count' =>
                        $candidate
                            ->sources
                            ->count(),
                    'source_domains' =>
                        $candidate
                            ->sources
                            ->pluck('domain')
                            ->unique()
                            ->values()
                            ->all(),
                    'editorial_note' =>
                        $candidate
                            ->decision_note,
                ]
            )
            ->values()
            ->all();
    }

    /**
     * @param list<mixed> $items
     * @param Collection<int, DiscoveryCandidate> $candidates
     * @return list<array<string, mixed>>
     */
    private function normalizePlanItems(
        array $items,
        Collection $candidates,
        int $limit
    ): array {
        $allowedIds =
            $candidates
                ->pluck('id')
                ->map(
                    static fn ($id): int =>
                        (int) $id
                )
                ->all();

        $seen = [];
        $normalized = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $candidateId =
                (int) (
                    $item['candidate_id']
                    ?? 0
                );

            $title = trim(
                (string) (
                    $item['planned_title']
                    ?? ''
                )
            );

            if (
                $candidateId < 1
                || $title === ''
                || ! in_array(
                    $candidateId,
                    $allowedIds,
                    true
                )
                || isset(
                    $seen[$candidateId]
                )
            ) {
                continue;
            }

            $seen[$candidateId] = true;

            $normalized[] = [
                'candidate_id' =>
                    $candidateId,
                'planned_title' =>
                    $title,
                'editorial_angle' =>
                    trim(
                        (string) (
                            $item[
                                'editorial_angle'
                            ]
                            ?? ''
                        )
                    ),
                'rationale' =>
                    trim(
                        (string) (
                            $item['rationale']
                            ?? ''
                        )
                    ),
                'suggested_section' =>
                    (string) (
                        $item[
                            'suggested_section'
                        ]
                        ?? ''
                    ),
            ];

            if (
                count($normalized)
                >= $limit
            ) {
                break;
            }
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $fields
     * @return array{
     *     title: string,
     *     excerpt: string,
     *     body_html: string,
     *     seo_title: string,
     *     seo_description: string
     * }
     */
    private function validateArticleFields(
        array $fields
    ): array {
        $title = trim(
            (string) (
                $fields['title']
                ?? ''
            )
        );

        $body = trim(
            (string) (
                $fields['body_html']
                ?? ''
            )
        );

        if (
            $title === ''
            || $body === ''
        ) {
            throw new RuntimeException(
                __('orchestrator.errors.invalid_article')
            );
        }

        return [
            'title' =>
                Str::limit(
                    $title,
                    220,
                    ''
                ),
            'excerpt' =>
                Str::limit(
                    trim(
                        (string) (
                            $fields['excerpt']
                            ?? ''
                        )
                    ),
                    1000,
                    ''
                ),
            'body_html' => $body,
            'seo_title' =>
                Str::limit(
                    trim(
                        (string) (
                            $fields['seo_title']
                            ?? ''
                        )
                    ),
                    70,
                    ''
                ),
            'seo_description' =>
                Str::limit(
                    trim(
                        (string) (
                            $fields[
                                'seo_description'
                            ]
                            ?? ''
                        )
                    ),
                    180,
                    ''
                ),
        ];
    }

    private function sourcesHtml(
        DiscoveryCandidate $candidate,
        string $locale
    ): string {
        $sources =
            $candidate->sources
                ->unique('url')
                ->values();

        if ($sources->isEmpty()) {
            return '';
        }

        $heading =
            $locale === 'en'
                ? 'Sources'
                : 'Źródła';

        $html =
            '<h2>'
            . htmlspecialchars(
                $heading,
                ENT_QUOTES
                | ENT_SUBSTITUTE,
                'UTF-8'
            )
            . '</h2><ul>';

        foreach ($sources as $source) {
            $url = htmlspecialchars(
                (string) $source->url,
                ENT_QUOTES
                | ENT_SUBSTITUTE,
                'UTF-8'
            );

            $label = htmlspecialchars(
                trim(
                    (string) (
                        $source->title
                        ?: $source->domain
                    )
                ),
                ENT_QUOTES
                | ENT_SUBSTITUTE,
                'UTF-8'
            );

            $html .=
                '<li><a href="'
                . $url
                . '" rel="noopener noreferrer">'
                . $label
                . '</a></li>';
        }

        return $html . '</ul>';
    }

    private function stripGeneratedLinks(
        string $html
    ): string {
        return preg_replace(
            '#<a\b[^>]*>(.*?)</a>#is',
            '$1',
            $html
        ) ?? $html;
    }

    private function uniqueTranslationSlug(
        string $locale,
        string $title
    ): string {
        $base =
            Str::slug($title)
            ?: 'article';

        $slug = $base;
        $number = 2;

        while (
            ArticleTranslation::query()
                ->where(
                    'locale',
                    $locale
                )
                ->where(
                    'slug',
                    $slug
                )
                ->exists()
        ) {
            $slug =
                $base
                . '-'
                . $number;

            $number += 1;
        }

        return $slug;
    }

    private function uniqueLegacySlug(
        string $source
    ): string {
        $base =
            Str::slug($source)
            ?: 'article';

        $slug = $base;
        $number = 2;

        while (
            Article::query()
                ->where(
                    'slug',
                    $slug
                )
                ->exists()
        ) {
            $slug =
                $base
                . '-'
                . $number;

            $number += 1;
        }

        return $slug;
    }

    private function section(
        string $value
    ): ?string {
        $allowed = [
            'technology',
            'photography',
            'lenticular',
            'history',
            'cinema',
            'spatial',
            'hardware',
            'science',
            'culture',
        ];

        return in_array(
            $value,
            $allowed,
            true
        ) ? $value : null;
    }

    private function nullableText(
        mixed $value,
        int $limit
    ): ?string {
        $value = trim(
            (string) $value
        );

        return $value === ''
            ? null
            : Str::limit(
                $value,
                $limit,
                ''
            );
    }
}
