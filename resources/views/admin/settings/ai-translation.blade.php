@extends('admin.layout')

@section('title', __('ai_translator.settings.title') . ' — ' . __('admin.title'))
@section('page_heading', __('ai_translator.settings.title'))

@section('content')
<section class="ai-translator-settings-page">
    <div class="cms-page-heading">
        <div>
            <span class="admin-eyebrow">{{ __('ai_translator.settings.kicker') }}</span>
            <h1>{{ __('ai_translator.settings.title') }}</h1>
            <p>{{ __('ai_translator.settings.description') }}</p>
        </div>

        <a
            class="cms-secondary-button"
            href="{{ route('admin.translations') }}"
        >
            ← {{ __('ai_translator.settings.back') }}
        </a>
    </div>

    <form
        class="ai-settings-form"
        method="post"
        action="{{ route('admin.settings.ai-translation.update') }}"
    >
        @csrf
        @method('PUT')

        <section class="cms-panel">
            <h2>{{ __('ai_translator.settings.general') }}</h2>

            <div class="ai-settings-grid">
                <label class="ai-settings-toggle">
                    <input
                        type="checkbox"
                        name="enabled"
                        value="1"
                        @checked(old('enabled', $settings->enabled()))
                    >
                    <span>
                        <strong>{{ __('ai_translator.settings.enabled') }}</strong>
                        {{ __('ai_translator.settings.enabled_help') }}
                    </span>
                </label>

                <div class="cms-field">
                    <label for="ai-provider">{{ __('ai_translator.settings.provider') }}</label>
                    <select id="ai-provider" name="provider" required>
                        <option value="openai" @selected(old('provider', $settings->provider()) === 'openai')>
                            OpenAI
                        </option>
                        <option value="gemini" @selected(old('provider', $settings->provider()) === 'gemini')>
                            Google Gemini
                        </option>
                    </select>
                </div>

                <div class="cms-field">
                    <label for="ai-timeout">{{ __('ai_translator.settings.timeout') }}</label>
                    <input
                        id="ai-timeout"
                        type="number"
                        name="timeout"
                        min="10"
                        max="180"
                        value="{{ old('timeout', $settings->timeout()) }}"
                        required
                    >
                </div>
            </div>
        </section>

        <div class="ai-provider-settings-grid">
            <section class="cms-panel ai-provider-card">
                <div class="ai-provider-card-heading">
                    <strong>OpenAI</strong>
                    <span>Responses API</span>
                </div>

                <div class="cms-field">
                    <label for="openai-model">{{ __('ai_translator.settings.model') }}</label>
                    <input
                        id="openai-model"
                        type="text"
                        name="openai_model"
                        maxlength="120"
                        value="{{ old('openai_model', $settings->model('openai')) }}"
                        required
                    >
                </div>

                <div class="cms-field">
                    <label for="openai-key">API key</label>
                    <input
                        id="openai-key"
                        type="password"
                        name="openai_api_key"
                        maxlength="500"
                        autocomplete="new-password"
                        placeholder="{{ $openAiKeyMasked ?: __('ai_translator.settings.key_placeholder') }}"
                    >
                    <small>{{ __('ai_translator.settings.secret_help') }}</small>
                </div>

                @if ($openAiKeyMasked)
                    <label class="ai-settings-clear">
                        <input type="checkbox" name="clear_openai_api_key" value="1">
                        <span>{{ __('ai_translator.settings.clear_key') }}</span>
                    </label>
                @endif
            </section>

            <section class="cms-panel ai-provider-card">
                <div class="ai-provider-card-heading">
                    <strong>Google Gemini</strong>
                    <span>GenerateContent API</span>
                </div>

                <div class="cms-field">
                    <label for="gemini-model">{{ __('ai_translator.settings.model') }}</label>
                    <input
                        id="gemini-model"
                        type="text"
                        name="gemini_model"
                        maxlength="120"
                        value="{{ old('gemini_model', $settings->model('gemini')) }}"
                        required
                    >
                </div>

                <div class="cms-field">
                    <label for="gemini-key">API key</label>
                    <input
                        id="gemini-key"
                        type="password"
                        name="gemini_api_key"
                        maxlength="500"
                        autocomplete="new-password"
                        placeholder="{{ $geminiKeyMasked ?: __('ai_translator.settings.key_placeholder') }}"
                    >
                    <small>{{ __('ai_translator.settings.secret_help') }}</small>
                </div>

                @if ($geminiKeyMasked)
                    <label class="ai-settings-clear">
                        <input type="checkbox" name="clear_gemini_api_key" value="1">
                        <span>{{ __('ai_translator.settings.clear_key') }}</span>
                    </label>
                @endif
            </section>
        </div>

        <section class="cms-panel">
            <h2>{{ __('ai_translator.settings.glossary') }}</h2>
            <p class="ai-settings-note">{{ __('ai_translator.settings.glossary_help') }}</p>

            <textarea
                class="ai-glossary"
                name="glossary"
                rows="12"
                maxlength="20000"
                placeholder="{{ __('ai_translator.settings.glossary_placeholder') }}"
            >{{ old('glossary', $settings->glossary()) }}</textarea>
        </section>

        <div class="ai-settings-submit">
            <button class="cms-primary-button" type="submit">
                {{ __('ai_translator.settings.save') }}
            </button>
        </div>
    </form>
</section>
@endsection
