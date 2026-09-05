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

        @include('gallery.partials.browser', [
            'items' => $items,
            'currentGalleryItem' => $currentGalleryItem,
        ])

    </div>
</section>
@endsection
