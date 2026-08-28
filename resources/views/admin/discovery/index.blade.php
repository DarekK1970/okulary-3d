@extends('admin.layout')

@section('title', __('discovery.admin.title') . ' — ' . __('admin.title'))
@section('page_heading', __('discovery.admin.title'))

@section('content')
<section class="discovery-page">
    <div class="cms-page-heading discovery-heading">
        <div>
            <span class="admin-eyebrow">{{ __('discovery.admin.kicker') }}</span>
            <h1>{{ __('discovery.admin.title') }}</h1>
            <p>{{ __('discovery.admin.description') }}</p>
        </div>

        @if (auth()->user()->role === \App\Models\User::ROLE_SUPER_ADMIN)
            <a
                class="cms-secondary-button"
                href="{{ route('admin.settings.discovery') }}"
            >
                ⚙ {{ __('discovery.admin.settings') }}
            </a>
        @endif
    </div>

    @if ($errors->has('discovery'))
        <div class="discovery-alert is-error">
            {{ $errors->first('discovery') }}
        </div>
    @endif

    <div class="discovery-status-strip">
        <div>
            <span>{{ __('discovery.admin.agent_status') }}</span>
            <strong class="{{ $settings->configured() ? 'is-ok' : 'is-off' }}">
                {{ $settings->configured() ? __('discovery.admin.configured') : __('discovery.admin.not_configured') }}
            </strong>
        </div>
        <div>
            <span>{{ __('discovery.admin.provider') }}</span>
            <strong>{{ strtoupper($settings->provider()) }}</strong>
        </div>
        <div>
            <span>{{ __('discovery.admin.model') }}</span>
            <strong>{{ $settings->model() }}</strong>
        </div>
        <div>
            <span>{{ __('discovery.admin.source_rule') }}</span>
            <strong>
                {{ $settings->minSources() }} {{ __('discovery.admin.sources_short') }} /
                {{ $settings->minDomains() }} {{ __('discovery.admin.domains_short') }}
            </strong>
        </div>
    </div>

    <section class="cms-panel discovery-run-panel">
        <div class="discovery-panel-heading">
            <div>
                <span class="admin-eyebrow">{{ __('discovery.admin.manual_run_kicker') }}</span>
                <h2>{{ __('discovery.admin.manual_run') }}</h2>
                <p>{{ __('discovery.admin.manual_run_help') }}</p>
            </div>
        </div>

        <form
            method="post"
            action="{{ route('admin.discovery.run') }}"
            class="discovery-run-form"
        >
            @csrf

            <label>
                <span>{{ __('discovery.admin.topic') }}</span>
                <input
                    type="text"
                    name="topic"
                    list="discovery-topics"
                    maxlength="190"
                    value="{{ old('topic', $topics[0] ?? '') }}"
                    required
                >
                <datalist id="discovery-topics">
                    @foreach ($topics as $topic)
                        <option value="{{ $topic }}"></option>
                    @endforeach
                </datalist>
            </label>

            <label class="discovery-run-query">
                <span>{{ __('discovery.admin.query') }}</span>
                <textarea
                    name="query"
                    rows="3"
                    maxlength="3000"
                    placeholder="{{ __('discovery.admin.query_placeholder') }}"
                >{{ old('query') }}</textarea>
            </label>

            <label>
                <span>{{ __('discovery.admin.freshness') }}</span>
                <input
                    type="number"
                    name="freshness_days"
                    min="1"
                    max="365"
                    value="{{ old('freshness_days', $settings->freshnessDays()) }}"
                    required
                >
            </label>

            <label>
                <span>{{ __('discovery.admin.limit') }}</span>
                <input
                    type="number"
                    name="candidate_limit"
                    min="1"
                    max="25"
                    value="{{ old('candidate_limit', $settings->candidateLimit()) }}"
                    required
                >
            </label>

            <button
                class="cms-primary-button discovery-run-button"
                type="submit"
                @disabled(!$settings->configured())
            >
                ⌕ {{ __('discovery.admin.run_now') }}
            </button>
        </form>
    </section>

    <div class="discovery-columns">
        <section>
            <div class="discovery-section-heading">
                <div>
                    <span class="admin-eyebrow">{{ __('discovery.admin.queue_kicker') }}</span>
                    <h2>{{ __('discovery.admin.queue') }}</h2>
                </div>
            </div>

            <form
                class="cms-filter-bar discovery-filter-bar"
                method="get"
                action="{{ route('admin.discovery.index') }}"
            >
                <input
                    type="search"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="{{ __('discovery.admin.search') }}"
                >

                <select name="decision">
                    <option value="">{{ __('discovery.admin.all_decisions') }}</option>
                    @foreach (\App\Enums\DiscoveryDecision::cases() as $decision)
                        <option
                            value="{{ $decision->value }}"
                            @selected(request('decision') === $decision->value)
                        >
                            {{ __('discovery.decisions.' . $decision->value) }}
                        </option>
                    @endforeach
                </select>

                <select name="section">
                    <option value="">{{ __('discovery.admin.all_sections') }}</option>
                    @foreach (['technology', 'photography', 'lenticular', 'history', 'cinema', 'spatial', 'hardware', 'science', 'culture'] as $section)
                        <option
                            value="{{ $section }}"
                            @selected(request('section') === $section)
                        >
                            {{ __('discovery.sections.' . $section) }}
                        </option>
                    @endforeach
                </select>

                <button type="submit">{{ __('discovery.admin.filter') }}</button>
            </form>

            <div class="discovery-candidate-list">
                @forelse ($candidates as $candidate)
                    <article class="discovery-candidate-card">
                        <div class="discovery-candidate-main">
                            <div class="discovery-candidate-topline">
                                <span class="discovery-section-badge">
                                    {{ $candidate->suggested_section ? __('discovery.sections.' . $candidate->suggested_section) : '—' }}
                                </span>

                                <span class="discovery-decision discovery-decision-{{ $candidate->decision->value }}">
                                    {{ __('discovery.decisions.' . $candidate->decision->value) }}
                                </span>
                            </div>

                            <h3>{{ $candidate->title }}</h3>
                            <p>{{ $candidate->summary }}</p>

                            <div class="discovery-candidate-meta">
                                <span>{{ $candidate->sources->count() }} {{ __('discovery.admin.sources') }}</span>
                                <span>{{ $candidate->sources->pluck('domain')->unique()->count() }} {{ __('discovery.admin.domains') }}</span>
                                <span>{{ $candidate->created_at->format('d.m.Y H:i') }}</span>
                            </div>
                        </div>

                        <div class="discovery-score-column">
                            <div>
                                <span>R</span>
                                <strong>{{ $candidate->relevance_score }}</strong>
                                <small>{{ __('discovery.admin.relevance') }}</small>
                            </div>
                            <div>
                                <span>N</span>
                                <strong>{{ $candidate->novelty_score }}</strong>
                                <small>{{ __('discovery.admin.novelty') }}</small>
                            </div>
                            <div>
                                <span>C</span>
                                <strong>{{ $candidate->confidence_score }}</strong>
                                <small>{{ __('discovery.admin.confidence') }}</small>
                            </div>
                        </div>

                        <a
                            class="cms-action-button discovery-open-button"
                            href="{{ route('admin.discovery.show', $candidate) }}"
                        >
                            {{ __('discovery.admin.review') }} →
                        </a>
                    </article>
                @empty
                    <div class="discovery-empty">
                        <strong>{{ __('discovery.admin.empty') }}</strong>
                        <p>{{ __('discovery.admin.empty_help') }}</p>
                    </div>
                @endforelse
            </div>

            @if ($candidates->hasPages())
                <div class="cms-pagination">
                    {{ $candidates->links() }}
                </div>
            @endif
        </section>

        <aside>
            <section class="cms-panel discovery-runs-panel">
                <h2>{{ __('discovery.admin.recent_runs') }}</h2>

                <div class="discovery-runs-list">
                    @forelse ($runs as $run)
                        <div class="discovery-run-item">
                            <div>
                                <strong>{{ $run->topic ?: __('discovery.admin.untitled_run') }}</strong>
                                <span>{{ $run->created_at->format('d.m.Y H:i') }}</span>
                            </div>

                            <span class="discovery-run-status discovery-run-status-{{ $run->status->value }}">
                                {{ __('discovery.run_statuses.' . $run->status->value) }}
                            </span>

                            <div class="discovery-run-stats">
                                <span>{{ __('discovery.admin.saved') }}: {{ $run->saved_candidates }}</span>
                                <span>{{ __('discovery.admin.skipped') }}: {{ $run->skipped_candidates }}</span>
                                <span>{{ __('discovery.admin.duplicates') }}: {{ $run->duplicate_candidates }}</span>
                            </div>

                            @if ($run->total_tokens)
                                <small>{{ number_format($run->total_tokens, 0, ',', ' ') }} tokens</small>
                            @endif

                            @if ($run->error_message)
                                <p class="discovery-run-error">{{ $run->error_message }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="discovery-muted">{{ __('discovery.admin.no_runs') }}</p>
                    @endforelse
                </div>
            </section>
        </aside>
    </div>
</section>
@endsection
