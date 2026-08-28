@extends('layouts.app')

@section('title', __('lab.index.meta_title') . ' — ' . __('site.title'))
@section('meta_description', __('lab.index.meta_description'))

@push('head')
    @vite('resources/css/lab.css')
@endpush

@section('content')
<section class="lab-landing">
    <div class="site-container">
        <div class="lab-hero">
            <span class="lab-kicker">3D LAB</span>
            <h1>{{ __('lab.index.title') }}</h1>
            <p>{{ __('lab.index.description') }}</p>

            <div class="lab-privacy-note">
                <span>✓</span>
                <strong>{{ __('lab.index.local_processing_title') }}</strong>
                <p>{{ __('lab.index.local_processing') }}</p>
            </div>
        </div>

        <div class="lab-tool-grid">
            <article class="lab-tool-card">
                <div class="lab-tool-icon lab-tool-icon-anaglyph">
                    <span class="lab-lens lab-lens-red"></span>
                    <span class="lab-lens lab-lens-cyan"></span>
                </div>

                <div class="lab-tool-copy">
                    <span class="lab-tool-number">01</span>
                    <h2>{{ __('lab.index.anaglyph.title') }}</h2>
                    <p>{{ __('lab.index.anaglyph.description') }}</p>

                    <ul>
                        <li>{{ __('lab.index.anaglyph.feature_1') }}</li>
                        <li>{{ __('lab.index.anaglyph.feature_2') }}</li>
                        <li>{{ __('lab.index.anaglyph.feature_3') }}</li>
                    </ul>

                    <a
                        class="lab-primary-button"
                        href="{{ route('lab.anaglyph', ['locale' => app()->getLocale()]) }}"
                    >
                        {{ __('lab.index.open_tool') }} →
                    </a>
                </div>
            </article>

            <article class="lab-tool-card">
                <div class="lab-tool-icon lab-tool-icon-stereo">
                    <span class="lab-photo lab-photo-left">L</span>
                    <span class="lab-photo lab-photo-right">R</span>
                </div>

                <div class="lab-tool-copy">
                    <span class="lab-tool-number">02</span>
                    <h2>{{ __('lab.index.alignment.title') }}</h2>
                    <p>{{ __('lab.index.alignment.description') }}</p>

                    <ul>
                        <li>{{ __('lab.index.alignment.feature_1') }}</li>
                        <li>{{ __('lab.index.alignment.feature_2') }}</li>
                        <li>{{ __('lab.index.alignment.feature_3') }}</li>
                    </ul>

                    <a
                        class="lab-primary-button"
                        href="{{ route('lab.stereo-alignment', ['locale' => app()->getLocale()]) }}"
                    >
                        {{ __('lab.index.open_tool') }} →
                    </a>
                </div>
            </article>
        </div>

        <section class="lab-how">
            <span class="lab-kicker">{{ __('lab.index.workflow_kicker') }}</span>
            <h2>{{ __('lab.index.workflow_title') }}</h2>

            <div class="lab-how-grid">
                @foreach (['1', '2', '3', '4'] as $step)
                    <div>
                        <span>{{ $step }}</span>
                        <strong>{{ __('lab.index.workflow.' . $step . '.title') }}</strong>
                        <p>{{ __('lab.index.workflow.' . $step . '.text') }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</section>
@endsection
