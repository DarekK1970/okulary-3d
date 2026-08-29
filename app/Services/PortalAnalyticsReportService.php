<?php

namespace App\Services;

use App\Models\PortalAnalyticsEvent;
use App\Models\PortalAnalyticsPageView;
use App\Models\PortalAnalyticsSession;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class PortalAnalyticsReportService
{
    /**
     * @return array<string, mixed>
     */
    public function report(
        string $range
    ): array {
        [$start, $label] =
            $this->period($range);

        $pageViewsQuery =
            PortalAnalyticsPageView::query()
                ->where(
                    'occurred_at',
                    '>=',
                    $start
                );

        $eventsQuery =
            PortalAnalyticsEvent::query()
                ->where(
                    'occurred_at',
                    '>=',
                    $start
                );

        $sessionsQuery =
            PortalAnalyticsSession::query()
                ->where(
                    'last_seen_at',
                    '>=',
                    $start
                );

        $pageViews =
            (int) (
                clone $pageViewsQuery
            )->count();

        $sessions =
            (int) (
                clone $pageViewsQuery
            )
                ->distinct(
                    'analytics_session_id'
                )
                ->count(
                    'analytics_session_id'
                );

        $events =
            (int) (
                clone $eventsQuery
            )->count();

        $activeSessions =
            PortalAnalyticsSession::query()
                ->where(
                    'last_seen_at',
                    '>=',
                    now()->subMinutes(5)
                )
                ->count();

        $pageViewRows =
            (clone $pageViewsQuery)
                ->orderBy('occurred_at')
                ->get([
                    'occurred_at',
                    'route_name',
                    'path',
                    'locale',
                    'page_type',
                ]);

        $sessionRows =
            (clone $sessionsQuery)
                ->get([
                    'source_group',
                    'source_name',
                    'landing_locale',
                    'device_type',
                    'started_at',
                    'last_seen_at',
                ]);

        $eventRows =
            (clone $eventsQuery)
                ->orderBy('occurred_at')
                ->get([
                    'event_name',
                    'category',
                    'label',
                    'route_name',
                    'path',
                    'locale',
                    'occurred_at',
                ]);

        return [
            'range' => $range,
            'rangeLabel' => $label,
            'start' => $start,
            'metrics' => [
                'pageviews' => $pageViews,
                'sessions' => $sessions,
                'events' => $events,
                'active_sessions' =>
                    $activeSessions,
                'pages_per_session' =>
                    $sessions > 0
                        ? round(
                            $pageViews
                            / $sessions,
                            2
                        )
                        : 0,
                'lab_actions' =>
                    $eventRows
                        ->where(
                            'event_name',
                            'lab_action'
                        )
                        ->count(),
                'recommendation_clicks' =>
                    $eventRows
                        ->where(
                            'event_name',
                            'recommendation_click'
                        )
                        ->count(),
            ],
            'timeline' =>
                $this->timeline(
                    $pageViewRows,
                    $start,
                    $range
                ),
            'hourly' =>
                $this->hourly(
                    $pageViewRows
                ),
            'topPages' =>
                $this->topPages(
                    $pageViewRows
                ),
            'pageTypes' =>
                $this->counts(
                    $pageViewRows,
                    'page_type'
                ),
            'sources' =>
                $this->counts(
                    $sessionRows,
                    'source_group'
                ),
            'sourceNames' =>
                $this->sourceNames(
                    $sessionRows
                ),
            'locales' =>
                $this->counts(
                    $pageViewRows,
                    'locale'
                ),
            'devices' =>
                $this->counts(
                    $sessionRows,
                    'device_type'
                ),
            'events' =>
                $this->counts(
                    $eventRows,
                    'event_name'
                ),
            'recentEvents' =>
                PortalAnalyticsEvent::query()
                    ->where(
                        'occurred_at',
                        '>=',
                        $start
                    )
                    ->latest(
                        'occurred_at'
                    )
                    ->limit(20)
                    ->get(),
            'funnel' =>
                $this->funnel(
                    $pageViewRows,
                    $eventRows
                ),
        ];
    }

    /**
     * @return array{
     *     0: CarbonImmutable,
     *     1: string
     * }
     */
    private function period(
        string $range
    ): array {
        $now = CarbonImmutable::now(
            config(
                'app.timezone',
                'UTC'
            )
        );

        return match ($range) {
            'today' => [
                $now->startOfDay(),
                __('analytics.ranges.today'),
            ],
            '30' => [
                $now
                    ->subDays(29)
                    ->startOfDay(),
                __('analytics.ranges.days_30'),
            ],
            default => [
                $now
                    ->subDays(6)
                    ->startOfDay(),
                __('analytics.ranges.days_7'),
            ],
        };
    }

    /**
     * @param Collection<int, mixed> $rows
     * @return list<array{
     *     label: string,
     *     value: int
     * }>
     */
    private function timeline(
        Collection $rows,
        CarbonImmutable $start,
        string $range
    ): array {
        if ($range === 'today') {
            $counts =
                $rows
                    ->groupBy(
                        fn ($row): int =>
                            (int)
                            $row
                                ->occurred_at
                                ->format('G')
                    )
                    ->map->count();

            $result = [];

            for (
                $hour = 0;
                $hour < 24;
                $hour++
            ) {
                $result[] = [
                    'label' =>
                        str_pad(
                            (string) $hour,
                            2,
                            '0',
                            STR_PAD_LEFT
                        ) . ':00',
                    'value' =>
                        (int) (
                            $counts[$hour]
                            ?? 0
                        ),
                ];
            }

            return $result;
        }

        $counts =
            $rows
                ->groupBy(
                    fn ($row): string =>
                        $row
                            ->occurred_at
                            ->format(
                                'Y-m-d'
                            )
                )
                ->map->count();

        $days =
            $range === '30'
                ? 30
                : 7;

        $result = [];

        for (
            $offset = 0;
            $offset < $days;
            $offset++
        ) {
            $day =
                $start->addDays(
                    $offset
                );

            $key =
                $day->format(
                    'Y-m-d'
                );

            $result[] = [
                'label' =>
                    $day->format(
                        'd.m'
                    ),
                'value' =>
                    (int) (
                        $counts[$key]
                        ?? 0
                    ),
            ];
        }

        return $result;
    }

    /**
     * @param Collection<int, mixed> $rows
     * @return list<array{
     *     label: string,
     *     value: int
     * }>
     */
    private function hourly(
        Collection $rows
    ): array {
        $counts =
            $rows
                ->groupBy(
                    fn ($row): int =>
                        (int)
                        $row
                            ->occurred_at
                            ->format('G')
                )
                ->map->count();

        $result = [];

        for (
            $hour = 0;
            $hour < 24;
            $hour++
        ) {
            $result[] = [
                'label' =>
                    str_pad(
                        (string) $hour,
                        2,
                        '0',
                        STR_PAD_LEFT
                    ),
                'value' =>
                    (int) (
                        $counts[$hour]
                        ?? 0
                    ),
            ];
        }

        return $result;
    }

    /**
     * @param Collection<int, mixed> $rows
     * @return list<array{
     *     path: string,
     *     route: string|null,
     *     value: int
     * }>
     */
    private function topPages(
        Collection $rows
    ): array {
        return $rows
            ->groupBy(
                fn ($row): string =>
                    (string)
                    $row->path
            )
            ->map(
                fn (Collection $group): array => [
                    'path' =>
                        (string)
                        $group
                            ->first()
                            ->path,
                    'route' =>
                        $group
                            ->first()
                            ->route_name,
                    'value' =>
                        $group->count(),
                ]
            )
            ->sortByDesc('value')
            ->take(15)
            ->values()
            ->all();
    }

    /**
     * @param Collection<int, mixed> $rows
     * @return list<array{
     *     label: string,
     *     value: int
     * }>
     */
    private function counts(
        Collection $rows,
        string $field
    ): array {
        return $rows
            ->groupBy(
                fn ($row): string =>
                    (string) (
                        $row->{$field}
                        ?: 'other'
                    )
            )
            ->map(
                fn (Collection $group): int =>
                    $group->count()
            )
            ->sortDesc()
            ->map(
                fn (
                    int $value,
                    string $label
                ): array => [
                    'label' => $label,
                    'value' => $value,
                ]
            )
            ->values()
            ->all();
    }

    /**
     * @param Collection<int, mixed> $rows
     * @return list<array{
     *     label: string,
     *     value: int
     * }>
     */
    private function sourceNames(
        Collection $rows
    ): array {
        return $rows
            ->filter(
                fn ($row): bool =>
                    filled(
                        $row
                            ->source_name
                    )
            )
            ->groupBy(
                fn ($row): string =>
                    (string)
                    $row
                        ->source_name
            )
            ->map->count()
            ->sortDesc()
            ->take(12)
            ->map(
                fn (
                    int $value,
                    string $label
                ): array => [
                    'label' => $label,
                    'value' => $value,
                ]
            )
            ->values()
            ->all();
    }

    /**
     * @param Collection<int, mixed> $pageViews
     * @param Collection<int, mixed> $events
     * @return array<string, int>
     */
    private function funnel(
        Collection $pageViews,
        Collection $events
    ): array {
        return [
            'product_views' =>
                $pageViews
                    ->where(
                        'route_name',
                        'shop.show'
                    )
                    ->count(),
            'add_to_cart' =>
                $events
                    ->where(
                        'event_name',
                        'add_to_cart'
                    )
                    ->count(),
            'cart_views' =>
                $pageViews
                    ->where(
                        'route_name',
                        'cart.index'
                    )
                    ->count(),
            'checkout_views' =>
                $pageViews
                    ->where(
                        'route_name',
                        'checkout.create'
                    )
                    ->count(),
            'checkout_submit' =>
                $events
                    ->where(
                        'event_name',
                        'checkout_submit'
                    )
                    ->count(),
        ];
    }
}
