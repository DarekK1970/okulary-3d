@extends('admin.layout')

@section('title', __('newsletter.admin.title') . ' — ' . __('admin.title'))
@section('page_heading', __('newsletter.admin.title'))

@section('content')
<section class="admin-newsletter-page">
    <div class="cms-page-heading">
        <div>
            <span class="admin-eyebrow">{{ __('newsletter.admin.kicker') }}</span>
            <h1>{{ __('newsletter.admin.title') }}</h1>
            <p>{{ __('newsletter.admin.description') }}</p>
        </div>

        <a class="cms-primary-button" href="{{ route('admin.newsletter.campaigns.create') }}">
            + {{ __('newsletter.admin.new_campaign') }}
        </a>
    </div>

    <div class="newsletter-admin-metrics">
        <article>
            <span>{{ __('newsletter.statuses.active') }}</span>
            <strong>{{ number_format($counts['active'], 0, ',', ' ') }}</strong>
        </article>
        <article>
            <span>{{ __('newsletter.statuses.pending') }}</span>
            <strong>{{ number_format($counts['pending'], 0, ',', ' ') }}</strong>
        </article>
        <article>
            <span>{{ __('newsletter.statuses.unsubscribed') }}</span>
            <strong>{{ number_format($counts['unsubscribed'], 0, ',', ' ') }}</strong>
        </article>
    </div>

    <section class="cms-panel">
        <div class="newsletter-panel-heading">
            <div>
                <span class="admin-eyebrow">{{ __('newsletter.admin.campaigns_kicker') }}</span>
                <h2>{{ __('newsletter.admin.campaigns') }}</h2>
            </div>
        </div>

        <div class="cms-table-wrap">
            <table class="cms-table newsletter-campaign-table">
                <thead>
                    <tr>
                        <th>{{ __('newsletter.admin.subject') }}</th>
                        <th>{{ __('newsletter.admin.language') }}</th>
                        <th>{{ __('newsletter.admin.status') }}</th>
                        <th>{{ __('newsletter.admin.schedule') }}</th>
                        <th>{{ __('newsletter.admin.delivery') }}</th>
                        <th class="cms-actions-cell">{{ __('newsletter.admin.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($campaigns as $campaign)
                        <tr>
                            <td><strong>{{ $campaign->subject }}</strong></td>
                            <td>{{ strtoupper($campaign->locale) }}</td>
                            <td>
                                <span class="newsletter-status status-{{ $campaign->status->value }}">
                                    {{ __('newsletter.campaign_statuses.' . $campaign->status->value) }}
                                </span>
                            </td>
                            <td>
                                {{ $campaign->scheduled_at?->format('d.m.Y H:i') ?: '—' }}
                            </td>
                            <td>
                                {{ $campaign->sent_count }} / {{ $campaign->recipient_count }}
                                @if ($campaign->failed_count > 0)
                                    <small class="newsletter-failed">{{ $campaign->failed_count }} {{ __('newsletter.admin.failed_short') }}</small>
                                @endif
                            </td>
                            <td class="cms-actions-cell">
                                <a class="cms-action-button" href="{{ route('admin.newsletter.campaigns.edit', $campaign) }}">
                                    {{ __('newsletter.admin.edit') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="cms-empty">{{ __('newsletter.admin.no_campaigns') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="cms-panel">
        <div class="newsletter-panel-heading">
            <div>
                <span class="admin-eyebrow">{{ __('newsletter.admin.subscribers_kicker') }}</span>
                <h2>{{ __('newsletter.admin.subscribers') }}</h2>
            </div>

            <a
                class="cms-secondary-button"
                href="{{ route('admin.newsletter.subscribers.export', array_filter([
                    'status' => $filters['status'] ?? null,
                    'locale' => $filters['locale'] ?? null,
                ])) }}"
            >
                CSV
            </a>
        </div>

        <form class="newsletter-filter-form" method="get" action="{{ route('admin.newsletter.index') }}">
            <input
                type="search"
                name="q"
                value="{{ $filters['q'] ?? '' }}"
                placeholder="{{ __('newsletter.admin.search_placeholder') }}"
            >

            <select name="status">
                <option value="">{{ __('newsletter.admin.all_statuses') }}</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>
                        {{ __('newsletter.statuses.' . $status->value) }}
                    </option>
                @endforeach
            </select>

            <select name="locale">
                <option value="">{{ __('newsletter.admin.all_languages') }}</option>
                @foreach ($supportedLocales as $code => $language)
                    <option value="{{ $code }}" @selected(($filters['locale'] ?? '') === $code)>
                        {{ strtoupper($code) }} — {{ $language['native'] }}
                    </option>
                @endforeach
            </select>

            <button class="cms-primary-button" type="submit">{{ __('newsletter.admin.filter') }}</button>
            <a class="cms-secondary-button" href="{{ route('admin.newsletter.index') }}">{{ __('newsletter.admin.clear') }}</a>
        </form>

        <div class="cms-table-wrap">
            <table class="cms-table newsletter-subscriber-table">
                <thead>
                    <tr>
                        <th>E-mail</th>
                        <th>{{ __('newsletter.admin.language') }}</th>
                        <th>{{ __('newsletter.admin.status') }}</th>
                        <th>{{ __('newsletter.admin.source') }}</th>
                        <th>{{ __('newsletter.admin.confirmed') }}</th>
                        <th>{{ __('newsletter.admin.last_sent') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($subscribers as $subscriber)
                        <tr>
                            <td><strong>{{ $subscriber->email }}</strong></td>
                            <td>{{ strtoupper($subscriber->locale) }}</td>
                            <td>
                                <span class="newsletter-status status-{{ $subscriber->status->value }}">
                                    {{ __('newsletter.statuses.' . $subscriber->status->value) }}
                                </span>
                            </td>
                            <td>{{ $subscriber->source }}</td>
                            <td>{{ $subscriber->confirmed_at?->format('d.m.Y H:i') ?: '—' }}</td>
                            <td>{{ $subscriber->last_sent_at?->format('d.m.Y H:i') ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="cms-empty">{{ __('newsletter.admin.no_subscribers') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="newsletter-pagination">
            {{ $subscribers->links() }}
        </div>
    </section>
</section>
@endsection
