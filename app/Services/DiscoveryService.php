<?php

namespace App\Services;

use App\Enums\DiscoveryDecision;
use App\Enums\DiscoveryRunStatus;
use App\Models\DiscoveryCandidate;
use App\Models\DiscoveryRun;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class DiscoveryService
{
    public function __construct(
        private DiscoverySettingsService $settings,
        private DiscoveryProviderService $provider
    ) {
    }

    public function run(
        string $topic,
        string $query,
        ?User $user = null,
        ?int $freshnessDays = null,
        ?int $candidateLimit = null
    ): DiscoveryRun {
        $topic = trim($topic);
        $query = trim($query);
        $freshnessDays ??= $this->settings->freshnessDays();
        $candidateLimit ??= $this->settings->candidateLimit();
        $freshnessDays = max(1, min(365, $freshnessDays));
        $candidateLimit = max(1, min(25, $candidateLimit));

        if ($query === '') {
            $query = $topic;
        }

        if ($topic === '') {
            $topic = $query;
        }

        if ($topic === '' || $query === '') {
            throw new RuntimeException(
                __('discovery.errors.query_required')
            );
        }

        $run = DiscoveryRun::create([
            'user_id' => $user?->id,
            'provider' => $this->settings->provider(),
            'model' => $this->settings->model(),
            'status' => DiscoveryRunStatus::Running,
            'topic' => Str::limit($topic, 190, ''),
            'query' => $query,
            'freshness_days' => $freshnessDays,
            'requested_candidates' => $candidateLimit,
            'started_at' => now(),
        ]);

        try {
            $result = $this->provider->discover(
                $topic,
                $query,
                $freshnessDays,
                $candidateLimit
            );

            $saved = 0;
            $skipped = 0;
            $duplicates = 0;

            DB::transaction(function () use (
                $run,
                $result,
                &$saved,
                &$skipped,
                &$duplicates
            ) {
                foreach (array_slice(
                    $result['candidates'],
                    0,
                    $run->requested_candidates
                ) as $candidateData) {
                    $normalized = $this->normalizeCandidate(
                        $candidateData
                    );

                    if ($normalized === null) {
                        $skipped += 1;
                        continue;
                    }

                    if ($this->isDuplicate($normalized['fingerprint'])) {
                        $duplicates += 1;
                        continue;
                    }

                    $candidate = $run->candidates()->create([
                        'fingerprint' => $normalized['fingerprint'],
                        'cluster_key' => $normalized['cluster_key'],
                        'title' => $normalized['title'],
                        'angle' => $normalized['angle'],
                        'summary' => $normalized['summary'],
                        'suggested_section' => $normalized['suggested_section'],
                        'relevance_score' => $normalized['relevance_score'],
                        'novelty_score' => $normalized['novelty_score'],
                        'confidence_score' => $normalized['confidence_score'],
                        'facts' => $normalized['facts'],
                        'keywords' => $normalized['keywords'],
                        'decision' => DiscoveryDecision::Pending,
                    ]);

                    foreach ($normalized['sources'] as $source) {
                        $candidate->sources()->create($source);
                    }

                    $saved += 1;
                }

                $run->update([
                    'provider' => $result['provider'],
                    'model' => $result['model'],
                    'status' => DiscoveryRunStatus::Completed,
                    'saved_candidates' => $saved,
                    'skipped_candidates' => $skipped,
                    'duplicate_candidates' => $duplicates,
                    'input_tokens' => $result['input_tokens'],
                    'output_tokens' => $result['output_tokens'],
                    'total_tokens' => $result['total_tokens'],
                    'raw_response' => $result['raw_text'],
                    'completed_at' => now(),
                ]);
            });
        } catch (Throwable $exception) {
            $run->update([
                'status' => DiscoveryRunStatus::Failed,
                'error_message' => Str::limit(
                    $exception->getMessage(),
                    65000,
                    ''
                ),
                'completed_at' => now(),
            ]);

            throw $exception;
        }

        return $run->fresh([
            'candidates.sources',
            'user',
        ]);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>|null
     */
    private function normalizeCandidate(array $data): ?array
    {
        $title = trim((string) ($data['title'] ?? ''));
        $summary = trim((string) ($data['summary'] ?? ''));
        $clusterKey = trim((string) ($data['cluster_key'] ?? $title));

        if ($title === '' || $summary === '' || $clusterKey === '') {
            return null;
        }

        $sources = collect((array) ($data['sources'] ?? []))
            ->filter(static fn (mixed $value): bool => is_array($value))
            ->map(fn (array $source) => $this->normalizeSource($source))
            ->filter()
            ->unique('url_hash')
            ->values();

        if ($sources->count() < $this->settings->minSources()) {
            return null;
        }

        if ($sources->pluck('domain')->unique()->count() < $this->settings->minDomains()) {
            return null;
        }

        $allowedSourceMap = $sources
            ->mapWithKeys(fn (array $source) => [
                $this->normalizeUrl($source['url']) => $source['url'],
            ]);

        $facts = collect((array) ($data['facts'] ?? []))
            ->filter(static fn (mixed $value): bool => is_array($value))
            ->map(function (array $fact) use ($allowedSourceMap): array {
                return [
                    'fact' => trim((string) ($fact['fact'] ?? '')),
                    'source_urls' => collect(
                        (array) ($fact['source_urls'] ?? [])
                    )
                        ->map(fn ($url) => trim((string) $url))
                        ->filter(fn ($url) => filter_var(
                            $url,
                            FILTER_VALIDATE_URL
                        ))
                        ->map(fn ($url) => $allowedSourceMap->get(
                            $this->normalizeUrl($url)
                        ))
                        ->filter()
                        ->unique()
                        ->values()
                        ->all(),
                ];
            })
            ->filter(fn (array $fact) =>
                $fact['fact'] !== ''
                && $fact['source_urls'] !== []
            )
            ->values()
            ->all();

        if ($facts === []) {
            return null;
        }

        $citedUrls = collect($facts)
            ->flatMap(fn (array $fact) => $fact['source_urls'])
            ->unique()
            ->values();

        if ($citedUrls->count() < $this->settings->minSources()) {
            return null;
        }

        $citedDomains = $sources
            ->whereIn('url', $citedUrls->all())
            ->pluck('domain')
            ->unique();

        if ($citedDomains->count() < $this->settings->minDomains()) {
            return null;
        }

        $keywords = collect((array) ($data['keywords'] ?? []))
            ->map(fn ($keyword) => trim((string) $keyword))
            ->filter()
            ->unique()
            ->take(20)
            ->values()
            ->all();

        $fingerprint = hash(
            'sha256',
            Str::lower(
                Str::ascii($clusterKey)
            )
        );

        return [
            'fingerprint' => $fingerprint,
            'cluster_key' => Str::limit($clusterKey, 190, ''),
            'title' => Str::limit($title, 255, ''),
            'angle' => $this->nullableText($data['angle'] ?? null),
            'summary' => $summary,
            'suggested_section' => $this->section(
                (string) ($data['suggested_section'] ?? '')
            ),
            'relevance_score' => $this->score($data['relevance_score'] ?? 0),
            'novelty_score' => $this->score($data['novelty_score'] ?? 0),
            'confidence_score' => $this->score($data['confidence_score'] ?? 0),
            'facts' => $facts,
            'keywords' => $keywords,
            'sources' => $sources->all(),
        ];
    }

    /**
     * @param array<string, mixed> $source
     * @return array<string, mixed>|null
     */
    private function normalizeSource(array $source): ?array
    {
        $url = trim((string) ($source['url'] ?? ''));

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $scheme = Str::lower((string) parse_url($url, PHP_URL_SCHEME));

        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        $domain = $this->normalizeDomain(
            (string) (
                parse_url($url, PHP_URL_HOST)
                ?: ($source['domain'] ?? '')
            )
        );

        if ($domain === '') {
            return null;
        }

        if ($this->excludedDomain($domain)) {
            return null;
        }

        $language = Str::lower(
            trim((string) ($source['language'] ?? ''))
        );

        if (
            $this->settings->excludePolishSources()
            && (
                $language === 'pl'
                || str_ends_with($domain, '.pl')
            )
        ) {
            return null;
        }

        $publishedAt = null;
        $publishedRaw = trim(
            (string) ($source['published_at'] ?? '')
        );

        if ($publishedRaw !== '') {
            try {
                $publishedAt = Carbon::parse($publishedRaw);
            } catch (Throwable) {
                $publishedAt = null;
            }
        }

        $normalizedUrl = $this->normalizeUrl($url);

        return [
            'url' => $url,
            'url_hash' => hash('sha256', $normalizedUrl),
            'title' => $this->nullableText($source['title'] ?? null, 500),
            'domain' => $domain,
            'language' => $language !== ''
                ? Str::limit($language, 10, '')
                : null,
            'published_at' => $publishedAt,
            'excerpt' => $this->nullableText($source['excerpt'] ?? null),
            'source_type' => $this->sourceType(
                (string) ($source['source_type'] ?? 'other')
            ),
            'credibility_score' => $this->score(
                $source['credibility_score'] ?? 0
            ),
        ];
    }

    private function isDuplicate(string $fingerprint): bool
    {
        return DiscoveryCandidate::query()
            ->where('fingerprint', $fingerprint)
            ->where(
                'decision',
                '!=',
                DiscoveryDecision::Rejected->value
            )
            ->exists();
    }

    private function excludedDomain(string $domain): bool
    {
        foreach ($this->settings->excludedDomains() as $excluded) {
            $excluded = $this->normalizeDomain($excluded);

            if (
                $excluded !== ''
                && (
                    $domain === $excluded
                    || str_ends_with($domain, '.' . $excluded)
                )
            ) {
                return true;
            }
        }

        return false;
    }

    private function normalizeDomain(string $domain): string
    {
        $domain = Str::lower(trim($domain));
        $domain = preg_replace('#^https?://#', '', $domain) ?: $domain;
        $domain = explode('/', $domain)[0];
        $domain = preg_replace('/^www\./', '', $domain) ?: $domain;

        return trim($domain, ". \t\n\r\0\x0B");
    }

    private function normalizeUrl(string $url): string
    {
        $parts = parse_url($url);

        if (! is_array($parts)) {
            return $url;
        }

        $scheme = Str::lower((string) ($parts['scheme'] ?? 'https'));
        $host = $this->normalizeDomain((string) ($parts['host'] ?? ''));
        $path = rtrim((string) ($parts['path'] ?? ''), '/');

        return $scheme . '://' . $host . $path;
    }

    private function score(mixed $value): int
    {
        return max(0, min(100, (int) $value));
    }

    private function section(string $value): ?string
    {
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

        return in_array($value, $allowed, true)
            ? $value
            : null;
    }

    private function sourceType(string $value): string
    {
        $allowed = [
            'primary',
            'institution',
            'research',
            'specialist_media',
            'news_media',
            'manufacturer',
            'other',
        ];

        return in_array($value, $allowed, true)
            ? $value
            : 'other';
    }

    private function nullableText(
        mixed $value,
        int $limit = 65000
    ): ?string {
        $value = trim((string) $value);

        return $value === ''
            ? null
            : Str::limit($value, $limit, '');
    }
}
