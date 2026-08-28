@extends('admin.layout')

@section('title', __('media.edit.title') . ' — ' . __('admin.title'))
@section('page_heading', __('media.edit.title'))

@section('content')
<section class="media-page">
    <div class="cms-page-heading">
        <div>
            <span class="admin-eyebrow">{{ __('media.kicker') }}</span>
            <h1>{{ __('media.edit.title') }}</h1>
            <p>{{ $media->original_name }}</p>
        </div>

        <a class="cms-secondary-button" href="{{ route('admin.media.index') }}">
            ← {{ __('media.edit.back') }}
        </a>
    </div>

    <div class="media-edit-grid">
        <section class="media-preview-panel">
            <img
                src="{{ $media->url() }}"
                alt="{{ $media->alt_text ?: ($media->title ?: $media->original_name) }}"
            >

            <div class="media-technical-data">
                <div>
                    <span>{{ __('media.edit.filename') }}</span>
                    <strong>{{ $media->original_name }}</strong>
                </div>
                <div>
                    <span>{{ __('media.edit.dimensions') }}</span>
                    <strong>{{ $media->width && $media->height ? $media->width . ' × ' . $media->height . ' px' : '—' }}</strong>
                </div>
                <div>
                    <span>{{ __('media.edit.size') }}</span>
                    <strong>{{ $media->humanSize() }}</strong>
                </div>
                <div>
                    <span>{{ __('media.edit.type') }}</span>
                    <strong>{{ $media->mime_type ?: '—' }}</strong>
                </div>
                <div>
                    <span>{{ __('media.edit.folder') }}</span>
                    <strong>{{ $media->folder }}</strong>
                </div>
                <div>
                    <span>{{ __('media.edit.usage') }}</span>
                    <strong>{{ $media->hero_articles_count }}</strong>
                </div>
            </div>
        </section>

        <section class="cms-panel">
            <h2>{{ __('media.edit.metadata') }}</h2>

            <form
                method="post"
                action="{{ route('admin.media.update', $media) }}"
                class="media-metadata-form"
            >
                @csrf
                @method('PUT')

                <div class="cms-field">
                    <label for="title">{{ __('media.fields.title') }}</label>
                    <input
                        id="title"
                        name="title"
                        type="text"
                        value="{{ old('title', $media->title) }}"
                        maxlength="180"
                    >
                </div>

                <div class="cms-field">
                    <label for="alt_text">{{ __('media.fields.alt') }}</label>
                    <input
                        id="alt_text"
                        name="alt_text"
                        type="text"
                        value="{{ old('alt_text', $media->alt_text) }}"
                        maxlength="255"
                    >
                    <small>{{ __('media.fields.alt_help') }}</small>
                </div>

                <div class="cms-field">
                    <label for="caption">{{ __('media.fields.caption') }}</label>
                    <textarea
                        id="caption"
                        name="caption"
                        rows="5"
                        maxlength="2000"
                    >{{ old('caption', $media->caption) }}</textarea>
                </div>

                <div class="cms-field">
                    <label for="folder">{{ __('media.fields.folder') }}</label>
                    <input
                        id="folder"
                        name="folder"
                        type="text"
                        value="{{ old('folder', $media->folder) }}"
                        list="media-folders"
                        maxlength="120"
                        required
                    >
                    <datalist id="media-folders">
                        @foreach ($folders as $folder)
                            <option value="{{ $folder }}">
                        @endforeach
                    </datalist>
                </div>

                <button class="cms-primary-button" type="submit">
                    {{ __('media.actions.save') }}
                </button>
            </form>

            <div class="media-delete-zone">
                <strong>{{ __('media.delete.title') }}</strong>

                @if ($media->hero_articles_count > 0)
                    <p>{{ __('media.delete.in_use', ['count' => $media->hero_articles_count]) }}</p>
                @else
                    <p>{{ __('media.delete.description') }}</p>

                    <form
                        method="post"
                        action="{{ route('admin.media.destroy', $media) }}"
                        onsubmit="return confirm('{{ __('media.delete.confirm') }}')"
                    >
                        @csrf
                        @method('DELETE')

                        <button class="cms-danger-button" type="submit">
                            {{ __('media.actions.delete') }}
                        </button>
                    </form>
                @endif
            </div>
        </section>
    </div>
</section>
@endsection
