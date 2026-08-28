@extends('layouts.app')

@section('title', __('lab.mpo.meta_title') . ' — ' . __('site.title'))
@section('meta_description', __('lab.mpo.meta_description'))

@push('head')
    @vite([
        'resources/css/lab.css',
        'resources/css/advanced-stereo-lab.css',
        'resources/js/mpo-viewer.js'
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
            <span>{{ __('lab.mpo.title') }}</span>
        </nav>

        <div class="lab-workspace-heading">
            <div>
                <span class="lab-kicker">3D LAB / MPO</span>
                <h1>{{ __('lab.mpo.title') }}</h1>
                <p>{{ __('lab.mpo.description') }}</p>
            </div>

            <div class="lab-local-badge">
                <span>✓</span>
                {{ __('lab.mpo.local_note') }}
            </div>
        </div>

        <div
            class="advanced-stereo-tool"
            data-mpo-viewer
            data-loading="{{ __('lab.mpo.status.loading') }}"
            data-ready="{{ __('lab.mpo.status.ready') }}"
            data-invalid="{{ __('lab.mpo.status.invalid') }}"
        >
            <section class="advanced-panel advanced-upload-panel">
                <div class="advanced-panel-heading">
                    <div>
                        <span class="lab-kicker">01 / MPO</span>
                        <h2>{{ __('lab.mpo.upload.title') }}</h2>
                        <p>{{ __('lab.mpo.upload.help') }}</p>
                    </div>
                    <strong data-mpo-status>{{ __('lab.mpo.status.waiting') }}</strong>
                </div>

                <label class="advanced-dropzone" data-mpo-dropzone>
                    <input
                        type="file"
                        accept=".mpo,image/jpeg"
                        data-mpo-file
                    >
                    <span class="advanced-drop-icon">MPO</span>
                    <strong>{{ __('lab.mpo.upload.choose') }}</strong>
                    <span>{{ __('lab.mpo.upload.drop') }}</span>
                </label>

                <div class="advanced-metrics advanced-metrics-three">
                    <div>
                        <span>{{ __('lab.mpo.info.file') }}</span>
                        <strong data-mpo-filename>—</strong>
                    </div>
                    <div>
                        <span>{{ __('lab.mpo.info.images') }}</span>
                        <strong data-mpo-count>0</strong>
                    </div>
                    <div>
                        <span>{{ __('lab.mpo.info.dimensions') }}</span>
                        <strong data-mpo-dimensions>—</strong>
                    </div>
                </div>
            </section>

            <div class="advanced-workspace-grid">
                <aside class="advanced-controls">
                    <section class="advanced-panel">
                        <div class="advanced-panel-heading compact">
                            <div>
                                <span class="lab-kicker">02 / VIEW</span>
                                <h2>{{ __('lab.mpo.preview.title') }}</h2>
                            </div>
                        </div>

                        <div class="advanced-field">
                            <label for="mpo-mode">{{ __('lab.mpo.preview.mode') }}</label>
                            <select id="mpo-mode" data-mpo-mode>
                                <option value="parallel">{{ __('lab.mpo.preview.parallel') }}</option>
                                <option value="cross">{{ __('lab.mpo.preview.cross') }}</option>
                                <option value="anaglyph">{{ __('lab.mpo.preview.anaglyph') }}</option>
                                <option value="left">{{ __('lab.mpo.preview.left') }}</option>
                                <option value="right">{{ __('lab.mpo.preview.right') }}</option>
                            </select>
                        </div>
                    </section>

                    <section class="advanced-panel">
                        <div class="advanced-panel-heading compact">
                            <div>
                                <span class="lab-kicker">03 / EXPORT</span>
                                <h2>{{ __('lab.mpo.export.title') }}</h2>
                                <p>{{ __('lab.mpo.export.help') }}</p>
                            </div>
                        </div>

                        <div class="advanced-action-stack">
                            <button class="lab-primary-button" type="button" data-mpo-action="left-jpeg">
                                {{ __('lab.mpo.export.left') }}
                            </button>
                            <button class="lab-primary-button" type="button" data-mpo-action="right-jpeg">
                                {{ __('lab.mpo.export.right') }}
                            </button>
                            <button class="lab-secondary-button" type="button" data-mpo-action="sbs-png">
                                {{ __('lab.mpo.export.sbs') }}
                            </button>
                            <button class="lab-secondary-button" type="button" data-mpo-action="anaglyph-png">
                                {{ __('lab.mpo.export.anaglyph') }}
                            </button>
                        </div>
                    </section>
                </aside>

                <main class="advanced-preview-card">
                    <div class="advanced-preview-head">
                        <span>{{ __('lab.mpo.preview.canvas') }}</span>
                        <span data-mpo-preview-size>—</span>
                    </div>

                    <div class="advanced-canvas-stage">
                        <canvas data-mpo-canvas></canvas>

                        <div class="advanced-empty" data-mpo-empty>
                            <span class="advanced-empty-symbol">MPO</span>
                            <strong>{{ __('lab.mpo.preview.empty_title') }}</strong>
                            <p>{{ __('lab.mpo.preview.empty_text') }}</p>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>
</section>
@endsection
