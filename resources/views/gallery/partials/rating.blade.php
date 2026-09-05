@php
    $isRated = auth()->check() && $item->ratedByCurrentUser();
    $ratingLabel = $label ?? __('gallery.rating.rate');
@endphp

<div class="gallery-rating-panel" data-gallery-rating-panel>
    <span class="gallery-rating-summary" data-gallery-rating-summary>
        {{ $item->ratingSummary() }}
    </span>

    @auth
        <form
            class="gallery-rating-form"
            method="post"
            action="{{ route('gallery.ratings.store', [
                'locale' => app()->getLocale(),
                'galleryItem' => $item,
            ]) }}"
            data-gallery-rating
            data-rated="{{ $isRated ? 'true' : 'false' }}"
        >
            @csrf
            <span>{{ $ratingLabel }}</span>

            @for ($rating = 1; $rating <= 5; $rating++)
                <button
                    class="gallery-rating-star @if ($isRated) is-muted @endif"
                    type="submit"
                    name="rating"
                    value="{{ $rating }}"
                    @disabled($isRated)
                    aria-label="{{ __('gallery.rating.rate_value', ['rating' => $rating]) }}"
                >★</button>
            @endfor
        </form>
    @else
        <a
            class="gallery-rating-login"
            href="{{ route('login', ['locale' => app()->getLocale()]) }}"
        >
            {{ __('gallery.rating.login_to_rate') }} ★★★★★
        </a>
    @endauth
</div>