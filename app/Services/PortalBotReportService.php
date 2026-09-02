<?php

namespace App\Services;

use App\Models\PortalAnalyticsBotRequest;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class PortalBotReportService
{
    /**
     * @return array<string, mixed>
     */
    public function report(
        CarbonInterface $start
    ): array {
        $rows =
            PortalAnalyticsBotRequest::query()
                ->where(
                    'occurred_at',
                    '>=',
                    $start
                )
                ->orderBy(
                    'occurred_at'
                )
                ->get([
                    'bot_name',
                    'category',
                    'route_name',
                    'path',
                    'method',
                    'status_code',
                    'locale',
                    'occurred_at',
                ]);

        $total =
            $rows->count();

        $categories =
            $this->categories(
                $rows,
                $total
            );

        return [
            'requests' =>
                $total,

            'recognized_bots' =>
                $rows
                    ->pluck(
                        'bot_name'
                    )
                    ->filter()
                    ->unique()
                    ->count(),

            'crawled_paths' =>
                $rows
                    ->pluck('path')
                    ->filter()
                    ->unique()
                    ->count(),

            'last_activity' =>
                $rows
                    ->max(
                        'occurred_at'
                    ),

            'categories' =>
                $categories,

            'top_bots' =>
                $this->topBots(
                    $rows
                ),

            'top_paths' =>
                $this->topPaths(
                    $rows
                ),
        ];
    }

    /**
     * @param Collection<int, mixed> $rows
     * @return list<array{
     *     key: string,
     *     value: int,
     *     percent: int
     * }>
     */
    private function categories(
        Collection $rows,
        int $total
    ): array {
        $labels = [
            BotDetectorService::CATEGORY_SEARCH,
            BotDetectorService::CATEGORY_AI,
            BotDetectorService::CATEGORY_SEO,
            BotDetectorService::CATEGORY_SOCIAL,
            BotDetectorService::CATEGORY_MONITORING,
            BotDetectorService::CATEGORY_OTHER,
        ];

        return collect($labels)
            ->map(
                function (
                    string $category
                ) use (
                    $rows,
                    $total
                ): array {
                    $value =
                        $rows
                            ->where(
                                'category',
                                $category
                            )
                            ->count();

                    return [
                        'key' =>
                            $category,
                        'value' =>
                            $value,
                        'percent' =>
                            $total > 0
                                ? (int) round(
                                    (
                                        $value
                                        / $total
                                    ) * 100
                                )
                                : 0,
                    ];
                }
            )
            ->all();
    }

    /**
     * @param Collection<int, mixed> $rows
     * @return list<array{
     *     name: string,
     *     category: string,
     *     requests: int,
     *     last_activity: mixed
     * }>
     */
    private function topBots(
        Collection $rows
    ): array {
        return $rows
            ->groupBy(
                fn ($row): string =>
                    (string)
                    $row->bot_name
            )
            ->map(
                function (
                    Collection $group,
                    string $name
                ): array {
                    $last =
                        $group
                            ->sortByDesc(
                                'occurred_at'
                            )
                            ->first();

                    return [
                        'name' => $name,
                        'category' =>
                            (string)
                            (
                                $last
                                    ?->category
                                ?: BotDetectorService
                                    ::CATEGORY_OTHER
                            ),
                        'requests' =>
                            $group->count(),
                        'last_activity' =>
                            $last
                                ?->occurred_at,
                    ];
                }
            )
            ->sortByDesc(
                'requests'
            )
            ->take(20)
            ->values()
            ->all();
    }

    /**
     * @param Collection<int, mixed> $rows
     * @return list<array{
     *     path: string,
     *     requests: int,
     *     bots: int
     * }>
     */
    private function topPaths(
        Collection $rows
    ): array {
        return $rows
            ->groupBy(
                fn ($row): string =>
                    (string) $row->path
            )
            ->map(
                fn (
                    Collection $group,
                    string $path
                ): array => [
                    'path' =>
                        $path,
                    'requests' =>
                        $group->count(),
                    'bots' =>
                        $group
                            ->pluck(
                                'bot_name'
                            )
                            ->filter()
                            ->unique()
                            ->count(),
                ]
            )
            ->sortByDesc(
                'requests'
            )
            ->take(20)
            ->values()
            ->all();
    }
}
