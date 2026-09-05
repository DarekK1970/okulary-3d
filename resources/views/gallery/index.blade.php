@extends('layouts.app')

@section('title', __('gallery.index.meta_title') . ' — ' . __('site.title'))
@section('meta_description', __('gallery.index.meta_description'))

@push('head')
    @vite([
        'resources/css/gallery.css',
        'resources/js/community-gallery.js'
    ])
@endpush

@section('content')
<section class="community-gallery-page">
    <div class="site-container">
        <div class="community-gallery-hero">
            <div>
                <span class="gallery-kicker">{{ __('gallery.index.kicker') }}</span>
                <h1>{{ __('gallery.index.title') }}</h1>
                <p>{{ __('gallery.index.description') }}</p>
            </div>

            <div class="community-gallery-hero-actions">
                @auth
                    <a
                        class="gallery-primary-button"
                        href="{{ route('gallery.create', ['locale' => app()->getLocale()]) }}"
                    >
                        {{ __('gallery.index.submit') }}
                    </a>

                    <a
                        class="gallery-secondary-button"
                        href="{{ route('account.gallery.index', ['locale' => app()->getLocale()]) }}"
                    >
                        {{ __('gallery.index.my_submissions') }}
                    </a>
                @else
                    <a
                        class="gallery-primary-button"
                        href="{{ route('login', ['locale' => app()->getLocale()]) }}"
                    >
                        {{ __('gallery.index.login_to_submit') }}
                    </a>
                @endauth
            </div>
        </div>

        <div class="gallery-viewing-note">
            <span>3D</span>
            <div>
                <strong>{{ __('gallery.index.viewing_title') }}</strong>
                <p>{{ __('gallery.index.viewing_text') }}</p>
            </div>
        </div>

        @if ($currentGalleryItem)
            @include('gallery.partials.browser', [
                'items' => $items,
                'currentGalleryItem' => $currentGalleryItem,
            ])
        @else
            <div class="community-gallery-grid">
                <div class="community-gallery-empty">
                    <strong>{{ __('gallery.index.empty_title') }}</strong>
                    <p>{{ __('gallery.index.empty_text') }}</p>

                    @auth
                        <a
                            class="gallery-primary-button"
                            href="{{ route('gallery.create', ['locale' => app()->getLocale()]) }}"
                        >
                            {{ __('gallery.index.submit_first') }}
                        </a>
                    @endauth
                </div>
            </div>
        @endif
    </div>
</section>
@endsection
