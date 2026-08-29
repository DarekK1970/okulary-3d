@extends('admin.layout')

@section('title', __('orchestrator.admin.plan_title') . ' — ' . $plan->week_start->format('d.m.Y'))
@section('page_heading', __('orchestrator.admin.plan_title'))

@section('content')
<section class="admin-orchestrator-page">
    <div class="cms-page-heading">
        <div>
            <span class="admin-eyebrow">{{ __('orchestrator.admin.plan_kicker') }}</span>
            <h1>
                {{ $plan->week_start->format('d.m.Y') }}
                —
                {{ $plan->week_end->format('d.m.Y') }}
            </h1>
            <p>
                {{ strtoupper($plan->provider) }}
                · {{ $plan->model }}
                ·
                <span class="orchestrator-plan-status status-{{ $plan->status->value }}">
                    {{ __('orchestrator.plan_statuses.' . $plan->status->value) }}
                </span>
            </p>
        </div>

        <a
            class="cms-secondary-button"
            href="{{ route('admin.orchestrator.index') }}"
        >
            ← {{ __('orchestrator.admin.back') }}
        </a>
    </div>

    @if ($errors->has('orchestrator'))
        <div class="orchestrator-alert orchestrator-alert-error">
            {{ $errors->first('orchestrator') }}
        </div>
    @endif

    @if ($plan->editorial_summary)
        <section class="cms-panel orchestrator-summary-panel">
            <span>{{ __('orchestrator.admin.editorial_summary') }}</span>
            <p>{{ $plan->editorial_summary }}</p>
        </section>
    @endif

    @if ($plan->status === \App\Enums\OrchestratorPlanStatus::Draft)
        <section class="cms-panel orchestrator-approval-panel">
            <div>
                <strong>{{ __('orchestrator.admin.approval_title') }}</strong>
                <p>{{ __('orchestrator.admin.approval_help') }}</p>
            </div>

            <div class="orchestrator-approval-actions">
                <form
                    method="post"
                    action="{{ route('admin.orchestrator.plans.approve', $plan) }}"
                >
                    @csrf
                    @method('PATCH')

                    <button class="cms-primary-button" type="submit">
                        {{ __('orchestrator.admin.approve_plan') }}
                    </button>
                </form>

                <form
                    method="post"
                    action="{{ route('admin.orchestrator.plans.destroy', $plan) }}"
                    onsubmit="return confirm('{{ __('orchestrator.admin.delete_confirm') }}')"
                >
                    @csrf
                    @method('DELETE')

                    <button class="orchestrator-danger-button" type="submit">
                        {{ __('orchestrator.admin.delete_plan') }}
                    </button>
                </form>
            </div>
        </section>
    @elseif ($plan->approved_at)
        <div class="orchestrator-approved-note">
            {{ __('orchestrator.admin.approved_by') }}
            <strong>{{ $plan->approver?->name ?? '—' }}</strong>
            · {{ $plan->approved_at->format('d.m.Y H:i') }}
        </div>
    @endif

    <div class="orchestrator-items">
        @foreach ($plan->items as $item)
            <article class="orchestrator-item-card">
                <div class="orchestrator-item-number">
                    {{ $item->position }}
                </div>

                <div class="orchestrator-item-main">
                    <div class="orchestrator-item-heading">
                        <div>
                            <span>
                                {{ $item->planned_for?->format('d.m.Y H:i') }}
                                @if ($item->suggested_section)
                                    · {{ __('discovery.sections.' . $item->suggested_section) }}
                                @endif
                            </span>

                            <h2>{{ $item->planned_title }}</h2>
                        </div>

                        <span class="orchestrator-item-status status-{{ $item->status->value }}">
                            {{ __('orchestrator.item_statuses.' . $item->status->value) }}
                        </span>
                    </div>

                    @if ($item->editorial_angle)
                        <div class="orchestrator-item-block">
                            <strong>{{ __('orchestrator.admin.angle') }}</strong>
                            <p>{{ $item->editorial_angle }}</p>
                        </div>
                    @endif

                    @if ($item->rationale)
                        <div class="orchestrator-item-block">
                            <strong>{{ __('orchestrator.admin.rationale') }}</strong>
                            <p>{{ $item->rationale }}</p>
                        </div>
                    @endif

                    <div class="orchestrator-research-package">
                        <div>
                            <span>{{ __('orchestrator.admin.discovery_candidate') }}</span>
                            <a href="{{ route('admin.discovery.show', $item->candidate) }}">
                                {{ $item->candidate->title }}
                            </a>
                        </div>

                        <div>
                            <span>{{ __('orchestrator.admin.scores') }}</span>
                            <strong>
                                R {{ $item->candidate->relevance_score }}
                                · N {{ $item->candidate->novelty_score }}
                                · C {{ $item->candidate->confidence_score }}
                            </strong>
                        </div>

                        <div>
                            <span>{{ __('orchestrator.admin.sources') }}</span>
                            <strong>
                                {{ $item->candidate->sources->count() }}
                                /
                                {{ $item->candidate->sources->pluck('domain')->unique()->count() }}
                                {{ __('orchestrator.admin.domains') }}
                            </strong>
                        </div>
                    </div>

                    <div class="orchestrator-item-actions">
                        @if ($item->article)
                            <a
                                class="cms-primary-button"
                                href="{{ route('admin.articles.edit', $item->article) }}"
                            >
                                {{ __('orchestrator.admin.edit_article') }}
                            </a>
                        @elseif ($plan->status === \App\Enums\OrchestratorPlanStatus::Approved)
                            <form
                                method="post"
                                action="{{ route('admin.orchestrator.items.draft', $item) }}"
                            >
                                @csrf

                                <button class="cms-primary-button" type="submit">
                                    {{ __('orchestrator.admin.generate_draft') }}
                                </button>
                            </form>
                        @else
                            <span class="orchestrator-waiting-approval">
                                {{ __('orchestrator.admin.waiting_approval') }}
                            </span>
                        @endif
                    </div>
                </div>
            </article>
        @endforeach
    </div>

    <div class="orchestrator-publication-warning">
        <strong>{{ __('orchestrator.admin.no_autopublish_title') }}</strong>
        <p>{{ __('orchestrator.admin.no_autopublish_text') }}</p>
    </div>
</section>
@endsection
