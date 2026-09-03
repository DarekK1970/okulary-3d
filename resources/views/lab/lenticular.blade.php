@extends('layouts.app')

@section('title', __('lab.lenticular.meta_title') . ' — ' . __('site.title'))
@section('meta_description', __('lab.lenticular.meta_description'))

@push('head')
    @vite([
        'resources/css/lab.css',
        'resources/css/lenticular-lab.css',
        'resources/js/lenticular-lab.js'
    ])
@endpush

@section('content')
<section
    class="lab-workspace-page lenticular-page"
    data-lenticular-lab
    data-error-image="{{ __('lab.lenticular.interlacer.error') }}"
    data-too-many="{{ __('lab.lenticular.interlacer.too_many') }}"
    data-processing="{{ __('lab.lenticular.interlacer.processing') }}"
    data-ready="{{ __('lab.lenticular.interlacer.ready') }}"
    data-waiting="{{ __('lab.lenticular.interlacer.waiting') }}"
    data-warning-low-pitch="{{ __('lab.lenticular.interlacer.warning_low_pitch') }}"
    data-quality-good="{{ __('lab.lenticular.calculator.quality_good') }}"
    data-quality-low="{{ __('lab.lenticular.calculator.quality_low') }}"
    data-quality-high="{{ __('lab.lenticular.calculator.quality_high') }}"
    data-download-prefix="lenticular"
