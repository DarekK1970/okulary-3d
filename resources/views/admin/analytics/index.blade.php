@extends('admin.layout')

@section('title', __('analytics.admin.title') . ' — ' . __('admin.title'))
@section('page_heading', __('analytics.admin.title'))

@section('content')
@php
    $timelineMax = max(
        1,
        collect($timeline)->max('value') ?? 1
    );

    $hourlyMax = max(
        1,
        collect($hourly)->max('value') ?? 1
    );

    $pageMax = max(
        1,
        collect($topPages)->max('value') ?? 1
    );

    $botTopMax = max(
        1,
        collect($bots['top_bots'] ?? [])->max('requests') ?? 1
    );

    $botPathMax = max(
        1,
        collect($bots['top_paths'] ?? [])->max('requests') ?? 1
    );
@endphp

<section class="portal-analytics-page">
    <div class="cms-page-heading">
        <div>
            <span class="admin-eyebrow">{{ __('analytics.admin.kicker') }}</span>
            <h1>{{ __('analytics.admin.title') }}</h1>
            <p>{{ __('analytics.admin.description') }}</p>
        </div>

        <div class="analytics-range-switcher">
            @foreach ([
                'today' => __('analytics.ranges.today'),
                '7' => __('analytics.ranges.days_7'),
                '30' => __('analytics.ranges.days_30'),
            ] as $rangeKey => $rangeText)
                <a
                    class="{{ $range === $rangeKey ? 'is-active' : '' }}"
                    href="{{ route('admin.analytics', ['range' => $rangeKey]) }}"
                >
                    {{ $rangeText }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="analytics-privacy-note">
        <strong>{{ __('analytics.admin.privacy_title') }}</strong>
        <span>{{ __('analytics.admin.privacy_text') }}</span>
    </div>

    <div class="analytics-metric-grid">
        <article>
            <span>{{ __('analytics.metrics.pageviews') }}</span>
            <strong>{{ number_format($metrics['pageviews'], 0, ',', ' ') }}</strong>
            <small>{{ $rangeLabel }}</small>
        </article>

        <article>
            <span>{{ __('analytics.metrics.sessions') }}</span>
            <strong>{{ number_format($metrics['sessions'], 0, ',', ' ') }}</strong>
            <small>{{ __('analytics.metrics.anonymous_sessions') }}</small>
        </article>

        <article>
            <span>{{ __('analytics.metrics.pages_per_session') }}</span>
            <strong>{{ number_format($metrics['pages_per_session'], 2, ',', ' ') }}</strong>
            <small>{{ __('analytics.metrics.average') }}</small>
        </article>

        <article>
            <span>{{ __('analytics.metrics.active_sessions') }}</span>
            <strong>{{ number_format($metrics['active_sessions'], 0, ',', ' ') }}</strong>
            <small>{{ __('analytics.metrics.last_5_minutes') }}</small>
        </article>

        <article>
            <span>{{ __('analytics.metrics.events') }}</span>
            <strong>{{ number_format($metrics['events'], 0, ',', ' ') }}</strong>
            <small>{{ __('analytics.metrics.interactions') }}</small>
        </article>

        <article>
            <span>{{ __('analytics.metrics.lab_actions') }}</span>
            <strong>{{ number_format($metrics['lab_actions'], 0, ',', ' ') }}</strong>
            <small>3D LAB</small>
        </article>

        <article>
            <span>{{ __('analytics.metrics.recommendation_clicks') }}</span>
            <strong>{{ number_format($metrics['recommendation_clicks'], 0, ',', ' ') }}</strong>
            <small>Article → LAB / Shop</small>
        </article>
    </div>

    <section class="cms-panel analytics-chart-panel">
        <div class="analytics-panel-heading">
            <div>
                <span class="admin-eyebrow">{{ __('analytics.admin.traffic_kicker') }}</span>
                <h2>{{ __('analytics.admin.traffic_title') }}</h2>
            </div>
            <span>{{ $rangeLabel }}</span>
        </div>

        <div class="analytics-column-chart">
            @foreach ($timeline as $point)
                <div class="analytics-column">
                    <span class="analytics-column-value">
                        {{ $point['value'] }}
                    </span>

                    <div class="analytics-column-track">
                        <i
                            style="height: {{ max(2, round(($point['value'] / $timelineMax) * 100, 2)) }}%"
                            title="{{ $point['label'] }}: {{ $point['value'] }}"
                        ></i>
                    </div>

                    <small>{{ $point['label'] }}</small>
                </div>
            @endforeach
        </div>
    </section>

    <div class="analytics-two-column">
        <section class="cms-panel">
            <div class="analytics-panel-heading">
                <div>
                    <span class="admin-eyebrow">{{ __('analytics.admin.top_pages_kicker') }}</span>
                    <h2>{{ __('analytics.admin.top_pages') }}</h2>
                </div>
            </div>

            <div class="analytics-ranking">
                @forelse ($topPages as $page)
                    <div class="analytics-ranking-row">
                        <div class="analytics-ranking-copy">
                            <strong>{{ $page['path'] }}</strong>
                            <small>{{ $page['route'] ?: '—' }}</small>
                        </div>

                        <div class="analytics-ranking-bar">
                            <i
                                style="width: {{ max(2, round(($page['value'] / $pageMax) * 100, 2)) }}%"
                            ></i>
                        </div>

                        <b>{{ $page['value'] }}</b>
                    </div>
                @empty
                    <p class="analytics-empty">{{ __('analytics.admin.no_data') }}</p>
                @endforelse
            </div>
        </section>

        <section class="cms-panel">
            <div class="analytics-panel-heading">
                <div>
                    <span class="admin-eyebrow">{{ __('analytics.admin.hourly_kicker') }}</span>
                    <h2>{{ __('analytics.admin.hourly') }}</h2>
                </div>
            </div>

            <div class="analytics-hourly-chart">
                @foreach ($hourly as $point)
                    <div>
                        <i
                            style="height: {{ max(2, round(($point['value'] / $hourlyMax) * 100, 2)) }}%"
                            title="{{ $point['label'] }}: {{ $point['value'] }}"
                        ></i>
                        <span>{{ $point['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <div class="analytics-four-column">
        @foreach ([
            [
                'title' => __('analytics.admin.page_types'),
                'data' => $pageTypes,
                'prefix' => 'analytics.page_types.',
            ],
            [
                'title' => __('analytics.admin.sources'),
                'data' => $sources,
                'prefix' => 'analytics.sources.',
            ],
            [
                'title' => __('analytics.admin.devices'),
                'data' => $devices,
                'prefix' => 'analytics.devices.',
            ],
            [
                'title' => __('analytics.admin.languages'),
                'data' => $locales,
                'prefix' => null,
            ],
        ] as $block)
            <section class="cms-panel analytics-small-panel">
                <h2>{{ $block['title'] }}</h2>

                <div class="analytics-simple-list">
                    @forelse ($block['data'] as $item)
                        <div>
                            <span>
                                @if ($block['prefix'])
                                    {{ __($block['prefix'] . $item['label']) }}
                                @else
                                    {{ strtoupper($item['label']) }}
                                @endif
                            </span>
                            <strong>{{ $item['value'] }}</strong>
                        </div>
                    @empty
                        <p class="analytics-empty">{{ __('analytics.admin.no_data') }}</p>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>

    <div class="analytics-two-column">
        <section class="cms-panel">
            <div class="analytics-panel-heading">
                <div>
                    <span class="admin-eyebrow">{{ __('analytics.admin.referrers_kicker') }}</span>
                    <h2>{{ __('analytics.admin.referrers') }}</h2>
                </div>
            </div>

            <div class="analytics-simple-list">
                @forelse ($sourceNames as $source)
                    <div>
                        <span>{{ $source['label'] }}</span>
                        <strong>{{ $source['value'] }}</strong>
                    </div>
                @empty
                    <p class="analytics-empty">{{ __('analytics.admin.no_data') }}</p>
                @endforelse
            </div>
        </section>

        <section class="cms-panel">
            <div class="analytics-panel-heading">
                <div>
                    <span class="admin-eyebrow">{{ __('analytics.admin.events_kicker') }}</span>
                    <h2>{{ __('analytics.admin.events') }}</h2>
                </div>
            </div>

            <div class="analytics-simple-list">
                @forelse ($events as $event)
                    <div>
                        <span>{{ __('analytics.events.' . $event['label']) }}</span>
                        <strong>{{ $event['value'] }}</strong>
                    </div>
                @empty
                    <p class="analytics-empty">{{ __('analytics.admin.no_data') }}</p>
                @endforelse
            </div>
        </section>
    </div>

    <section class="cms-panel analytics-funnel-panel">
        <div class="analytics-panel-heading">
            <div>
                <span class="admin-eyebrow">{{ __('analytics.admin.funnel_kicker') }}</span>
                <h2>{{ __('analytics.admin.funnel') }}</h2>
            </div>
        </div>

        <div class="analytics-funnel">
            @foreach ([
                'product_views' => __('analytics.funnel.product_views'),
                'add_to_cart' => __('analytics.funnel.add_to_cart'),
                'cart_views' => __('analytics.funnel.cart_views'),
                'checkout_views' => __('analytics.funnel.checkout_views'),
                'checkout_submit' => __('analytics.funnel.checkout_submit'),
            ] as $key => $label)
                <div>
                    <span>{{ $label }}</span>
                    <strong>{{ $funnel[$key] }}</strong>
                </div>

                @if (!$loop->last)
                    <b>→</b>
                @endif
            @endforeach
        </div>
    </section>


    <section class="cms-panel analytics-bot-panel">
        <div class="analytics-bot-heading">
            <div>
                <span class="admin-eyebrow">
                    {{ __('analytics.bots.kicker') }}
                </span>

                <h2>
                    {{ __('analytics.bots.title') }}
                </h2>

                <p>
                    {{ __('analytics.bots.description') }}
                </p>
            </div>

            <span class="analytics-bot-range">
                {{ $rangeLabel }}
            </span>
        </div>

        <div class="analytics-bot-metrics">
            <article>
                <span>
                    {{ __('analytics.bots.requests') }}
                </span>

                <strong>
                    {{ number_format(
                        $bots['requests'],
                        0,
                        ',',
                        ' '
                    ) }}
                </strong>
            </article>

            <article>
                <span>
                    {{ __('analytics.bots.recognized') }}
                </span>

                <strong>
                    {{ number_format(
                        $bots['recognized_bots'],
                        0,
                        ',',
                        ' '
                    ) }}
                </strong>
            </article>

            <article>
                <span>
                    {{ __('analytics.bots.crawled_paths') }}
                </span>

                <strong>
                    {{ number_format(
                        $bots['crawled_paths'],
                        0,
                        ',',
                        ' '
                    ) }}
                </strong>
            </article>

            <article>
                <span>
                    {{ __('analytics.bots.last_activity') }}
                </span>

                <strong class="analytics-bot-date">
                    {{ $bots['last_activity']
                        ? $bots['last_activity']->format('d.m.Y H:i:s')
                        : '—' }}
                </strong>
            </article>
        </div>

        <div class="analytics-bot-categories">
            @foreach ($bots['categories'] as $category)
                <article
                    class="analytics-bot-category analytics-bot-category-{{ $category['key'] }}"
                >
                    <div>
                        <span>
                            {{ __('analytics.bot_categories.' . $category['key']) }}
                        </span>

                        <strong>
                            {{ number_format(
                                $category['value'],
                                0,
                                ',',
                                ' '
                            ) }}
                        </strong>
                    </div>

                    <div class="analytics-bot-category-track">
                        <i
                            style="width: {{ max(
                                $category['value'] > 0 ? 2 : 0,
                                $category['percent']
                            ) }}%"
                        ></i>
                    </div>

                    <small>
                        {{ $category['percent'] }}%
                        {{ __('analytics.bots.of_bot_requests') }}
                    </small>
                </article>
            @endforeach
        </div>

        <div class="analytics-bot-columns">
            <section>
                <div class="analytics-bot-table-heading">
                    <h3>
                        {{ __('analytics.bots.top_bots') }}
                    </h3>

                    <span>
                        {{ __('analytics.bots.request_count') }}
                    </span>
                </div>

                <div class="analytics-bot-list">
                    @forelse ($bots['top_bots'] as $bot)
                        <div class="analytics-bot-row">
                            <div class="analytics-bot-copy">
                                <strong>
                                    {{ $bot['name'] }}
                                </strong>

                                <span
                                    class="analytics-bot-badge analytics-bot-badge-{{ $bot['category'] }}"
                                >
                                    {{ __('analytics.bot_categories.' . $bot['category']) }}
                                </span>
                            </div>

                            <div class="analytics-bot-row-bar">
                                <i
                                    style="width: {{ max(
                                        2,
                                        round(
                                            (
                                                $bot['requests']
                                                / $botTopMax
                                            ) * 100,
                                            2
                                        )
                                    ) }}%"
                                ></i>
                            </div>

                            <b>
                                {{ $bot['requests'] }}
                            </b>

                            <time>
                                {{ $bot['last_activity']
                                    ? $bot['last_activity']->format('d.m.Y H:i:s')
                                    : '—' }}
                            </time>
                        </div>
                    @empty
                        <p class="analytics-empty">
                            {{ __('analytics.bots.no_data') }}
                        </p>
                    @endforelse
                </div>
            </section>

            <section>
                <div class="analytics-bot-table-heading">
                    <h3>
                        {{ __('analytics.bots.top_paths') }}
                    </h3>

                    <span>
                        {{ __('analytics.bots.portal_urls') }}
                    </span>
                </div>

                <div class="analytics-bot-list">
                    @forelse ($bots['top_paths'] as $path)
                        <div class="analytics-bot-row analytics-bot-path-row">
                            <div class="analytics-bot-copy">
                                <strong>
                                    {{ $path['path'] }}
                                </strong>

                                <span>
                                    {{ $path['bots'] }}
                                    {{ __('analytics.bots.bots_short') }}
                                </span>
                            </div>

                            <div class="analytics-bot-row-bar">
                                <i
                                    style="width: {{ max(
                                        2,
                                        round(
                                            (
                                                $path['requests']
                                                / $botPathMax
                                            ) * 100,
                                            2
                                        )
                                    ) }}%"
                                ></i>
                            </div>

                            <b>
                                {{ $path['requests'] }}
                            </b>
                        </div>
                    @empty
                        <p class="analytics-empty">
                            {{ __('analytics.bots.no_data') }}
                        </p>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="analytics-bot-note">
            <strong>
                {{ __('analytics.bots.exclusion_title') }}
            </strong>

            <span>
                {{ __('analytics.bots.exclusion_text') }}
            </span>
        </div>
    </section>

    <section class="cms-panel">
        <div class="analytics-panel-heading">
            <div>
                <span class="admin-eyebrow">{{ __('analytics.admin.recent_kicker') }}</span>
                <h2>{{ __('analytics.admin.recent_events') }}</h2>
            </div>
        </div>

        <div class="cms-table-wrap">
            <table class="cms-table analytics-events-table">
                <thead>
                    <tr>
                        <th>{{ __('analytics.table.time') }}</th>
                        <th>{{ __('analytics.table.event') }}</th>
                        <th>{{ __('analytics.table.category') }}</th>
                        <th>{{ __('analytics.table.label') }}</th>
                        <th>{{ __('analytics.table.page') }}</th>
                        <th>{{ __('analytics.table.language') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentEvents as $event)
                        <tr>
                            <td>{{ $event->occurred_at->format('d.m H:i:s') }}</td>
                            <td><strong>{{ $event->event_name }}</strong></td>
                            <td>{{ $event->category ?: '—' }}</td>
                            <td>{{ $event->label ?: '—' }}</td>
                            <td>{{ $event->path ?: $event->route_name ?: '—' }}</td>
                            <td>{{ strtoupper($event->locale ?: '—') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="cms-empty">
                                {{ __('analytics.admin.no_events') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</section>
@endsection
