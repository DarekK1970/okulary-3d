@extends('admin.layout')

@section('title', $candidate->title . ' — ' . __('discovery.admin.title'))
@section('page_heading', __('discovery.admin.review_title'))

@section('content')
<section class="discovery-page">
    <div class="cms-page-heading discovery-heading">
        <div>
            <span class="admin-eyebrow">{{ __('discovery.admin.review_kicker') }}</span>
            <h1>{{ $candidate->title }}</h1>
            <p>{{ $candidate->summary }}</p>
        </div>

        <a
            class="cms-secondary-button"
            href="{{ route('admin.discovery.index') }}"
        >
            ← {{ __('discovery.admin.back') }}
        </a>
    </div>

    <div class="discovery-review-grid">
        <main>
            <section class="cms-panel discovery-review-summary">
                <div class="discovery-review-badges">
                    <span class="discovery-section-badge">
                        {{ $candidate->suggested_section ? __('discovery.sections.' . $candidate->suggested_section) : '—' }}
                    </span>
                    <span class="discovery-decision discovery-decision-{{ $candidate->decision->value }}">
                        {{ __('discovery.decisions.' . $candidate->decision->value) }}
                    </span>
                </div>

                <h2>{{ __('discovery.admin.editorial_angle') }}</h2>
                <p>{{ $candidate->angle ?: '—' }}</p>

                <div class="discovery-score-grid">
                    <div>
                        <span>{{ __('discovery.admin.relevance') }}</span>
                        <strong>{{ $candidate->relevance_score }}/100</strong>
                    </div>
                    <div>
                        <span>{{ __('discovery.admin.novelty') }}</span>
                        <strong>{{ $candidate->novelty_score }}/100</strong>
                    </div>
                    <div>
                        <span>{{ __('discovery.admin.confidence') }}</span>
                        <strong>{{ $candidate->confidence_score }}/100</strong>
                    </div>
                </div>
            </section>

            <section class="cms-panel">
                <h2>{{ __('discovery.admin.facts') }}</h2>
                <p class="discovery-panel-note">{{ __('discovery.admin.facts_help') }}</p>

                <ol class="discovery-facts-list">
                    @forelse (($candidate->facts ?? []) as $fact)
                        <li>
                            <strong>{{ $fact['fact'] ?? '' }}</strong>

                            @if (!empty($fact['source_urls']))
                                <div class="discovery-fact-sources">
                                    @foreach ($fact['source_urls'] as $url)
                                        <a
                                            href="{{ $url }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            {{ parse_url($url, PHP_URL_HOST) ?: __('discovery.admin.source') }} ↗
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </li>
                    @empty
                        <li>{{ __('discovery.admin.no_facts') }}</li>
                    @endforelse
                </ol>
            </section>

            <section class="cms-panel">
                <div class="discovery-panel-heading">
                    <div>
                        <h2>{{ __('discovery.admin.sources') }}</h2>
                        <p>
                            {{ $candidate->sources->count() }} {{ __('discovery.admin.sources') }} ·
                            {{ $candidate->sources->pluck('domain')->unique()->count() }} {{ __('discovery.admin.domains') }}
                        </p>
                    </div>
                </div>

                <div class="discovery-source-list">
                    @foreach ($candidate->sources as $source)
                        <article class="discovery-source-card">
                            <div class="discovery-source-heading">
                                <div>
                                    <span>{{ $source->domain }}</span>
                                    <h3>{{ $source->title ?: $source->url }}</h3>
                                </div>

                                <span class="discovery-source-type">
                                    {{ __('discovery.source_types.' . ($source->source_type ?: 'other')) }}
                                </span>
                            </div>

                            @if ($source->excerpt)
                                <p>{{ $source->excerpt }}</p>
                            @endif

                            <div class="discovery-source-meta">
                                @if ($source->published_at)
                                    <span>{{ $source->published_at->format('d.m.Y') }}</span>
                                @endif
                                @if ($source->language)
                                    <span>{{ strtoupper($source->language) }}</span>
                                @endif
                                <span>{{ __('discovery.admin.credibility') }}: {{ $source->credibility_score }}/100</span>
                            </div>

                            <a
                                href="{{ $source->url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="discovery-source-link"
                            >
                                {{ __('discovery.admin.open_source') }} ↗
                            </a>
                        </article>
                    @endforeach
                </div>
            </section>

            @if (!empty($candidate->keywords))
                <section class="cms-panel">
                    <h2>{{ __('discovery.admin.keywords') }}</h2>
                    <div class="discovery-keywords">
                        @foreach ($candidate->keywords as $keyword)
                            <span>{{ $keyword }}</span>
                        @endforeach
                    </div>
                </section>
            @endif
        </main>

        <aside>
            <section class="cms-panel discovery-decision-panel">
                <h2>{{ __('discovery.admin.editorial_decision') }}</h2>
                <p>{{ __('discovery.admin.editorial_decision_help') }}</p>

                <form
                    method="post"
                    action="{{ route('admin.discovery.decision', $candidate) }}"
                >
                    @csrf
                    @method('PATCH')

                    <div class="cms-field">
                        <label for="discovery-decision">{{ __('discovery.admin.decision') }}</label>
                        <select id="discovery-decision" name="decision" required>
                            @foreach (\App\Enums\DiscoveryDecision::cases() as $decision)
                                <option
                                    value="{{ $decision->value }}"
                                    @selected(old('decision', $candidate->decision->value) === $decision->value)
                                >
                                    {{ __('discovery.decisions.' . $decision->value) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="cms-field">
                        <label for="decision-note">{{ __('discovery.admin.decision_note') }}</label>
                        <textarea
                            id="decision-note"
                            name="decision_note"
                            rows="7"
                            maxlength="4000"
                        >{{ old('decision_note', $candidate->decision_note) }}</textarea>
                    </div>

                    <button class="cms-primary-button" type="submit">
                        {{ __('discovery.admin.save_decision') }}
                    </button>
                </form>

                @if ($candidate->decisionUser)
                    <div class="discovery-decision-audit">
                        <span>{{ __('discovery.admin.decided_by') }}</span>
                        <strong>{{ $candidate->decisionUser->name }}</strong>
                        <small>{{ $candidate->decided_at?->format('d.m.Y H:i') }}</small>
                    </div>
                @endif
            </section>

            <section class="cms-panel discovery-origin-panel">
                <h2>{{ __('discovery.admin.run_details') }}</h2>
                <dl>
                    <div>
                        <dt>{{ __('discovery.admin.topic') }}</dt>
                        <dd>{{ $candidate->run->topic }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('discovery.admin.query') }}</dt>
                        <dd>{{ $candidate->run->query }}</dd>
                    </div>
                    @if ($candidate->run->user)
                        <div>
                            <dt>{{ __('discovery.admin.run_by') }}</dt>
                            <dd>{{ $candidate->run->user->name }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt>{{ __('discovery.admin.provider') }}</dt>
                        <dd>{{ strtoupper($candidate->run->provider) }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('discovery.admin.model') }}</dt>
                        <dd>{{ $candidate->run->model }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('discovery.admin.freshness') }}</dt>
                        <dd>{{ $candidate->run->freshness_days }} {{ __('discovery.admin.days') }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('discovery.admin.discovery_date') }}</dt>
                        <dd>{{ $candidate->run->created_at->format('d.m.Y H:i') }}</dd>
                    </div>
                    @if ($candidate->run->total_tokens)
                        <div>
                            <dt>Tokens</dt>
                            <dd>{{ number_format($candidate->run->total_tokens, 0, ',', ' ') }}</dd>
                        </div>
                    @endif
                </dl>
            </section>
        </aside>
    </div>
</section>
@endsection
