@extends('admin.layout')

@section('title', __('orchestrator.admin.title') . ' — ' . __('admin.title'))
@section('page_heading', __('orchestrator.admin.title'))

@section('content')
<section class="admin-orchestrator-page">
    <div class="cms-page-heading">
        <div>
            <span class="admin-eyebrow">{{ __('orchestrator.admin.kicker') }}</span>
            <h1>{{ __('orchestrator.admin.title') }}</h1>
            <p>{{ __('orchestrator.admin.description') }}</p>
        </div>

        @if (auth()->user()->role === \App\Models\User::ROLE_SUPER_ADMIN)
            <a
                class="cms-secondary-button"
                href="{{ route('admin.settings.orchestrator') }}"
            >
                ⚙ {{ __('orchestrator.admin.settings') }}
            </a>
        @endif
    </div>

    @if ($errors->has('orchestrator'))
        <div class="orchestrator-alert orchestrator-alert-error">
            {{ $errors->first('orchestrator') }}
        </div>
    @endif

    <div class="orchestrator-status-grid">
        <article class="orchestrator-status-card">
            <span>{{ __('orchestrator.admin.status') }}</span>
            <strong class="{{ $settings->configured() ? 'is-ok' : 'is-warning' }}">
                {{ $settings->configured()
                    ? __('orchestrator.admin.ready')
                    : __('orchestrator.admin.not_ready') }}
            </strong>
            <small>
                {{ strtoupper($settings->provider()) }}
                · {{ $settings->model() }}
            </small>
        </article>

        <article class="orchestrator-status-card">
            <span>{{ __('orchestrator.admin.accepted_queue') }}</span>
            <strong>{{ $availableCandidates }}</strong>
            <small>{{ __('orchestrator.admin.available_candidates') }}</small>
        </article>

        <article class="orchestrator-status-card">
            <span>{{ __('orchestrator.admin.weekly_limit') }}</span>
            <strong>{{ $settings->weeklyArticleLimit() }}</strong>
            <small>{{ __('orchestrator.admin.articles_per_week') }}</small>
        </article>

        <article class="orchestrator-status-card">
            <span>{{ __('orchestrator.admin.default_category') }}</span>
            <strong>{{ $settings->defaultCategory()?->name ?? '—' }}</strong>
            <small>{{ strtoupper($settings->sourceLocale()) }}</small>
        </article>
    </div>

    <section class="cms-panel orchestrator-usage-panel">
        <div class="orchestrator-panel-heading">
            <div>
                <span class="admin-eyebrow">{{ __('orchestrator.admin.usage_kicker') }}</span>
                <h2>{{ __('orchestrator.admin.usage_title') }}</h2>
            </div>
            <span>{{ __('orchestrator.admin.last_7_days') }}</span>
        </div>

        <div class="orchestrator-usage-grid">
            <div>
                <span>{{ __('orchestrator.admin.translation_tokens') }}</span>
                <strong>{{ number_format($usage['translation'], 0, ',', ' ') }}</strong>
            </div>
            <div>
                <span>{{ __('orchestrator.admin.discovery_tokens') }}</span>
                <strong>{{ number_format($usage['discovery'], 0, ',', ' ') }}</strong>
            </div>
            <div>
                <span>{{ __('orchestrator.admin.orchestrator_tokens') }}</span>
                <strong>{{ number_format($usage['orchestrator'], 0, ',', ' ') }}</strong>
            </div>
            <div class="is-total">
                <span>{{ __('orchestrator.admin.total_tokens') }}</span>
                <strong>{{ number_format($usage['total'], 0, ',', ' ') }}</strong>
            </div>
        </div>
    </section>

    <div class="orchestrator-main-grid">
        <section class="cms-panel">
            <span class="admin-eyebrow">{{ __('orchestrator.admin.plan_kicker') }}</span>
            <h2>{{ __('orchestrator.admin.create_plan') }}</h2>
            <p class="orchestrator-help">{{ __('orchestrator.admin.create_plan_help') }}</p>

            <form
                class="orchestrator-plan-form"
                method="post"
                action="{{ route('admin.orchestrator.plans.store') }}"
            >
                @csrf

                <label>
                    <span>{{ __('orchestrator.admin.week_start') }}</span>
                    <input
                        type="date"
                        name="week_start"
                        value="{{ old('week_start', $defaultWeek) }}"
                        required
                    >
                </label>

                <label>
                    <span>{{ __('orchestrator.admin.article_limit') }}</span>
                    <input
                        type="number"
                        name="article_limit"
                        min="1"
                        max="{{ $settings->weeklyArticleLimit() }}"
                        value="{{ old('article_limit', $settings->weeklyArticleLimit()) }}"
                        required
                    >
                </label>

                <button
                    class="cms-primary-button"
                    type="submit"
                    @disabled(!$settings->configured() || $availableCandidates < 1)
                >
                    {{ __('orchestrator.admin.generate_plan') }}
                </button>
            </form>

            <div class="orchestrator-safety-note">
                <strong>{{ __('orchestrator.admin.safety_title') }}</strong>
                <p>{{ __('orchestrator.admin.safety_text') }}</p>
            </div>
        </section>

        <section class="cms-panel">
            <span class="admin-eyebrow">{{ __('orchestrator.admin.workflow_kicker') }}</span>
            <h2>{{ __('orchestrator.admin.workflow_title') }}</h2>

            <ol class="orchestrator-workflow">
                <li>
                    <strong>1</strong>
                    <span>{{ __('orchestrator.admin.workflow_1') }}</span>
                </li>
                <li>
                    <strong>2</strong>
                    <span>{{ __('orchestrator.admin.workflow_2') }}</span>
                </li>
                <li>
                    <strong>3</strong>
                    <span>{{ __('orchestrator.admin.workflow_3') }}</span>
                </li>
                <li>
                    <strong>4</strong>
                    <span>{{ __('orchestrator.admin.workflow_4') }}</span>
                </li>
            </ol>
        </section>
    </div>

    <section class="cms-panel">
        <div class="orchestrator-panel-heading">
            <div>
                <span class="admin-eyebrow">{{ __('orchestrator.admin.plans_kicker') }}</span>
                <h2>{{ __('orchestrator.admin.plans') }}</h2>
            </div>
        </div>

        <div class="cms-table-wrap">
            <table class="cms-table orchestrator-plan-table">
                <thead>
                    <tr>
                        <th>{{ __('orchestrator.admin.week') }}</th>
                        <th>{{ __('orchestrator.admin.items') }}</th>
                        <th>{{ __('orchestrator.admin.provider') }}</th>
                        <th>{{ __('orchestrator.admin.plan_status') }}</th>
                        <th>{{ __('orchestrator.admin.created_by') }}</th>
                        <th class="cms-actions-cell">{{ __('orchestrator.admin.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($plans as $plan)
                        <tr>
                            <td>
                                <strong>
                                    {{ $plan->week_start->format('d.m.Y') }}
                                    —
                                    {{ $plan->week_end->format('d.m.Y') }}
                                </strong>
                            </td>
                            <td>{{ $plan->items_count }}</td>
                            <td>
                                {{ strtoupper($plan->provider) }}
                                <div class="catalog-muted">{{ $plan->model }}</div>
                            </td>
                            <td>
                                <span class="orchestrator-plan-status status-{{ $plan->status->value }}">
                                    {{ __('orchestrator.plan_statuses.' . $plan->status->value) }}
                                </span>
                            </td>
                            <td>{{ $plan->creator?->name ?? '—' }}</td>
                            <td class="cms-actions-cell">
                                <a
                                    class="cms-action-button"
                                    href="{{ route('admin.orchestrator.plans.show', $plan) }}"
                                >
                                    {{ __('orchestrator.admin.open') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="cms-empty">
                                {{ __('orchestrator.admin.no_plans') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</section>
@endsection
