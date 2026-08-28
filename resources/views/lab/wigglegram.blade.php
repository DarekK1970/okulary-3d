@extends('layouts.app')

@section('title', __('lab.wigglegram.meta_title') . ' — ' . __('site.title'))
@section('meta_description', __('lab.wigglegram.meta_description'))

@push('head')
    @vite([
        'resources/css/lab.css',
        'resources/css/advanced-stereo-lab.css',
        'resources/js/wigglegram.js'
    ])
@endpush

@section('content')
<section class="lab-workspace-page advanced-stereo-page">
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
            <span>{{ __('lab.wigglegram.title') }}</span>
        </nav>

        <div class="lab-workspace-heading">
            <div>
                <span class="lab-kicker">3D LAB / WIGGLE</span>
                <h1>{{ __('lab.wigglegram.title') }}</h1>
                <p>{{ __('lab.wigglegram.description') }}</p>
            </div>

            <div class="lab-local-badge">
                <span>✓</span>
                {{ __('lab.wigglegram.local_note') }}
            </div>
        </div>

        <div
            class="advanced-stereo-tool"
            data-wigglegram
            data-loading="{{ __('lab.wigglegram.status.loading') }}"
            data-ready="{{ __('lab.wigglegram.status.ready') }}"
            data-waiting="{{ __('lab.wigglegram.status.waiting') }}"
            data-exporting="{{ __('lab.wigglegram.status.exporting') }}"
        >
            <section class="advanced-panel advanced-upload-panel">
                <div class="advanced-panel-heading">
                    <div>
                        <span class="lab-kicker">01 / FRAMES</span>
                        <h2>{{ __('lab.wigglegram.upload.title') }}</h2>
                        <p>{{ __('lab.wigglegram.upload.help') }}</p>
                    </div>
                    <strong data-wiggle-status>{{ __('lab.wigglegram.status.waiting') }}</strong>
                </div>

                <label class="advanced-dropzone" data-wiggle-dropzone>
                    <input
                        type="file"
                        accept="image/jpeg,image/png,image/webp,.mpo"
                        multiple
                        data-wiggle-files
                    >
                    <span class="advanced-drop-icon">GIF</span>
                    <strong>{{ __('lab.wigglegram.upload.choose') }}</strong>
                    <span>{{ __('lab.wigglegram.upload.drop') }}</span>
                    <small data-wiggle-file-list>—</small>
                </label>
            </section>

            <div class="advanced-workspace-grid">
                <aside class="advanced-controls">
                    <section class="advanced-panel">
                        <div class="advanced-panel-heading compact">
                            <div>
                                <span class="lab-kicker">02 / ANIMATION</span>
                                <h2>{{ __('lab.wigglegram.settings.title') }}</h2>
                            </div>
                        </div>

                        <div class="advanced-field">
                            <label for="wiggle-delay">{{ __('lab.wigglegram.settings.delay') }}</label>
                            <input
                                id="wiggle-delay"
                                type="number"
                                min="60"
                                max="1500"
                                step="10"
                                value="140"
                                data-wiggle-control="delay"
                            >
                        </div>

                        <div class="advanced-field">
                            <label for="wiggle-loop">{{ __('lab.wigglegram.settings.loop') }}</label>
                            <select id="wiggle-loop" data-wiggle-control="loopMode">
                                <option value="pingpong">{{ __('lab.wigglegram.settings.pingpong') }}</option>
                                <option value="loop">{{ __('lab.wigglegram.settings.loop_forward') }}</option>
                            </select>
                        </div>

                        <div class="advanced-field">
                            <label for="wiggle-width">{{ __('lab.wigglegram.settings.width') }}</label>
                            <select id="wiggle-width" data-wiggle-control="width">
                                <option value="480">480 px</option>
                                <option value="720" selected>720 px</option>
                                <option value="960">960 px</option>
                            </select>
                        </div>

                        <div class="advanced-metrics">
                            <div>
                                <span>{{ __('lab.wigglegram.metrics.frames') }}</span>
                                <strong data-wiggle-metric="frames">0</strong>
                            </div>
                            <div>
                                <span>{{ __('lab.wigglegram.metrics.delay') }}</span>
                                <strong data-wiggle-metric="duration">140 ms</strong>
                            </div>
                            <div>
                                <span>{{ __('lab.wigglegram.metrics.width') }}</span>
                                <strong data-wiggle-metric="size">720 px</strong>
                            </div>
                        </div>
                    </section>

                    <section class="advanced-panel">
                        <div class="advanced-panel-heading compact">
                            <div>
                                <span class="lab-kicker">03 / EXPORT</span>
                                <h2>{{ __('lab.wigglegram.export.title') }}</h2>
                                <p>{{ __('lab.wigglegram.export.help') }}</p>
                            </div>
                        </div>

                        <div class="advanced-action-stack">
                            <button class="lab-primary-button" type="button" data-wiggle-action="gif">
                                {{ __('lab.wigglegram.export.gif') }}
                            </button>
                            <button class="lab-secondary-button" type="button" data-wiggle-action="frame-png">
                                {{ __('lab.wigglegram.export.frame') }}
                            </button>
                            <button class="lab-secondary-button" type="button" data-wiggle-action="reset">
                                {{ __('lab.wigglegram.export.reset') }}
                            </button>
                        </div>

                        <p class="advanced-note">{{ __('lab.wigglegram.export.note') }}</p>
                    </section>
                </aside>

                <main class="advanced-preview-card">
                    <div class="advanced-preview-head">
                        <span>{{ __('lab.wigglegram.preview.title') }}</span>
                        <span>GIF 256 colors</span>
                    </div>

                    <div class="advanced-canvas-stage">
                        <canvas data-wiggle-canvas></canvas>

                        <div class="advanced-empty" data-wiggle-empty>
                            <span class="advanced-empty-symbol">≋</span>
                            <strong>{{ __('lab.wigglegram.preview.empty_title') }}</strong>
                            <p>{{ __('lab.wigglegram.preview.empty_text') }}</p>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>
</section>
@endsection
