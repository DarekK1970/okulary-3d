@extends('admin.layout')

@section('title', __('ai_translator.title') . ' — ' . __('admin.title'))
@section('page_heading', __('ai_translator.title'))

@section('content')
<section class="ai-translator-page">
    <div class="cms-page-heading">
        <div>
            <span class="admin-eyebrow">{{ __('ai_translator.kicker') }}</span>
            <h1>{{ __('ai_translator.title') }}</h1>
            <p>{{ __('ai_translator.description') }}</p>
        </div>

        @if (auth()->user()->role === \App\Models\User::ROLE_SUPER_ADMIN)
            <a
                class="cms-secondary-button"
                href="{{ route('admin.settings.ai-translation') }}"
            >
                ⚙ {{ __('ai_translator.settings.open') }}
            </a>
        @endif
    </div>

    <div class="ai-translator-status-grid">
        <article class="ai-translator-status-card">
            <span>{{ __('ai_translator.status.engine') }}</span>
            <strong class="{{ $settings->configured() ? 'is-good' : 'is-bad' }}">
                {{ $settings->configured() ? __('ai_translator.status.ready') : __('ai_translator.status.not_ready') }}
            </strong>
        </article>

        <article class="ai-translator-status-card">
            <span>{{ __('ai_translator.status.provider') }}</span>
            <strong>{{ strtoupper($settings->provider()) }}</strong>
        </article>

        <article class="ai-translator-status-card">
            <span>{{ __('ai_translator.status.model') }}</span>
            <strong>{{ $settings->model() }}</strong>
        </article>

        <article class="ai-translator-status-card">
            <span>{{ __('ai_translator.status.workflow') }}</span>
            <strong>{{ __('ai_translator.status.draft_only') }}</strong>
        </article>
    </div>

    @if (! $settings->configured())
        <div class="ai-translator-warning">
            <strong>{{ __('ai_translator.not_configured.title') }}</strong>
            <p>{{ __('ai_translator.not_configured.text') }}</p>
        </div>
    @endif

    @if ($errors->has('translation'))
        <div class="ai-translator-error">
            {{ $errors->first('translation') }}
        </div>
    @endif

    <nav class="ai-translator-tabs" aria-label="{{ __('ai_translator.content_types') }}">
        @foreach ($allowedTypes as $allowedType)
            <a
                class="{{ $type === $allowedType ? 'is-active' : '' }}"
                href="{{ route('admin.translations', ['type' => $allowedType]) }}"
            >
                {{ __('ai_translator.types.' . $allowedType) }}
            </a>
        @endforeach
    </nav>

    <div class="cms-table-wrap">
        <table class="cms-table ai-translator-table">
            <thead>
                <tr>
                    <th>{{ __('ai_translator.table.content') }}</th>
                    <th>{{ __('ai_translator.table.direction') }}</th>
                    <th>{{ __('ai_translator.table.target_status') }}</th>
                    <th>{{ __('ai_translator.table.rule') }}</th>
                    <th class="cms-actions-cell">{{ __('ai_translator.table.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $row)
                    <tr>
                        <td>
                            <strong>{{ $row['label'] }}</strong>
                            <div class="catalog-muted">
                                #{{ $row['id'] }} · {{ __('ai_translator.types.' . $row['type']) }}
                            </div>
                        </td>

                        <td>
                            <span class="ai-lang-chip">{{ strtoupper($row['source_locale']) }}</span>
                            <span class="ai-direction-arrow">→</span>
                            <span class="ai-lang-chip">{{ strtoupper($row['target_locale']) }}</span>
                        </td>

                        <td>
                            <span class="ai-translation-status ai-status-{{ $row['target_status'] }}">
                                {{ __('ai_translator.target_statuses.' . $row['target_status']) }}
                            </span>
                        </td>

                        <td>
                            @if ($row['target_ready'] === '1')
                                <span class="ai-rule-note is-locked">
                                    {{ __('ai_translator.table.ready_locked') }}
                                </span>
                            @else
                                <span class="ai-rule-note">
                                    {{ __('ai_translator.table.saved_as_draft') }}
                                </span>
                            @endif
                        </td>

                        <td class="cms-actions-cell">
                            <a
                                class="cms-action-button"
                                href="{{ $row['edit_url'] }}"
                            >
                                {{ __('ai_translator.table.edit') }}
                            </a>

                            @if ($row['target_ready'] !== '1')
                                <form
                                    method="post"
                                    action="{{ route('admin.translations.translate', [
                                        'type' => $row['type'],
                                        'id' => $row['id']
                                    ]) }}"
                                >
                                    @csrf

                                    <button
                                        class="cms-primary-button ai-translate-button"
                                        type="submit"
                                        @disabled(! $settings->configured())
                                    >
                                        ✦ {{ $row['target_status'] === 'missing' ? __('ai_translator.table.translate') : __('ai_translator.table.regenerate') }}
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="cms-empty">
                            {{ __('ai_translator.table.empty') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($items->hasPages())
        <div class="cms-pagination">
            {{ $items->links() }}
        </div>
    @endif

    <section class="cms-panel ai-translation-runs">
        <div class="ai-runs-heading">
            <div>
                <span class="admin-eyebrow">{{ __('ai_translator.runs.kicker') }}</span>
                <h2>{{ __('ai_translator.runs.title') }}</h2>
            </div>
            <p>{{ __('ai_translator.runs.description') }}</p>
        </div>

        <div class="cms-table-wrap">
            <table class="cms-table">
                <thead>
                    <tr>
                        <th>{{ __('ai_translator.runs.date') }}</th>
                        <th>{{ __('ai_translator.runs.content') }}</th>
                        <th>{{ __('ai_translator.runs.provider') }}</th>
                        <th>{{ __('ai_translator.runs.tokens') }}</th>
                        <th>{{ __('ai_translator.runs.user') }}</th>
                        <th>{{ __('ai_translator.runs.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentRuns as $run)
                        <tr>
                            <td>{{ $run->created_at->format('d.m.Y H:i') }}</td>
                            <td>
                                {{ __('ai_translator.types.' . $run->content_type) }} #{{ $run->content_id }}
                                <div class="catalog-muted">
                                    {{ strtoupper($run->source_locale) }} → {{ strtoupper($run->target_locale) }}
                                </div>
                            </td>
                            <td>
                                <strong>{{ strtoupper($run->provider) }}</strong>
                                <div class="catalog-muted">{{ $run->model }}</div>
                            </td>
                            <td>
                                {{ $run->total_tokens ?? '—' }}
                                @if ($run->input_tokens || $run->output_tokens)
                                    <div class="catalog-muted">
                                        {{ $run->input_tokens ?? '—' }} / {{ $run->output_tokens ?? '—' }}
                                    </div>
                                @endif
                            </td>
                            <td>{{ $run->user?->name ?: '—' }}</td>
                            <td>
                                <span class="ai-run-status ai-run-{{ $run->status }}">
                                    {{ __('ai_translator.run_statuses.' . $run->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="cms-empty">
                                {{ __('ai_translator.runs.empty') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</section>
@endsection
