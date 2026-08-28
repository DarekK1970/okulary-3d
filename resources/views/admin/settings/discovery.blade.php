@extends('admin.layout')

@section('title', __('discovery.settings.title') . ' — ' . __('admin.title'))
@section('page_heading', __('discovery.settings.title'))

@section('content')
<section class="discovery-page discovery-settings-page">
    <div class="cms-page-heading discovery-heading">
        <div>
            <span class="admin-eyebrow">{{ __('discovery.settings.kicker') }}</span>
            <h1>{{ __('discovery.settings.title') }}</h1>
            <p>{{ __('discovery.settings.description') }}</p>
        </div>

        <a
            class="cms-secondary-button"
            href="{{ route('admin.discovery.index') }}"
        >
            ← {{ __('discovery.settings.back') }}
        </a>
    </div>

    <div class="discovery-shared-keys">
        <div>
            <span>OpenAI API</span>
            <strong>{{ $openAiKeyMasked !== '' ? $openAiKeyMasked : __('discovery.settings.no_key') }}</strong>
        </div>
        <div>
            <span>Gemini API</span>
            <strong>{{ $geminiKeyMasked !== '' ? $geminiKeyMasked : __('discovery.settings.no_key') }}</strong>
        </div>
        <a href="{{ route('admin.settings.ai-translation') }}">
            {{ __('discovery.settings.manage_shared_keys') }} →
        </a>
    </div>

    <form
        method="post"
        action="{{ route('admin.settings.discovery.update') }}"
        class="discovery-settings-form"
    >
        @csrf
        @method('PUT')

        <div class="discovery-settings-grid">
            <main>
                <section class="cms-panel">
                    <h2>{{ __('discovery.settings.engine') }}</h2>

                    <label class="discovery-toggle">
                        <input
                            type="checkbox"
                            name="enabled"
                            value="1"
                            @checked(old('enabled', $settings->enabled()))
                        >
                        <span>
                            <strong>{{ __('discovery.settings.enabled') }}</strong>
                            {{ __('discovery.settings.enabled_help') }}
                        </span>
                    </label>

                    <div class="discovery-settings-fields">
                        <div class="cms-field">
                            <label for="discovery-provider">{{ __('discovery.settings.provider') }}</label>
                            <select id="discovery-provider" name="provider" required>
                                <option value="openai" @selected(old('provider', $settings->provider()) === 'openai')>OpenAI Web Search</option>
                                <option value="gemini" @selected(old('provider', $settings->provider()) === 'gemini')>Gemini + Google Search</option>
                            </select>
                        </div>

                        <div class="cms-field">
                            <label for="discovery-openai-model">OpenAI model</label>
                            <input
                                id="discovery-openai-model"
                                type="text"
                                name="openai_model"
                                maxlength="120"
                                value="{{ old('openai_model', $settings->model('openai')) }}"
                                required
                            >
                        </div>

                        <div class="cms-field">
                            <label for="discovery-gemini-model">Gemini model</label>
                            <input
                                id="discovery-gemini-model"
                                type="text"
                                name="gemini_model"
                                maxlength="120"
                                value="{{ old('gemini_model', $settings->model('gemini')) }}"
                                required
                            >
                        </div>

                        <div class="cms-field">
                            <label for="discovery-timeout">{{ __('discovery.settings.timeout') }}</label>
                            <input
                                id="discovery-timeout"
                                type="number"
                                name="timeout"
                                min="20"
                                max="300"
                                value="{{ old('timeout', $settings->timeout()) }}"
                                required
                            >
                        </div>
                    </div>
                </section>

                <section class="cms-panel">
                    <h2>{{ __('discovery.settings.editorial_rules') }}</h2>

                    <div class="discovery-settings-fields">
                        <div class="cms-field">
                            <label for="freshness-days">{{ __('discovery.settings.freshness') }}</label>
                            <input
                                id="freshness-days"
                                type="number"
                                name="freshness_days"
                                min="1"
                                max="365"
                                value="{{ old('freshness_days', $settings->freshnessDays()) }}"
                                required
                            >
                        </div>

                        <div class="cms-field">
                            <label for="candidate-limit">{{ __('discovery.settings.candidate_limit') }}</label>
                            <input
                                id="candidate-limit"
                                type="number"
                                name="candidate_limit"
                                min="1"
                                max="25"
                                value="{{ old('candidate_limit', $settings->candidateLimit()) }}"
                                required
                            >
                        </div>

                        <div class="cms-field">
                            <label for="min-sources">{{ __('discovery.settings.min_sources') }}</label>
                            <input
                                id="min-sources"
                                type="number"
                                name="min_sources"
                                min="1"
                                max="6"
                                value="{{ old('min_sources', $settings->minSources()) }}"
                                required
                            >
                        </div>

                        <div class="cms-field">
                            <label for="min-domains">{{ __('discovery.settings.min_domains') }}</label>
                            <input
                                id="min-domains"
                                type="number"
                                name="min_domains"
                                min="1"
                                max="6"
                                value="{{ old('min_domains', $settings->minDomains()) }}"
                                required
                            >
                        </div>
                    </div>

                    <label class="discovery-toggle">
                        <input
                            type="checkbox"
                            name="exclude_polish_sources"
                            value="1"
                            @checked(old('exclude_polish_sources', $settings->excludePolishSources()))
                        >
                        <span>
                            <strong>{{ __('discovery.settings.non_polish') }}</strong>
                            {{ __('discovery.settings.non_polish_help') }}
                        </span>
                    </label>
                </section>

                <section class="cms-panel">
                    <h2>{{ __('discovery.settings.topics') }}</h2>
                    <p class="discovery-panel-note">{{ __('discovery.settings.topics_help') }}</p>
                    <textarea
                        class="discovery-large-textarea"
                        name="topics"
                        rows="10"
                        maxlength="20000"
                        required
                    >{{ old('topics', implode("\n", $settings->topics())) }}</textarea>
                </section>
            </main>

            <aside>
                <section class="cms-panel">
                    <h2>{{ __('discovery.settings.domains') }}</h2>

                    <div class="cms-field">
                        <label for="preferred-domains">{{ __('discovery.settings.preferred_domains') }}</label>
                        <textarea
                            id="preferred-domains"
                            name="preferred_domains"
                            rows="7"
                            maxlength="20000"
                            placeholder="example.org&#10;museum.example"
                        >{{ old('preferred_domains', implode("\n", $settings->preferredDomains())) }}</textarea>
                        <small>{{ __('discovery.settings.one_per_line') }}</small>
                    </div>

                    <div class="cms-field">
                        <label for="excluded-domains">{{ __('discovery.settings.excluded_domains') }}</label>
                        <textarea
                            id="excluded-domains"
                            name="excluded_domains"
                            rows="7"
                            maxlength="20000"
                            placeholder="spam.example&#10;aggregator.example"
                        >{{ old('excluded_domains', implode("\n", $settings->excludedDomains())) }}</textarea>
                        <small>{{ __('discovery.settings.one_per_line') }}</small>
                    </div>
                </section>

                <section class="cms-panel">
                    <h2>{{ __('discovery.settings.extra_instructions') }}</h2>
                    <p class="discovery-panel-note">{{ __('discovery.settings.extra_instructions_help') }}</p>
                    <textarea
                        name="extra_instructions"
                        rows="12"
                        maxlength="30000"
                    >{{ old('extra_instructions', $settings->extraInstructions()) }}</textarea>
                </section>
            </aside>
        </div>

        <div class="discovery-settings-submit">
            <button class="cms-primary-button" type="submit">
                {{ __('discovery.settings.save') }}
            </button>
        </div>
    </form>
</section>
@endsection
