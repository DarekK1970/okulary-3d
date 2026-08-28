@extends('layouts.app')

@section('title', ($translation->seo_title ?: $translation->title) . ' — ' . __('archive.index.title'))
@section('meta_description', $translation->seo_description ?: \Illuminate\Support\Str::limit($translation->description ?: __('archive.show.default_description'), 155))

@push('head')
    @vite([
        'resources/css/archive.css',
        'resources/js/archive-viewer.js'
    ])
@endpush

@section('content')
<section class="archive-page archive-detail-page">
    <div class="site-container">
        <nav class="archive-breadcrumbs">
            <a href="{{ route('home', ['locale' => app()->getLocale()]) }}">
                {{ __('archive.common.home') }}
            </a>
            <span>›</span>
            <a href="{{ route('archive.index', ['locale' => app()->getLocale()]) }}">
                {{ __('archive.index.title') }}
            </a>
            <span>›</span>
            <span>{{ $translation->title }}</span>
        </nav>

        <div class="archive-detail-heading">
            <div>
                <span class="archive-kicker">{{ __('archive.techniques.' . $archiveItem->technique) }}</span>
                <h1>{{ $translation->title }}</h1>

                <p>
                    <strong>{{ $archiveItem->yearLabel() }}</strong>

                    @if ($archiveItem->creator)
                        · {{ $archiveItem->creator }}
                    @endif

                    @if ($archiveItem->country)
                        · {{ $archiveItem->country }}
                    @endif
                </p>
            </div>

            <a
                class="archive-secondary-button"
                href="{{ route('archive.index', ['locale' => app()->getLocale()]) }}"
            >
                ← {{ __('archive.show.back') }}
            </a>
        </div>

        <div
            class="archive-viewer"
            data-archive-viewer
            data-original-url="{{ $archiveItem->originalImageUrl() }}"
            @if ($archiveItem->hasStereoPair())
                data-left-url="{{ $archiveItem->leftImageUrl() }}"
                data-right-url="{{ $archiveItem->rightImageUrl() }}"
            @endif
            data-loading="{{ __('archive.viewer.loading') }}"
            data-ready="{{ __('archive.viewer.ready') }}"
            data-error="{{ __('archive.viewer.error') }}"
        >
            <div class="archive-viewer-toolbar">
                <div class="archive-viewer-mode">
                    <label for="archive-view-mode">{{ __('archive.viewer.mode') }}</label>
                    <select id="archive-view-mode" data-archive-mode>
                        <option value="original">{{ __('archive.viewer.original') }}</option>

                        @if ($archiveItem->hasStereoPair())
                            <option value="parallel">{{ __('archive.viewer.parallel') }}</option>
                            <option value="cross">{{ __('archive.viewer.cross') }}</option>
                            <option value="anaglyph">{{ __('archive.viewer.anaglyph') }}</option>
                            <option value="wiggle">{{ __('archive.viewer.wiggle') }}</option>
                        @endif
                    </select>
                </div>

                @if ($archiveItem->hasStereoPair())
                    <button
                        class="archive-secondary-button"
                        type="button"
                        data-archive-action="swap"
                    >
                        ⇄ {{ __('archive.viewer.swap') }}
                    </button>
                @endif

                <span data-archive-status>{{ __('archive.viewer.loading') }}</span>
            </div>

            <div class="archive-viewer-stage">
                <canvas data-archive-canvas></canvas>

                <div class="archive-viewer-loading" data-archive-empty>
                    <strong>{{ __('archive.viewer.loading') }}</strong>
                </div>
            </div>

            <div class="archive-viewer-footer">
                <span data-archive-size>—</span>
                <span>{{ __('archive.viewer.tip') }}</span>
            </div>
        </div>

        <div class="archive-detail-grid">
            <main>
                @if ($translation->description)
                    <section class="archive-detail-panel">
                        <span class="archive-detail-label">{{ __('archive.show.description') }}</span>
                        <p>{{ $translation->description }}</p>
                    </section>
                @endif

                @if ($translation->historical_note)
                    <section class="archive-detail-panel">
                        <span class="archive-detail-label">{{ __('archive.show.historical_note') }}</span>
                        <div class="archive-historical-note">
                            {!! nl2br(e($translation->historical_note)) !!}
                        </div>
                    </section>
                @endif
            </main>

            <aside class="archive-metadata-panel">
                <h2>{{ __('archive.show.metadata') }}</h2>

                <dl>
                    <div>
                        <dt>{{ __('archive.show.date') }}</dt>
                        <dd>{{ $archiveItem->yearLabel() }}</dd>
                    </div>

                    <div>
                        <dt>{{ __('archive.show.technique') }}</dt>
                        <dd>{{ __('archive.techniques.' . $archiveItem->technique) }}</dd>
                    </div>

                    @if ($archiveItem->creator)
                        <div>
                            <dt>{{ __('archive.show.creator') }}</dt>
                            <dd>{{ $archiveItem->creator }}</dd>
                        </div>
                    @endif

                    @if ($archiveItem->publisher)
                        <div>
                            <dt>{{ __('archive.show.publisher') }}</dt>
                            <dd>{{ $archiveItem->publisher }}</dd>
                        </div>
                    @endif

                    @if ($archiveItem->country)
                        <div>
                            <dt>{{ __('archive.show.country') }}</dt>
                            <dd>{{ $archiveItem->country }}</dd>
                        </div>
                    @endif

                    @if ($archiveItem->collection_name)
                        <div>
                            <dt>{{ __('archive.show.collection') }}</dt>
                            <dd>{{ $archiveItem->collection_name }}</dd>
                        </div>
                    @endif

                    <div>
                        <dt>{{ __('archive.show.source') }}</dt>
                        <dd>
                            @if ($archiveItem->source_url)
                                <a
                                    href="{{ $archiveItem->source_url }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    {{ $archiveItem->source_name }} ↗
                                </a>
                            @else
                                {{ $archiveItem->source_name }}
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt>{{ __('archive.show.rights') }}</dt>
                        <dd>{{ __('archive.rights.' . $archiveItem->rights_status) }}</dd>
                    </div>

                    @if ($archiveItem->rights_note)
                        <div>
                            <dt>{{ __('archive.show.rights_note') }}</dt>
                            <dd>{{ $archiveItem->rights_note }}</dd>
                        </div>
                    @endif

                    <div>
                        <dt>{{ __('archive.show.original_size') }}</dt>
                        <dd>
                            {{ $archiveItem->original_width ?: '—' }}
                            ×
                            {{ $archiveItem->original_height ?: '—' }}
                            px
                        </dd>
                    </div>
                </dl>
            </aside>
        </div>
    </div>
</section>
@endsection
