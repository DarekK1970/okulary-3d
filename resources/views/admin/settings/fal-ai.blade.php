@extends('admin.layout')

@section('title', __('fal_ai.title').' — '.__('admin.title'))
@section('page_heading', __('fal_ai.title'))

@section('content')
<section class="fal-settings-page">
    <div class="cms-page-heading">
        <div>
            <span class="admin-eyebrow">{{ __('fal_ai.kicker') }}</span>
            <h1>{{ __('fal_ai.title') }}</h1>
            <p>{{ __('fal_ai.description') }}</p>
        </div>
        <span class="fal-status {{ $settings->configured() ? 'is-ready' : '' }}">
            <span aria-hidden="true">{{ $settings->configured() ? '●' : '○' }}</span>
            {{ $settings->configured() ? __('fal_ai.ready') : __('fal_ai.not_ready') }}
        </span>
    </div>

    <form class="fal-settings-form" method="post" action="{{ route('admin.settings.fal-ai.update') }}">
        @csrf
        @method('PUT')

        <section class="cms-panel fal-card">
            <div class="fal-card-heading"><span class="fal-icon">⌁</span><div><h2>{{ __('fal_ai.connection.title') }}</h2><p>{{ __('fal_ai.connection.description') }}</p></div></div>
            <div class="fal-grid">
                <label class="fal-toggle"><input type="checkbox" name="enabled" value="1" @checked(old('enabled', $settings->enabled()))><span><strong>{{ __('fal_ai.connection.enabled') }}</strong><small>{{ __('fal_ai.connection.enabled_help') }}</small></span></label>
                <div class="cms-field"><label for="fal-timeout">{{ __('fal_ai.connection.timeout') }}</label><input id="fal-timeout" type="number" name="timeout" min="10" max="180" value="{{ old('timeout', $settings->timeout()) }}" required></div>
                <div class="cms-field fal-span"><label for="fal-key">{{ __('fal_ai.connection.api_key') }}</label><input id="fal-key" type="password" name="api_key" maxlength="500" autocomplete="new-password" placeholder="{{ $apiKeyMasked ?: __('fal_ai.connection.key_placeholder') }}"><small>{{ __('fal_ai.connection.secret_help') }}</small></div>
                @if ($apiKeyMasked)<label class="fal-clear fal-span"><input type="checkbox" name="clear_api_key" value="1"><span>{{ __('fal_ai.connection.clear_key') }}</span></label>@endif
            </div>
        </section>

        <section class="cms-panel fal-card">
            <div class="fal-card-heading"><span class="fal-icon">▶</span><div><h2>{{ __('fal_ai.seedance.title') }}</h2><p>{{ __('fal_ai.seedance.description') }}</p></div></div>
            <div class="fal-grid">
                <div class="cms-field fal-span"><label for="seedance-model">{{ __('fal_ai.seedance.model') }}</label><input id="seedance-model" name="seedance_model" maxlength="160" value="{{ old('seedance_model', $settings->seedanceModel()) }}" required></div>
                <div class="cms-field"><label for="resolution">{{ __('fal_ai.seedance.resolution') }}</label><select id="resolution" name="resolution"><option value="480p" @selected(old('resolution', $settings->resolution()) === '480p')>480p</option><option value="720p" @selected(old('resolution', $settings->resolution()) === '720p')>720p</option></select></div>
                <div class="cms-field"><label for="duration">{{ __('fal_ai.seedance.duration') }}</label><input id="duration" type="number" name="duration" min="4" max="30" value="{{ old('duration', $settings->duration()) }}" required></div>
                <label class="fal-toggle fal-span"><input type="checkbox" name="generate_audio" value="1" @checked(old('generate_audio', $settings->generateAudio()))><span><strong>{{ __('fal_ai.seedance.audio') }}</strong><small>{{ __('fal_ai.seedance.audio_help') }}</small></span></label>
            </div>
        </section>

        <section class="cms-panel fal-card">
            <div class="fal-card-heading"><span class="fal-icon">↗</span><div><h2>{{ __('fal_ai.upscale.title') }}</h2><p>{{ __('fal_ai.upscale.description') }}</p></div></div>
            <div class="fal-grid">
                <label class="fal-toggle fal-span"><input type="checkbox" name="upscaling_enabled" value="1" @checked(old('upscaling_enabled', $settings->upscalingEnabled()))><span><strong>{{ __('fal_ai.upscale.enabled') }}</strong><small>{{ __('fal_ai.upscale.enabled_help') }}</small></span></label>
                <div class="cms-field"><label for="upscaler-model">{{ __('fal_ai.upscale.model') }}</label><input id="upscaler-model" name="upscaler_model" maxlength="160" value="{{ old('upscaler_model', $settings->upscalerModel()) }}" required></div>
                <div class="cms-field"><label for="upscale-resolution">{{ __('fal_ai.upscale.resolution') }}</label><select id="upscale-resolution" name="upscale_resolution">@foreach (['1080p', '2k', '4k', '6k', '8k'] as $value)<option value="{{ $value }}" @selected(old('upscale_resolution', $settings->upscaleResolution()) === $value)>{{ strtoupper($value) }}</option>@endforeach</select></div>
            </div>
        </section>

        <section class="cms-panel fal-card">
            <div class="fal-card-heading"><span class="fal-icon">$</span><div><h2>{{ __('fal_ai.cost.title') }}</h2><p>{{ __('fal_ai.cost.description') }}</p></div></div>
            <div class="fal-grid">
                <div class="cms-field"><label for="maximum-job-cost">{{ __('fal_ai.cost.maximum_job') }}</label><input id="maximum-job-cost" type="number" step="0.01" min="0.01" max="1000" name="maximum_job_cost_usd" value="{{ old('maximum_job_cost_usd', $settings->maximumJobCost()) }}" required></div>
                <div class="cms-field"><label for="daily-budget">{{ __('fal_ai.cost.daily_budget') }}</label><input id="daily-budget" type="number" step="0.01" min="0.01" max="10000" name="daily_budget_usd" value="{{ old('daily_budget_usd', $settings->dailyBudget()) }}" required></div>
            </div>
            <p class="fal-note">{{ __('fal_ai.cost.note') }}</p>
        </section>

        <div class="fal-actions"><button class="cms-primary-button" type="submit">{{ __('fal_ai.save') }}</button></div>
    </form>

    <section class="cms-panel fal-card fal-test-card">
        <div><strong>{{ __('fal_ai.test.title') }}</strong><p>{{ __('fal_ai.test.description') }}</p></div>
        <form method="post" action="{{ route('admin.settings.fal-ai.test') }}">@csrf<button class="cms-secondary-button" type="submit" @disabled(blank($settings->apiKey()))>{{ __('fal_ai.test.button') }}</button></form>
    </section>
</section>
@endsection
