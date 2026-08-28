@extends('layouts.app')

@section('title', __('archive.index.meta_title') . ' — ' . __('site.title'))
@section('meta_description', __('archive.index.meta_description'))

@push('head')
    @vite('resources/css/archive.css')
@endpush

@section('content')
<section class="archive-page">
    <div class="site-container">
        <div class="archive-hero">
            <div>
                <span class="archive-kicker">{{ __('archive.index.kicker') }}</span>
                <h1>{{ __('archive.index.title') }}</h1>
                <p>{{ __('archive.index.description') }}</p>
            </div>

            <div class="archive-hero-note">
                <span>◎</span>
                <div>
                    <strong>{{ __('archive.index.source_title') }}</strong>
                    <p>{{ __('archive.index.source_text') }}</p>
                </div>
            </div>
        </div>

        <form
            class="archive-filters"
            method="get"
            action="{{ route('archive.index', ['locale' => app()->getLocale()]) }}"
        >
            <div class="archive-filter-search">
                <label for="archive-q">{{ __('archive.index.search') }}</label>
                <input
                    id="archive-q"
                    type="search"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="{{ __('archive.index.search_placeholder') }}"
                >
            </div>

            <label>
                <span>{{ __('archive.index.technique') }}</span>
                <select name="technique">
                    <option value="">{{ __('archive.index.all_techniques') }}</option>

                    @foreach (['stereocard', 'stereo_photo', 'anaglyph', 'viewmaster', 'lenticular', 'other'] as $technique)
                        <option
                            value="{{ $technique }}"
                            @selected(request('technique') === $technique)
                        >
                            {{ __('archive.techniques.' . $technique) }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label>
                <span>{{ __('archive.index.country') }}</span>
                <select name="country">
                    <option value="">{{ __('archive.index.all_countries') }}</option>

                    @foreach ($countries as $country)
                        <option
                            value="{{ $country }}"
                            @selected(request('country') === $country)
                        >
                            {{ $country }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label>
                <span>{{ __('archive.index.year_from') }}</span>
                <input
                    type="number"
                    name="year_from"
                    min="1800"
                    max="2100"
                    value="{{ request('year_from') }}"
                >
            </label>

            <label>
                <span>{{ __('archive.index.year_to') }}</span>
                <input
                    type="number"
                    name="year_to"
                    min="1800"
                    max="2100"
                    value="{{ request('year_to') }}"
                >
            </label>

            <button class="archive-primary-button" type="submit">
                {{ __('archive.index.filter') }}
            </button>

            @if (request()->hasAny(['q', 'technique', 'country', 'year_from', 'year_to']))
                <a
                    class="archive-secondary-button"
                    href="{{ route('archive.index', ['locale' => app()->getLocale()]) }}"
                >
                    {{ __('archive.index.clear') }}
                </a>
            @endif
        </form>

        <div class="archive-grid-public">
            @forelse ($items as $item)
                @php
                    $translation = $item->publicTranslation(app()->getLocale());
                @endphp

                @if ($translation)
                    <a
                        class="archive-public-card"
                        href="{{ route('archive.show', [
                            'locale' => app()->getLocale(),
                            'slug' => $translation->slug
                        ]) }}"
                    >
                        <div class="archive-public-image">
                            <img
                                src="{{ $item->originalImageUrl() }}"
                                alt="{{ $translation->title }}"
                                loading="lazy"
                            >

                            <span class="archive-year">{{ $item->yearLabel() }}</span>

                            @if ($item->hasStereoPair())
                                <span class="archive-stereo-badge">L/R</span>
                            @endif
                        </div>

                        <div class="archive-public-copy">
                            <span>{{ __('archive.techniques.' . $item->technique) }}</span>
                            <h2>{{ $translation->title }}</h2>

                            <div class="archive-public-meta">
                                @if ($item->creator)
                                    <span>{{ $item->creator }}</span>
                                @endif

                                @if ($item->country)
                                    <span>{{ $item->country }}</span>
                                @endif
                            </div>

                            @if ($translation->description)
                                <p>{{ \Illuminate\Support\Str::limit($translation->description, 145) }}</p>
                            @endif
                        </div>
                    </a>
                @endif
            @empty
                <div class="archive-empty">
                    <strong>{{ __('archive.index.empty_title') }}</strong>
                    <p>{{ __('archive.index.empty_text') }}</p>
                </div>
            @endforelse
        </div>

        @if ($items->hasPages())
            <div class="archive-pagination">
                {{ $items->links() }}
            </div>
        @endif
    </div>
</section>
@endsection
