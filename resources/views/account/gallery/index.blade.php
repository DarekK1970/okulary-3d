@extends('layouts.app')

@section('title', __('gallery.account.title') . ' — ' . __('site.title'))

@push('head')
    @vite('resources/css/gallery.css')
@endpush

@section('content')
<section class="community-gallery-page">
    <div class="site-container">
        <div class="gallery-account-heading">
            <div>
                <span class="gallery-kicker">{{ __('gallery.account.kicker') }}</span>
                <h1>{{ __('gallery.account.title') }}</h1>
                <p>{{ __('gallery.account.description') }}</p>
            </div>

            <a
                class="gallery-primary-button"
                href="{{ route('gallery.create', ['locale' => app()->getLocale()]) }}"
            >
                {{ __('gallery.account.add') }}
            </a>
        </div>

        <div class="gallery-account-list">
            @forelse ($items as $item)
                <article class="gallery-account-item">
                    <img
                        src="{{ $item->leftImageUrl() }}"
                        alt=""
                        loading="lazy"
                    >

                    <div class="gallery-account-copy">
                        <h2>{{ $item->title }}</h2>
                        <span>{{ $item->created_at->format('d.m.Y H:i') }}</span>

                        @if ($item->moderation_note)
                            <p>
                                <strong>{{ __('gallery.account.moderation_note') }}:</strong>
                                {{ $item->moderation_note }}
                            </p>
                        @endif
                    </div>

                    <div class="gallery-account-actions">
                        <span class="gallery-status gallery-status-{{ $item->status->value }}">
                            {{ __('gallery.statuses.' . $item->status->value) }}
                        </span>

                        @if ($item->status === \App\Enums\GalleryStatus::Published)
                            <a
                                class="gallery-secondary-button"
                                href="{{ route('gallery.show', [
                                    'locale' => app()->getLocale(),
                                    'galleryItem' => $item
                                ]) }}"
                            >
                                {{ __('gallery.account.open') }}
                            </a>
                        @else
                            <form
                                method="post"
                                action="{{ route('account.gallery.destroy', [
                                    'locale' => app()->getLocale(),
                                    'galleryItem' => $item
                                ]) }}"
                                onsubmit="return confirm('{{ __('gallery.account.delete_confirm') }}')"
                            >
                                @csrf
                                @method('DELETE')

                                <button class="gallery-danger-button" type="submit">
                                    {{ __('gallery.account.delete') }}
                                </button>
                            </form>
                        @endif
                    </div>
                </article>
            @empty
                <div class="community-gallery-empty">
                    <strong>{{ __('gallery.account.empty_title') }}</strong>
                    <p>{{ __('gallery.account.empty_text') }}</p>
                </div>
            @endforelse
        </div>

        @if ($items->hasPages())
            <div class="community-gallery-pagination">
                {{ $items->links() }}
            </div>
        @endif
    </div>
</section>
@endsection
