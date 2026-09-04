@extends('layouts.app')

@section('title', __('lenticular_studio.meta_title').' — '.__('site.title'))
@section('meta_description', __('lenticular_studio.meta_description'))

@push('head')
    @vite(['resources/css/lab.css', 'resources/css/lenticular-studio.css'])
@endpush

@section('content')
<section class="studio-page">
    <div class="site-container">
        <nav class="lab-breadcrumbs">
            <a href="{{ route('lab.index', ['locale' => app()->getLocale()]) }}">3D LAB</a><span>›</span>
            <a href="{{ route('lab.lenticular', ['locale' => app()->getLocale()]) }}">{{ __('lab.lenticular.title') }}</a><span>›</span>
            <span>AI LENTICULAR STUDIO</span>
        </nav>

        <header class="studio-hero">
            <div>
                <span class="lab-kicker">AI LENTICULAR STUDIO</span>
                <h1>{{ __('lenticular_studio.title') }}</h1>
                <p>{{ __('lenticular_studio.description') }}</p>
            </div>
            <div class="studio-account-card">
                <span>{{ __('lenticular_studio.your_plan') }}</span>
                <strong>{{ $accessPlan === 'premium' ? __('lenticular_studio.premium_plan') : __('lenticular_studio.free_plan') }}</strong>
                <small>{{ $accessPlan === 'premium' ? __('lenticular_studio.premium_plan_help') : __('lenticular_studio.free_plan_help') }}</small>
            </div>
        </header>

        <div class="studio-progress" aria-label="{{ __('lenticular_studio.progress_label') }}">
            <div class="is-active"><span>1</span><strong>{{ __('lenticular_studio.progress.choose') }}</strong></div>
            <div><span>2</span><strong>{{ __('lenticular_studio.progress.material') }}</strong></div>
            <div><span>3</span><strong>{{ __('lenticular_studio.progress.prepare') }}</strong></div>
            <div><span>4</span><strong>{{ __('lenticular_studio.progress.print') }}</strong></div>
        </div>

        <div class="studio-section-heading">
            <div><span class="lab-kicker">{{ __('lenticular_studio.choose_kicker') }}</span><h2>{{ __('lenticular_studio.choose_title') }}</h2><p>{{ __('lenticular_studio.choose_help') }}</p></div>
            <span class="studio-api-state {{ $falReady ? 'is-ready' : '' }}"><i></i>{{ $falReady ? __('lenticular_studio.ai_ready') : __('lenticular_studio.ai_unavailable') }}</span>
        </div>

        <div class="studio-path-grid">
            <article class="studio-path-card is-available">
                <div class="studio-path-visual is-flip" aria-hidden="true">
                    <svg viewBox="0 0 120 90"><rect x="15" y="17" width="70" height="54" rx="9"/><path d="M28 58l17-18 13 13 9-8 13 13"/><path d="M91 30c13 5 18 20 11 32"/><path d="M96 62l7 1 2-7"/></svg>
                </div>
                <div class="studio-path-number">01</div><h3>{{ __('lenticular_studio.paths.flip.title') }}</h3><p>{{ __('lenticular_studio.paths.flip.description') }}</p>
                <ul><li>{{ __('lenticular_studio.paths.flip.feature_1') }}</li><li>{{ __('lenticular_studio.paths.flip.feature_2') }}</li></ul>
                <div class="studio-path-footer"><span class="studio-plan-tag is-free">{{ __('lenticular_studio.available_now') }}</span><a class="lab-primary-button" href="{{ route('lab.projects.create', ['locale' => app()->getLocale()]) }}">{{ __('lenticular_studio.start') }} →</a></div>
            </article>

            <article class="studio-path-card is-planned">
                <div class="studio-path-visual is-sequence" aria-hidden="true"><svg viewBox="0 0 120 90"><rect x="12" y="22" width="54" height="42" rx="7"/><rect x="28" y="15" width="54" height="42" rx="7"/><rect x="45" y="9" width="62" height="48" rx="7"/><path d="M56 46l14-15 11 10 8-7 12 12"/></svg></div>
                <div class="studio-path-number">02</div><h3>{{ __('lenticular_studio.paths.sequence.title') }}</h3><p>{{ __('lenticular_studio.paths.sequence.description') }}</p>
                <ul><li>{{ __('lenticular_studio.paths.sequence.feature_1') }}</li><li>{{ __('lenticular_studio.paths.sequence.feature_2') }}</li></ul>
                <div class="studio-path-footer"><span class="studio-plan-tag is-free">FREE · 12 · A5</span><button type="button" disabled>{{ __('lenticular_studio.in_preparation') }}</button></div>
            </article>

            <article class="studio-path-card is-locked">
                <div class="studio-path-visual is-pair" aria-hidden="true"><svg viewBox="0 0 120 90"><rect x="9" y="18" width="47" height="55" rx="8"/><rect x="64" y="18" width="47" height="55" rx="8"/><circle cx="34" cy="37" r="7"/><circle cx="89" cy="37" r="7"/><path d="M54 46h12m-5-5 5 5-5 5"/></svg></div>
                <div class="studio-path-number">03</div><h3>{{ __('lenticular_studio.paths.pair.title') }}</h3><p>{{ __('lenticular_studio.paths.pair.description') }}</p>
                <ul><li>{{ __('lenticular_studio.paths.pair.feature_1') }}</li><li>{{ __('lenticular_studio.paths.pair.feature_2') }}</li></ul>
                <div class="studio-path-footer"><span class="studio-plan-tag">{{ $accessPlan === 'premium' ? 'PREMIUM' : 'PRO · A4' }}</span><button type="button" disabled>@if($accessPlan !== 'premium')<span>🔒</span>@endif {{ $accessPlan === 'premium' ? __('lenticular_studio.in_preparation') : __('lenticular_studio.requires_pro') }}</button></div>
            </article>

            <article class="studio-path-card is-locked">
                <div class="studio-path-visual is-ai" aria-hidden="true"><svg viewBox="0 0 120 90"><rect x="26" y="15" width="68" height="60" rx="10"/><path d="M39 59l17-18 12 11 9-8 10 15"/><path d="M99 12v14m-7-7h14M17 27v10m-5-5h10"/></svg></div>
                <div class="studio-path-number">04</div><h3>{{ __('lenticular_studio.paths.single.title') }}</h3><p>{{ __('lenticular_studio.paths.single.description') }}</p>
                <ul><li>{{ __('lenticular_studio.paths.single.feature_1') }}</li><li>{{ __('lenticular_studio.paths.single.feature_2') }}</li></ul>
                <div class="studio-path-footer"><span class="studio-plan-tag">{{ $accessPlan === 'premium' ? 'PREMIUM' : 'PRO · A4' }}</span><button type="button" disabled>@if($accessPlan !== 'premium')<span>🔒</span>@endif {{ $accessPlan === 'premium' ? __('lenticular_studio.in_preparation') : __('lenticular_studio.requires_pro') }}</button></div>
            </article>
        </div>

        <aside class="studio-cost-note"><span>✓</span><div><strong>{{ __('lenticular_studio.cost_title') }}</strong><p>{{ __('lenticular_studio.cost_help') }}</p></div></aside>
    </div>
</section>
@endsection
