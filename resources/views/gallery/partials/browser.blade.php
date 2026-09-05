@php
    $currentIndex = $items->search(
        fn ($item) => $item->is($currentGalleryItem)
    );
    $previousItem = $currentIndex === false || $currentIndex === 0
        ? null
        : $items->get($currentIndex - 1);
    $nextItem = $currentIndex === false
        ? null
        : $items->get($currentIndex + 1);
    $browserUrl = function ($item) {
        if (request()->routeIs('gallery.index')) {
            return route('gallery.index', array_filter([
                'locale' => app()->getLocale(),
                'author' => request('author'),
                'photo' => $item->slug,
            ]));
        }

        return route('gallery.show', [
            'locale' => app()->getLocale(),
            'galleryItem' => $item,
        ]);
    };
@endphp

<div class="community-gallery-browser" data-gallery-browser>
    <div
        class="community-stereo-viewer"
        data-community-viewer
        data-left-url="{{ $currentGalleryItem->leftImageUrl() }}"
        data-right-url="{{ $currentGalleryItem->rightImageUrl() }}"
        data-loading="{{ __('gallery.viewer.loading') }}"
        data-rating-summary="{{ $currentGalleryItem->ratingSummary() }}"
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

            <span class="gallery-rating-badge" data-gallery-status>
                {{ $currentGalleryItem->ratingSummary() }}
            </span>
        </div>

        <div class="community-viewer-frame">
            @if ($previousItem)
                <a
                    class="community-gallery-nav community-gallery-nav-prev"
                    href="{{ $browserUrl($previousItem) }}"
                    data-gallery-nav="previous"
                    aria-label="{{ __('gallery.viewer.previous') }}"
                >‹</a>
            @else
                <a
                    class="community-gallery-nav community-gallery-nav-prev"
                    href="#"
                    data-gallery-nav="previous"
                    aria-label="{{ __('gallery.viewer.previous') }}"
                    hidden
                >‹</a>
            @endif

            <div class="community-viewer-stage">
                <div class="gallery-active-heading">
                    <h2 data-gallery-current-title>{{ $currentGalleryItem->title }}</h2>
                </div>

                <canvas data-gallery-canvas></canvas>

                <div class="community-viewer-loading" data-gallery-empty>
                    <strong>{{ __('gallery.viewer.loading') }}</strong>
                </div>
            </div>

            @if ($nextItem)
                <a
                    class="community-gallery-nav community-gallery-nav-next"
                    href="{{ $browserUrl($nextItem) }}"
                    data-gallery-nav="next"
                    aria-label="{{ __('gallery.viewer.next') }}"
                >›</a>
            @else
                <a
                    class="community-gallery-nav community-gallery-nav-next"
                    href="#"
                    data-gallery-nav="next"
                    aria-label="{{ __('gallery.viewer.next') }}"
                    hidden
                >›</a>
            @endif
        </div>

        <div class="community-viewer-footer">
            <span data-gallery-size>—</span>
            <span>{{ __('gallery.viewer.tip') }}</span>
        </div>

        <div class="gallery-current-meta">
            <div>
                <p data-gallery-current-description>
                    {{ $currentGalleryItem->description ?: __('gallery.show.no_description') }}
                </p>
                <a
                    href="{{ route('gallery.index', [
                        'locale' => app()->getLocale(),
                        'author' => $currentGalleryItem->author_name,
                    ]) }}"
                    data-gallery-current-author
                    title="{{ __('gallery.viewer.author_tooltip') }}"
                >{{ $currentGalleryItem->author_name }}</a>
                <span data-gallery-current-license>
                    {{ __('gallery.licenses.' . $currentGalleryItem->license) }}
                </span>
            </div>

            @include('gallery.partials.rating', [
                'item' => $currentGalleryItem,
                'label' => __('gallery.rating.rate_image'),
            ])
        </div>
    </div>

    <div class="community-gallery-strip" aria-label="{{ __('gallery.viewer.thumbnails') }}">
        @foreach ($items as $item)
            <a
                class="community-gallery-strip-item @if ($item->is($currentGalleryItem)) is-active @endif"
                href="{{ $browserUrl($item) }}"
                data-gallery-browser-item
                data-title="{{ $item->title }}"
                data-description="{{ $item->description ?: __('gallery.show.no_description') }}"
                data-left-url="{{ $item->leftImageUrl() }}"
                data-right-url="{{ $item->rightImageUrl() }}"
                data-author="{{ $item->author_name }}"
                data-author-url="{{ route('gallery.index', [
                    'locale' => app()->getLocale(),
                    'author' => $item->author_name,
                ]) }}"
                data-license="{{ __('gallery.licenses.' . $item->license) }}"
                data-rating-summary="{{ $item->ratingSummary() }}"
                data-rated="{{ auth()->check() && $item->ratedByCurrentUser() ? 'true' : 'false' }}"
                @auth
                    data-rating-url="{{ route('gallery.ratings.store', [
                        'locale' => app()->getLocale(),
                        'galleryItem' => $item,
                    ]) }}"
                @endauth
            >
                <img
                    src="{{ $item->leftImageUrl() }}"
                    alt="{{ $item->title }}"
                    loading="lazy"
                >
                <span>{{ $item->title }}</span>
            </a>
        @endforeach
    </div>
</div>