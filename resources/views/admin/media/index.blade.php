@extends('admin.layout')

@section('title', __('media.title') . ' — ' . __('admin.title'))
@section('page_heading', __('media.title'))

@section('content')
<section class="media-page">
    <div class="cms-page-heading">
        <div>
            <span class="admin-eyebrow">{{ __('media.kicker') }}</span>
            <h1>{{ __('media.title') }}</h1>
            <p>{{ __('media.description') }}</p>
        </div>
    </div>

    <section class="media-upload-panel">
        <div>
            <span class="media-panel-icon">＋</span>
            <div>
                <strong>{{ __('media.upload.title') }}</strong>
                <p>{{ __('media.upload.description') }}</p>
            </div>
        </div>

        <form
            method="post"
            action="{{ route('admin.media.store') }}"
            enctype="multipart/form-data"
            class="media-upload-form"
        >
            @csrf

            <input
                type="text"
                name="folder"
                value="{{ old('folder', 'general') }}"
                placeholder="{{ __('media.upload.folder') }}"
                maxlength="120"
            >

            <label class="media-file-button">
                <input
                    type="file"
                    name="files[]"
                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                    multiple
                    required
                >
                <span>{{ __('media.upload.choose') }}</span>
            </label>

            <button class="cms-primary-button" type="submit">
                {{ __('media.upload.submit') }}
            </button>
        </form>
    </section>

    <form class="media-filter-bar" method="get" action="{{ route('admin.media.index') }}">
        <input
            type="search"
            name="q"
            value="{{ request('q') }}"
            placeholder="{{ __('media.filters.search') }}"
        >

        <select name="folder">
            <option value="">{{ __('media.filters.all_folders') }}</option>
            @foreach ($folders as $folder)
                <option value="{{ $folder }}" @selected(request('folder') === $folder)>
                    {{ $folder }}
                </option>
            @endforeach
        </select>

        <button type="submit">{{ __('media.filters.apply') }}</button>

        @if (request()->hasAny(['q', 'folder']))
            <a href="{{ route('admin.media.index') }}">
                {{ __('media.filters.clear') }}
            </a>
        @endif
    </form>

    <div class="media-grid">
        @forelse ($mediaAssets as $media)
            <article class="media-card">
                <a
                    class="media-card-image"
                    href="{{ route('admin.media.edit', $media) }}"
                >
                    <img
                        src="{{ $media->url() }}"
                        alt="{{ $media->alt_text ?: ($media->title ?: $media->original_name) }}"
                        loading="lazy"
                    >
                </a>

                <div class="media-card-body">
                    <div class="media-card-title">
                        <strong>{{ $media->title ?: $media->original_name }}</strong>
                        <span>{{ $media->original_name }}</span>
                    </div>

                    <div class="media-card-meta">
                        <span>{{ $media->width && $media->height ? $media->width . '×' . $media->height : '—' }}</span>
                        <span>{{ $media->humanSize() }}</span>
                        <span>{{ strtoupper($media->extension ?: '?') }}</span>
                    </div>

                    <div class="media-folder"># {{ $media->folder }}</div>

                    <div class="media-card-footer">
                        <span>
                            {{ trans_choice('media.usage', $media->hero_articles_count, ['count' => $media->hero_articles_count]) }}
                        </span>

                        <a href="{{ route('admin.media.edit', $media) }}">
                            {{ __('media.actions.edit') }}
                        </a>
                    </div>
                </div>
            </article>
        @empty
            <div class="media-empty">
                <strong>{{ __('media.empty.title') }}</strong>
                <p>{{ __('media.empty.description') }}</p>
            </div>
        @endforelse
    </div>

    @if ($mediaAssets->hasPages())
        <div class="cms-pagination">
            {{ $mediaAssets->links() }}
        </div>
    @endif
</section>
@endsection
