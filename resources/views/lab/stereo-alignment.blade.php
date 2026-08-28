@extends('layouts.app')

@section('title', __('lab.alignment.meta_title') . ' — ' . __('site.title'))
@section('meta_description', __('lab.alignment.meta_description'))

@push('head')
    @vite([
        'resources/css/lab.css',
        'resources/js/stereo-lab.js'
    ])
@endpush

@section('content')
<section class="lab-workspace-page">
    <div class="site-container">
        <nav class="lab-breadcrumbs">
            <a href="{{ route('home', ['locale' => app()->getLocale()]) }}">
                {{ __('lab.common.home') }}
            </a>
            <span>›</span>
            <a href="{{ route('lab.index', ['locale' => app()->getLocale()]) }}">
                3D LAB
            </a>
            <span>›</span>
            <span>{{ __('lab.alignment.title') }}</span>
        </nav>

        <div class="lab-workspace-heading">
            <div>
                <span class="lab-kicker">3D LAB / STEREO</span>
                <h1>{{ __('lab.alignment.title') }}</h1>
                <p>{{ __('lab.alignment.description') }}</p>
            </div>

            <div class="lab-local-badge">
                <span>✓</span>
                {{ __('lab.common.local_only') }}
            </div>
        </div>

        <div
            class="stereo-lab"
            data-stereo-lab
            data-tool="alignment"
            data-download-prefix="stereo"
            data-error-two-images="{{ __('lab.common.errors.two_images') }}"
            data-error-image="{{ __('lab.common.errors.image') }}"
            data-ready="{{ __('lab.common.ready') }}"
            data-loading="{{ __('lab.common.loading') }}"
        >
            @include('lab.partials.source-panel')

            <div class="lab-workspace-grid">
                <aside class="lab-controls-panel">
                    <section class="lab-panel">
                        <div class="lab-panel-title">
                            <span>01</span>
                            <div>
                                <h2>{{ __('lab.alignment.preview_mode_title') }}</h2>
                                <p>{{ __('lab.alignment.preview_mode_help') }}</p>
                            </div>
                        </div>

                        <div class="lab-control">
                            <label for="preview-mode">{{ __('lab.alignment.preview_mode') }}</label>
                            <select id="preview-mode" data-control="previewMode">
                                <option value="parallel">{{ __('lab.alignment.modes.parallel') }}</option>
                                <option value="cross">{{ __('lab.alignment.modes.cross') }}</option>
                                <option value="anaglyph">{{ __('lab.alignment.modes.anaglyph') }}</option>
                                <option value="overlay">{{ __('lab.alignment.modes.overlay') }}</option>
                                <option value="blink">{{ __('lab.alignment.modes.blink') }}</option>
                            </select>
                        </div>

                        <button class="lab-secondary-button" type="button" data-action="swap">
                            ⇄ {{ __('lab.common.swap') }}
                        </button>
                    </section>

                    @include('lab.partials.geometry-controls')

                    <section class="lab-panel">
                        <div class="lab-panel-title">
                            <span>03</span>
                            <div>
                                <h2>{{ __('lab.common.export.title') }}</h2>
                                <p>{{ __('lab.alignment.export_help') }}</p>
                            </div>
                        </div>

                        @include('lab.partials.export-controls')
                    </section>
                </aside>

                <main class="lab-preview-panel">
                    <div class="lab-preview-head">
                        <div>
                            <span>{{ __('lab.common.preview') }}</span>
                            <strong data-status>{{ __('lab.common.waiting') }}</strong>
                        </div>

                        <div class="lab-preview-actions">
                            <button type="button" data-action="fit">
                                {{ __('lab.common.fit') }}
                            </button>
                            <button type="button" data-action="reset">
                                {{ __('lab.common.reset') }}
                            </button>
                        </div>
                    </div>

                    <div class="lab-canvas-stage">
                        <canvas data-preview-canvas></canvas>

                        <div class="lab-canvas-empty" data-empty-state>
                            <div class="lab-empty-symbol">L/R</div>
                            <strong>{{ __('lab.common.empty_title') }}</strong>
                            <p>{{ __('lab.common.empty_text') }}</p>
                        </div>
                    </div>

                    <div class="lab-preview-foot">
                        <span data-image-info>—</span>
                        <span>{{ __('lab.alignment.preview_hint') }}</span>
                    </div>
                </main>
            </div>
        </div>
    </div>
</section>
@endsection
