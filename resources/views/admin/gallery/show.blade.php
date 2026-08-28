@extends('admin.layout')

@section('title', $galleryItem->title . ' — ' . __('gallery.admin.title'))
@section('page_heading', $galleryItem->title)

@section('content')
<section class="admin-gallery-page">
    <div class="cms-page-heading">
        <div>
            <span class="admin-eyebrow">{{ __('gallery.admin.review') }}</span>
            <h1>{{ $galleryItem->title }}</h1>
            <p>
                {{ $galleryItem->author_name }}
                · {{ $galleryItem->created_at->format('d.m.Y H:i') }}
            </p>
        </div>

        <a
            class="cms-secondary-button"
            href="{{ route('admin.gallery.index') }}"
        >
            ← {{ __('gallery.admin.back') }}
        </a>
    </div>

    <div class="admin-gallery-review-grid">
        <div>
            <section class="cms-panel">
                <h2>{{ __('gallery.admin.stereo_pair') }}</h2>

                <div class="admin-gallery-pair">
                    <figure>
                        <img
                            src="{{ $galleryItem->leftImageUrl() }}"
                            alt=""
                        >
                        <figcaption>
                            L —
                            {{ $galleryItem->left_width ?: '—' }}
                            ×
                            {{ $galleryItem->left_height ?: '—' }}
                            px
                        </figcaption>
                    </figure>

                    <figure>
                        <img
                            src="{{ $galleryItem->rightImageUrl() }}"
                            alt=""
                        >
                        <figcaption>
                            R —
                            {{ $galleryItem->right_width ?: '—' }}
                            ×
                            {{ $galleryItem->right_height ?: '—' }}
                            px
                        </figcaption>
                    </figure>
                </div>
            </section>

            <section class="cms-panel">
                <h2>{{ __('gallery.admin.description_label') }}</h2>

                <p class="admin-gallery-description">
                    {{ $galleryItem->description ?: __('gallery.admin.no_description') }}
                </p>

                <div class="admin-gallery-data">
                    <div>
                        <span>{{ __('gallery.admin.author') }}</span>
                        <strong>{{ $galleryItem->author_name }}</strong>
                    </div>

                    <div>
                        <span>{{ __('gallery.admin.license') }}</span>
                        <strong>{{ __('gallery.licenses.' . $galleryItem->license) }}</strong>
                    </div>

                    <div>
                        <span>{{ __('gallery.admin.account') }}</span>
                        <strong>{{ $galleryItem->user?->name }}</strong>
                        <small>{{ $galleryItem->user?->email }}</small>
                    </div>

                    <div>
                        <span>{{ __('gallery.admin.rights_confirmed') }}</span>
                        <strong>{{ $galleryItem->rights_confirmed_at?->format('d.m.Y H:i') }}</strong>
                    </div>
                </div>
            </section>
        </div>

        <aside>
            <section class="cms-panel admin-gallery-moderation">
                <h2>{{ __('gallery.admin.moderation') }}</h2>

                <div class="admin-gallery-current-status">
                    <span>{{ __('gallery.admin.current_status') }}</span>
                    <strong class="gallery-status gallery-status-{{ $galleryItem->status->value }}">
                        {{ __('gallery.statuses.' . $galleryItem->status->value) }}
                    </strong>
                </div>

                <form
                    method="post"
                    action="{{ route('admin.gallery.update', $galleryItem) }}"
                >
                    @csrf
                    @method('PATCH')

                    <div class="cms-field">
                        <label>{{ __('gallery.admin.new_status') }}</label>
                        <select name="status" required>
                            @foreach (\App\Enums\GalleryStatus::cases() as $status)
                                <option
                                    value="{{ $status->value }}"
                                    @selected(old('status', $galleryItem->status->value) === $status->value)
                                >
                                    {{ __('gallery.statuses.' . $status->value) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="cms-field">
                        <label>{{ __('gallery.admin.moderation_note') }}</label>
                        <textarea
                            name="moderation_note"
                            rows="7"
                            maxlength="2000"
                        >{{ old('moderation_note', $galleryItem->moderation_note) }}</textarea>
                    </div>

                    <button class="cms-primary-button" type="submit">
                        {{ __('gallery.admin.save') }}
                    </button>
                </form>

                @if ($galleryItem->moderator)
                    <div class="admin-gallery-moderator">
                        {{ __('gallery.admin.last_moderation') }}:
                        <strong>{{ $galleryItem->moderator->name }}</strong>
                        · {{ $galleryItem->moderated_at?->format('d.m.Y H:i') }}
                    </div>
                @endif

                @if ($galleryItem->status === \App\Enums\GalleryStatus::Published)
                    <a
                        class="cms-secondary-button admin-gallery-public-link"
                        target="_blank"
                        href="{{ route('gallery.show', [
                            'locale' => config('locales.default', 'pl'),
                            'galleryItem' => $galleryItem
                        ]) }}"
                    >
                        {{ __('gallery.admin.open_public') }} ↗
                    </a>
                @endif
            </section>
        </aside>
    </div>
</section>
@endsection