>
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
            <span>{{ __('lab.lenticular.title') }}</span>
        </nav>

        <div class="lab-workspace-heading">
            <div>
                <span class="lab-kicker">3D LAB / LENTICULAR</span>
                <h1>{{ __('lab.lenticular.title') }}</h1>
                <p>{{ __('lab.lenticular.description') }}</p>
            </div>

            <div class="lab-local-badge">
                <span>✓</span>
                {{ __('lab.lenticular.local_note') }}
            </div>
        </div>

        <nav class="lenticular-section-nav" aria-label="{{ __('lab.lenticular.title') }}">
            <a href="#interlacer">{{ __('lab.lenticular.nav.interlacer') }}</a>
            <a href="#pitch-test">{{ __('lab.lenticular.nav.pitch') }}</a>
            <a href="#calculator">{{ __('lab.lenticular.nav.calculator') }}</a>
            <a href="#wizard">{{ __('lab.lenticular.nav.wizard') }}</a>
        </nav>

        <section class="lenticular-panel" id="interlacer">
            <div class="lenticular-panel-heading">
                <div>
                    <span class="lab-kicker">{{ __('lab.lenticular.interlacer.kicker') }}</span>
                    <h2>{{ __('lab.lenticular.interlacer.title') }}</h2>
                    <p>{{ __('lab.lenticular.interlacer.description') }}</p>
                </div>
                <strong data-interlacer-status>{{ __('lab.lenticular.interlacer.waiting') }}</strong>
            </div>

            <label class="lenticular-dropzone" data-lenticular-dropzone>
                <input
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    multiple
                    data-lenticular-files
                >
                <span class="lenticular-drop-icon">L</span>
                <strong>{{ __('lab.lenticular.interlacer.files') }}</strong>
                <span>{{ __('lab.lenticular.interlacer.choose') }}</span>
                <small data-lenticular-file-list>{{ __('lab.lenticular.interlacer.no_files') }}</small>
            </label>

            <div class="lenticular-file-help">
                {{ __('lab.lenticular.interlacer.files_help') }}
            </div>

            <div class="lenticular-tool-grid">
                <div class="lenticular-controls">
                    <div class="lenticular-control-grid">
                        <div class="lab-control">
                            <label for="lenticular-lpi">{{ __('lab.lenticular.interlacer.lpi') }}</label>
                            <input
                                id="lenticular-lpi"
                                type="number"
                                min="20"
                                max="200"
                                step="0.1"
                                value="60"
                                data-lenticular-control="lpi"
                            >
                        </div>

                        <div class="lab-control">
                            <label for="lenticular-dpi">{{ __('lab.lenticular.interlacer.dpi') }}</label>
                            <input
                                id="lenticular-dpi"
                                type="number"
                                min="72"
                                max="2400"
                                step="1"
                                value="600"
                                data-lenticular-control="dpi"
                            >
                        </div>

                        <div class="lab-control">
                            <label for="lenticular-width">{{ __('lab.lenticular.interlacer.width_mm') }}</label>
                            <input
                                id="lenticular-width"
                                type="number"
                                min="10"
                                max="1000"
                                step="1"
                                value="210"
                                data-lenticular-control="widthMm"
                            >
                        </div>

                        <div class="lab-control">
                            <label for="lenticular-height">{{ __('lab.lenticular.interlacer.height_mm') }}</label>
                            <input
                                id="lenticular-height"
                                type="number"
                                min="10"
                                max="1000"
                                step="1"
                                value="297"
                                data-lenticular-control="heightMm"
                            >
                        </div>

                        <div class="lab-control">
                            <label for="lenticular-phase">{{ __('lab.lenticular.interlacer.phase') }}</label>
                            <input
                                id="lenticular-phase"
                                type="number"
                                min="0"
                                max="20"
                                step="0.1"
                                value="0"
                                data-lenticular-control="phase"
                            >
                        </div>
                        <div class="lab-control">
                            <label for="lenticular-orientation">{{ __('lab.lenticular.interlacer.orientation') }}</label>
                            <select id="lenticular-orientation" data-lenticular-control="orientation">
                                <option value="vertical" selected>{{ __('lab.lenticular.interlacer.vertical') }}</option>
                                <option value="horizontal">{{ __('lab.lenticular.interlacer.horizontal') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="lenticular-metrics">
                        <div>
                            <span>{{ __('lab.lenticular.interlacer.views') }}</span>
                            <strong data-metric="views">0</strong>
                        </div>
                        <div>
                            <span>{{ __('lab.lenticular.interlacer.pitch') }}</span>
                            <strong data-metric="pitch">—</strong>
                        </div>
                        <div>
                            <span>{{ __('lab.lenticular.interlacer.strip') }}</span>
                            <strong data-metric="strip">—</strong>
                        </div>
                        <div>
                            <span>{{ __('lab.lenticular.interlacer.output') }}</span>
                            <strong data-metric="output">—</strong>
                        </div>
                    </div>

                    <div class="lenticular-note">
                        {{ __('lab.lenticular.interlacer.help') }}
                    </div>

                    <div class="lenticular-actions">
                        <button class="lab-primary-button" type="button" data-action="interlace-render">
                            {{ __('lab.lenticular.interlacer.render') }}
                        </button>
                        <button class="lab-primary-button is-pdf" type="button" data-action="interlace-export-pdf">
                            {{ __('lab.lenticular.interlacer.export_pdf') }}
                        </button>
                        <button class="lab-secondary-button" type="button" data-action="interlace-export-png">
                            {{ __('lab.lenticular.interlacer.export_png') }}
                        </button>
                        <button class="lab-secondary-button" type="button" data-action="interlace-reset">
                            {{ __('lab.lenticular.interlacer.reset') }}
                        </button>
                    </div>

                    <p class="lenticular-export-note">
                        <strong>{{ __('lab.lenticular.interlacer.pdf_recommended') }}</strong>
                        {{ __('lab.lenticular.interlacer.pdf_note') }}
                    </p>

                    <p class="lenticular-warning" data-interlacer-warning></p>
                </div>

                <div class="lenticular-preview-card">
                    <div class="lenticular-preview-head">
                        <span>{{ __('lab.lenticular.interlacer.preview') }}</span>
                        <span data-interlacer-size>—</span>
                    </div>
                    <div class="lenticular-canvas-stage">
                        <canvas data-interlacer-canvas></canvas>
                        <div class="lenticular-empty" data-interlacer-empty>
                            <strong>{{ __('lab.lenticular.interlacer.waiting') }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="lenticular-panel" id="pitch-test">
            <div class="lenticular-panel-heading">
                <div>
                    <span class="lab-kicker">{{ __('lab.lenticular.pitch.kicker') }}</span>
                    <h2>{{ __('lab.lenticular.pitch.title') }}</h2>
                    <p>{{ __('lab.lenticular.pitch.description') }}</p>
                </div>
            </div>

            <div class="lenticular-two-column">
                <div class="lenticular-controls">
                    <div class="lenticular-control-grid">
                        <div class="lab-control">
                            <label for="pitch-width">{{ __('lab.lenticular.pitch.width') }}</label>
                            <input id="pitch-width" type="number" min="50" max="500" step="1" value="210" data-pitch-control="width">
                        </div>
                        <div class="lab-control">
                            <label for="pitch-height">{{ __('lab.lenticular.pitch.height') }}</label>
                            <input id="pitch-height" type="number" min="50" max="500" step="1" value="297" data-pitch-control="height">
                        </div>
                        <div class="lab-control">
                            <label for="pitch-dpi">{{ __('lab.lenticular.pitch.dpi') }}</label>
                            <input id="pitch-dpi" type="number" min="72" max="1200" step="1" value="300" data-pitch-control="dpi">
                        </div>
                        <div class="lab-control">
                            <label for="pitch-start">{{ __('lab.lenticular.pitch.start') }}</label>
                            <input id="pitch-start" type="number" min="20" max="200" step="0.1" value="56" data-pitch-control="start">
                        </div>
                        <div class="lab-control">
                            <label for="pitch-end">{{ __('lab.lenticular.pitch.end') }}</label>
                            <input id="pitch-end" type="number" min="20" max="200" step="0.1" value="64" data-pitch-control="end">
                        </div>
                        <div class="lab-control">
                            <label for="pitch-step">{{ __('lab.lenticular.pitch.step') }}</label>
                            <input id="pitch-step" type="number" min="0.1" max="10" step="0.1" value="1" data-pitch-control="step">
                        </div>
                    </div>

                    <div class="lenticular-actions">
                        <button class="lab-primary-button" type="button" data-action="pitch-generate">
                            {{ __('lab.lenticular.pitch.generate') }}
                        </button>
                        <button class="lab-primary-button is-pdf" type="button" data-action="pitch-export-pdf">
                            {{ __('lab.lenticular.pitch.export_pdf') }}
                        </button>
                        <button class="lab-secondary-button" type="button" data-action="pitch-export-png">
                            {{ __('lab.lenticular.pitch.export_png') }}
                        </button>
                    </div>

                    <p class="lenticular-note">{{ __('lab.lenticular.pitch.note') }}</p>
                </div>

                <div class="lenticular-preview-card">
                    <div class="lenticular-preview-head">
                        <span>{{ __('lab.lenticular.pitch.preview') }}</span>
                        <span data-pitch-size>—</span>
                    </div>
                    <div class="lenticular-pitch-stage">
                        <canvas data-pitch-canvas></canvas>
                    </div>
                </div>
            </div>
        </section>

        <section class="lenticular-panel" id="calculator">
            <div class="lenticular-panel-heading">
                <div>
                    <span class="lab-kicker">{{ __('lab.lenticular.calculator.kicker') }}</span>
                    <h2>{{ __('lab.lenticular.calculator.title') }}</h2>
                    <p>{{ __('lab.lenticular.calculator.description') }}</p>
                </div>
            </div>

            <div class="lenticular-calculator">
                <div class="lenticular-control-grid">
                    <div class="lab-control">
                        <label for="calc-lpi">{{ __('lab.lenticular.calculator.lpi') }}</label>
                        <input id="calc-lpi" type="number" min="20" max="200" step="0.1" value="60" data-calc-control="lpi">
                    </div>
                    <div class="lab-control">
                        <label for="calc-dpi">{{ __('lab.lenticular.calculator.dpi') }}</label>
                        <input id="calc-dpi" type="number" min="72" max="2400" step="1" value="600" data-calc-control="dpi">
                    </div>
                    <div class="lab-control">
                        <label for="calc-views">{{ __('lab.lenticular.calculator.views') }}</label>
                        <input id="calc-views" type="number" min="2" max="12" step="1" value="8" data-calc-control="views">
                    </div>
                    <div class="lab-control">
                        <label for="calc-width">{{ __('lab.lenticular.calculator.width') }}</label>
                        <input id="calc-width" type="number" min="10" max="500" step="1" value="210" data-calc-control="width">
                    </div>
                    <div class="lab-control">
                        <label for="calc-height">{{ __('lab.lenticular.calculator.height') }}</label>
                        <input id="calc-height" type="number" min="10" max="500" step="1" value="297" data-calc-control="height">
                    </div>
                </div>

                <div class="lenticular-result-grid">
                    <div><span>{{ __('lab.lenticular.calculator.pixel_pitch') }}</span><strong data-calc-result="pitch">—</strong></div>
                    <div><span>{{ __('lab.lenticular.calculator.view_strip') }}</span><strong data-calc-result="strip">—</strong></div>
                    <div><span>{{ __('lab.lenticular.calculator.lens_count') }}</span><strong data-calc-result="lens-count">—</strong></div>
                    <div><span>{{ __('lab.lenticular.calculator.raster_width') }}</span><strong data-calc-result="width">—</strong></div>
                    <div><span>{{ __('lab.lenticular.calculator.raster_height') }}</span><strong data-calc-result="height">—</strong></div>
                    <div><span>{{ __('lab.lenticular.calculator.quality') }}</span><strong data-calc-result="quality">—</strong></div>
                </div>
            </div>
        </section>

        <section class="lenticular-panel" id="wizard">
            <div class="lenticular-panel-heading">
                <div>
                    <span class="lab-kicker">{{ __('lab.lenticular.wizard.kicker') }}</span>
                    <h2>{{ __('lab.lenticular.wizard.title') }}</h2>
                    <p>{{ __('lab.lenticular.wizard.description') }}</p>
                </div>
            </div>

            <div class="lenticular-wizard-grid">
                <div class="lenticular-wizard-steps">
                    <div><span>01</span><strong>{{ __('lab.lenticular.wizard.step_1') }}</strong></div>
                    <div><span>02</span><strong>{{ __('lab.lenticular.wizard.step_2') }}</strong></div>
                    <div><span>03</span><strong>{{ __('lab.lenticular.wizard.step_3') }}</strong></div>
                    <div><span>04</span><strong>{{ __('lab.lenticular.wizard.step_4') }}</strong></div>
                </div>

                <div class="lenticular-wizard-card">
                    <div class="lenticular-control-grid">
                        <div class="lab-control">
                            <label for="wizard-size">{{ __('lab.lenticular.wizard.size') }}</label>
                            <select id="wizard-size" data-wizard-control="size">
                                <option value="portrait">{{ __('lab.lenticular.wizard.portrait') }}</option>
                                <option value="landscape">{{ __('lab.lenticular.wizard.landscape') }}</option>
                            </select>
                        </div>
                        <div class="lab-control">
                            <label for="wizard-dpi">{{ __('lab.lenticular.wizard.dpi') }}</label>
                            <input id="wizard-dpi" type="number" min="72" max="1200" step="1" value="600" data-wizard-control="dpi">
                        </div>
                        <div class="lab-control">
                            <label for="wizard-views">{{ __('lab.lenticular.wizard.views') }}</label>
                            <input id="wizard-views" type="number" min="2" max="12" step="1" value="8" data-wizard-control="views">
                        </div>
                    </div>

                    <div class="lenticular-wizard-results">
                        <div><span>{{ __('lab.lenticular.wizard.dimensions') }}</span><strong data-wizard-result="dimensions">—</strong></div>
                        <div><span>{{ __('lab.lenticular.wizard.physical') }}</span><strong data-wizard-result="physical">210 × 297 mm</strong></div>
                        <div><span>{{ __('lab.lenticular.wizard.pitch') }}</span><strong data-wizard-result="pitch">—</strong></div>
                        <div><span>{{ __('lab.lenticular.wizard.strip') }}</span><strong data-wizard-result="strip">—</strong></div>
                    </div>

                    <button class="lab-primary-button" type="button" data-action="wizard-apply">
                        {{ __('lab.lenticular.wizard.apply') }}
                    </button>

                    <p class="lenticular-note">{{ __('lab.lenticular.wizard.note') }}</p>
                </div>
            </div>
        </section>
    </div>
</section>
@endsection
