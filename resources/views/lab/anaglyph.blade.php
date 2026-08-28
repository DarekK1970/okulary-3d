@extends('layouts.app')

@section('title', __('lab.anaglyph.meta_title') . ' — ' . __('site.title'))
@section('meta_description', __('lab.anaglyph.meta_description'))

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
            <span>{{ __('lab.anaglyph.title') }}</span>
        </nav>

        <div class="lab-workspace-heading">
            <div>
                <span class="lab-kicker">3D LAB / ANAGLYPH</span>
                <h1>{{ __('lab.anaglyph.title') }}</h1>
                <p>{{ __('lab.anaglyph.description') }}</p>
            </div>

            <div class="lab-local-badge">
                <span>✓</span>
                {{ __('lab.common.local_only') }}
            </div>
        </div>

        <div
            class="stereo-lab"
            data-stereo-lab
            data-tool="anaglyph"
            data-download-prefix="anaglyph"
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
                                <h2>{{ __('lab.anaglyph.mode_title') }}</h2>
                                <p>{{ __('lab.anaglyph.mode_help') }}</p>
                            </div>
                        </div>

                        <div class="lab-control">
                            <label for="anaglyph-mode">{{ __('lab.anaglyph.mode') }}</label>
                            <select id="anaglyph-mode" data-control="anaglyphMode">
                                <option value="color">{{ __('lab.anaglyph.modes.color') }}</option>
                                <option value="half-color">{{ __('lab.anaglyph.modes.half_color') }}</option>
                                <option value="gray">{{ __('lab.anaglyph.modes.gray') }}</option>
                                <option value="optimized">{{ __('lab.anaglyph.modes.optimized') }}</option>
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
                                <p>{{ __('lab.common.export.help') }}</p>
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
                            <div class="lab-empty-symbol">3D</div>
                            <strong>{{ __('lab.common.empty_title') }}</strong>
                            <p>{{ __('lab.common.empty_text') }}</p>
                        </div>
                    </div>

                    <div class="lab-preview-foot">
                        <span data-image-info>—</span>
                        <span>{{ __('lab.anaglyph.preview_hint') }}</span>
                    </div>
                </main>
            </div>
        </div>
    </div>
</section>
@endsection
