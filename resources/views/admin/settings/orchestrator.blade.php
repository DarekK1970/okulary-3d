@extends('admin.layout')

@section('title', __('orchestrator.settings.title') . ' — ' . __('admin.title'))
@section('page_heading', __('orchestrator.settings.title'))

@section('content')
<section class="admin-orchestrator-page">
    <div class="cms-page-heading">
        <div>
            <span class="admin-eyebrow">{{ __('orchestrator.settings.kicker') }}</span>
            <h1>{{ __('orchestrator.settings.title') }}</h1>
            <p>{{ __('orchestrator.settings.description') }}</p>
        </div>

        <a
            class="cms-secondary-button"
            href="{{ route('admin.orchestrator.index') }}"
        >
            ← {{ __('orchestrator.settings.back') }}
        </a>
    </div>

    <div class="orchestrator-shared-keys">
        <div>
            <span>OpenAI</span>
            <strong>
                {{ $aiSettings->maskedSecret('openai') ?: __('orchestrator.settings.no_key') }}
            </strong>
        </div>

        <div>
            <span>Gemini</span>
            <strong>
                {{ $aiSettings->maskedSecret('gemini') ?: __('orchestrator.settings.no_key') }}
            </strong>
        </div>

        <a href="{{ route('admin.settings.ai-translation') }}">
            {{ __('orchestrator.settings.manage_keys') }}
        </a>
    </div>

    <form
        class="orchestrator-settings-form"
        method="post"
        action="{{ route('admin.settings.orchestrator.update') }}"
    >
        @csrf
        @method('PUT')

        <section class="cms-panel">
            <h2>{{ __('orchestrator.settings.engine') }}</h2>

            <label class="orchestrator-check">
                <input
                    type="checkbox"
                    name="enabled"
                    value="1"
                    @checked(old('enabled', $settings->enabled()))
                >
                <span>
                    <strong>{{ __('orchestrator.settings.enabled') }}</strong>
                    {{ __('orchestrator.settings.enabled_help') }}
                </span>
            </label>

            <div class="orchestrator-settings-grid">
                <label>
                    <span>{{ __('orchestrator.settings.provider') }}</span>
                    <select name="provider" required>
                        <option value="openai" @selected(old('provider', $settings->provider()) === 'openai')>
                            OpenAI
                        </option>
                        <option value="gemini" @selected(old('provider', $settings->provider()) === 'gemini')>
                            Gemini
                        </option>
                    </select>
                </label>

                <label>
                    <span>{{ __('orchestrator.settings.timeout') }}</span>
                    <input
                        type="number"
                        name="timeout"
                        min="20"
                        max="300"
                        value="{{ old('timeout', $settings->timeout()) }}"
                        required
                    >
                </label>

                <label>
                    <span>OpenAI model</span>
                    <input
                        type="text"
                        name="openai_model"
                        maxlength="120"
                        value="{{ old('openai_model', $settings->model('openai')) }}"
                        required
                    >
                </label>

                <label>
                    <span>Gemini model</span>
                    <input
                        type="text"
                        name="gemini_model"
                        maxlength="120"
                        value="{{ old('gemini_model', $settings->model('gemini')) }}"
                        required
                    >
                </label>
            </div>
        </section>

        <section class="cms-panel">
            <h2>{{ __('orchestrator.settings.editorial') }}</h2>

            <div class="orchestrator-settings-grid">
                <label>
                    <span>{{ __('orchestrator.settings.weekly_limit') }}</span>
                    <input
                        type="number"
                        name="weekly_article_limit"
                        min="1"
                        max="7"
                        value="{{ old('weekly_article_limit', $settings->weeklyArticleLimit()) }}"
                        required
                    >
                </label>

                <label>
                    <span>{{ __('orchestrator.settings.min_relevance') }}</span>
                    <input
                        type="number"
                        name="min_relevance"
                        min="0"
                        max="100"
                        value="{{ old('min_relevance', $settings->minRelevance()) }}"
                        required
                    >
                </label>

                <label>
                    <span>{{ __('orchestrator.settings.target_words') }}</span>
                    <input
                        type="number"
                        name="target_words"
                        min="450"
                        max="2200"
                        step="50"
                        value="{{ old('target_words', $settings->targetWords()) }}"
                        required
                    >
                </label>

                <label>
                    <span>{{ __('orchestrator.settings.source_locale') }}</span>
                    <select name="source_locale" required>
                        @foreach ($supportedLocales as $locale => $data)
                            <option
                                value="{{ $locale }}"
                                @selected(old('source_locale', $settings->sourceLocale()) === $locale)
                            >
                                {{ strtoupper($locale) }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="orchestrator-settings-full">
                    <span>{{ __('orchestrator.settings.default_category') }}</span>
                    <select name="default_category_id" required>
                        @foreach ($categories as $category)
                            <option
                                value="{{ $category->id }}"
                                @selected((int) old(
                                    'default_category_id',
                                    $settings->defaultCategoryId()
                                        ?? $categories->first()?->id
                                ) === $category->id)
                            >
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    <small>{{ __('orchestrator.settings.default_category_help') }}</small>
                </label>
            </div>
        </section>

        <section class="cms-panel">
            <h2>{{ __('orchestrator.settings.schedule') }}</h2>

            <label class="orchestrator-settings-block">
                <span>{{ __('orchestrator.settings.slots') }}</span>
                <textarea
                    name="schedule_slots"
                    rows="7"
                    required
                >{{ old('schedule_slots', $settings->scheduleSlotsRaw()) }}</textarea>
                <small>{{ __('orchestrator.settings.slots_help') }}</small>
            </label>
        </section>

        <section class="cms-panel">
            <h2>{{ __('orchestrator.settings.instructions') }}</h2>

            <label class="orchestrator-settings-block">
                <span>{{ __('orchestrator.settings.extra_instructions') }}</span>
                <textarea
                    name="extra_instructions"
                    rows="8"
                    maxlength="30000"
                >{{ old('extra_instructions', $settings->extraInstructions()) }}</textarea>
                <small>{{ __('orchestrator.settings.extra_help') }}</small>
            </label>
        </section>

        <div class="orchestrator-settings-submit">
            <button class="cms-primary-button" type="submit">
                {{ __('orchestrator.settings.save') }}
            </button>
        </div>
    </form>
</section>
@endsection
