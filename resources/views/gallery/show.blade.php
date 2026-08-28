@extends('layouts.app')

@section('title', $galleryItem->title . ' — ' . __('gallery.index.title'))
@section('meta_description', \Illuminate\Support\Str::limit($galleryItem->description ?: __('gallery.show.default_description'), 155))

@push('head')
    @vite([
        'resources/css/gallery.css',
        'resources/js/community-gallery.js'
    ])
@endpush

@section('content')
<section class="community-gallery-page gallery-detail-page">
    <div class="site-container">
        <nav class="gallery-breadcrumbs">
            <a href="{{ route('home', ['locale' => app()->getLocale()]) }}">
                {{ __('gallery.common.home') }}
            </a>
            <span>›</span>
            <a href="{{ route('gallery.index', ['locale' => app()->getLocale()]) }}">
                {{ __('gallery.index.title') }}
            </a>
            <span>›</span>
            <span>{{ $galleryItem->title }}</span>
        </nav>

        <div class="gallery-detail-heading">
            <div>
                <span class="gallery-kicker">{{ __('gallery.show.kicker') }}</span>
                <h1>{{ $galleryItem->title }}</h1>
                <p>
                    {{ __('gallery.show.author') }}
                    <strong>{{ $galleryItem->author_name }}</strong>
                    · {{ $galleryItem->published_at?->format('d.m.Y') }}
                </p>
            </div>

            <a
                class="gallery-secondary-button"
                href="{{ route('gallery.index', ['locale' => app()->getLocale()]) }}"
            >
                ← {{ __('gallery.show.back') }}
            </a>
        </div>

        <div
            class="community-stereo-viewer"
            data-community-viewer
            data-left-url="{{ $galleryItem->leftImageUrl() }}"
            data-right-url="{{ $galleryItem->rightImageUrl() }}"
            data-loading="{{ __('gallery.viewer.loading') }}"
            data-ready="{{ __('gallery.viewer.ready') }}"
            data-error="{{ __('gallery.viewer.error') }}"
        >
            <div class="community-viewer-toolbar">
                <div class="community-viewer-mode">
                    <label for="gallery-mode">{{ __('gallery.viewer.mode') }}</label>
                    <select id="gallery-mode" data-gallery-mode>
                        <option value="parallel">{{ __('gallery.viewer.parallel') }}</option>
                        <option value="cross">{{ __('gallery.viewer.cross') }}</option>
                        <option value="anaglyph">{{ __('gallery.viewer.anaglyph') }}</option>
                        <option value="wiggle">{{ __('gallery.viewer.wiggle') }}</option>
                    </select>
                </div>

                <button
                    class="gallery-secondary-button"
                    type="button"
                    data-gallery-action="swap"
                >
                    ⇄ {{ __('gallery.viewer.swap') }}
                </button>

                <span data-gallery-status>{{ __('gallery.viewer.loading') }}</span>
            </div>

            <div class="community-viewer-stage">
                <canvas data-gallery-canvas></canvas>

                <div class="community-viewer-loading" data-gallery-empty>
                    <strong>{{ __('gallery.viewer.loading') }}</strong>
                </div>
            </div>

            <div class="community-viewer-footer">
                <span data-gallery-size>—</span>
                <span>{{ __('gallery.viewer.tip') }}</span>
            </div>
        </div>

        <div class="gallery-detail-info">
            <article>
                <span>{{ __('gallery.show.description') }}</span>
                <p>
                    {{ $galleryItem->description ?: __('gallery.show.no_description') }}
                </p>
            </article>

            <aside>
                <div>
                    <span>{{ __('gallery.show.license') }}</span>
                    <strong>{{ __('gallery.licenses.' . $galleryItem->license) }}</strong>
                </div>

                <div>
                    <span>{{ __('gallery.show.source_size') }}</span>
                    <strong>
                        {{ $galleryItem->left_width ?: '—' }}
                        ×
                        {{ $galleryItem->left_height ?: '—' }}
                        px
                    </strong>
                </div>
            </aside>
        </div>
    </div>
</section>
@endsection
