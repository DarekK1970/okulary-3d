@extends('layouts.app')

@section('title', __('gallery.create.meta_title') . ' — ' . __('site.title'))
@section('meta_description', __('gallery.create.meta_description'))

@push('head')
    @vite([
        'resources/css/gallery.css',
        'resources/js/gallery-upload.js'
    ])
@endpush

@section('content')
<section class="community-gallery-page">
    <div class="site-container">
        <nav class="gallery-breadcrumbs">
            <a href="{{ route('gallery.index', ['locale' => app()->getLocale()]) }}">
                {{ __('gallery.index.title') }}
            </a>
            <span>›</span>
            <span>{{ __('gallery.create.title') }}</span>
        </nav>

        <div class="gallery-submit-heading">
            <span class="gallery-kicker">{{ __('gallery.create.kicker') }}</span>
            <h1>{{ __('gallery.create.title') }}</h1>
            <p>{{ __('gallery.create.description') }}</p>
        </div>

        <form
            class="gallery-submit-form"
            method="post"
            action="{{ route('gallery.store', ['locale' => app()->getLocale()]) }}"
            enctype="multipart/form-data"
            data-gallery-upload-form
        >
            @csrf

            @if ($errors->any())
                <div class="gallery-form-errors" role="alert">
                    <strong>{{ __('gallery.create.errors_title') }}</strong>

                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="gallery-form-panel">
                <h2>{{ __('gallery.create.images_title') }}</h2>
                <p>{{ __('gallery.create.images_help') }}</p>

                <label class="gallery-file-type-field">
                    <span>{{ __('gallery.create.file_type_question') }}</span>
                    <select name="submission_type" data-gallery-upload-type>
                        <option value="stereo_pair" @selected(old('submission_type', 'stereo_pair') === 'stereo_pair')>
                            {{ __('gallery.create.file_type.stereo_pair') }}
                        </option>
                        <option value="mpo" @selected(old('submission_type') === 'mpo')>
                            {{ __('gallery.create.file_type.mpo') }}
                        </option>
                        <option value="left_right" @selected(old('submission_type') === 'left_right')>
                            {{ __('gallery.create.file_type.left_right') }}
                        </option>
                    </select>
                </label>

                <div class="gallery-upload-grid" data-gallery-single-upload>
                    <label class="gallery-upload-box">
                        <input
                            type="file"
                            name="source_image"
                            accept="image/jpeg,image/png,image/webp,.mpo"
                            data-gallery-upload-source
                        >

                        <span class="gallery-upload-side">S</span>

                        <strong>{{ __('gallery.create.source_image') }}</strong>
                        <span>{{ __('gallery.create.choose_image') }}</span>

                        <img
                            src=""
                            alt=""
                            data-gallery-preview="source"
                        >

                        <small data-gallery-filename="source">
                            {{ __('gallery.create.no_file') }}
                        </small>
                    </label>
                </div>

                <div class="gallery-upload-grid" data-gallery-split-upload hidden>
                    @foreach (['left', 'right'] as $side)
                        <label class="gallery-upload-box">
                            <input
                                type="file"
                                name="{{ $side }}_image"
                                accept="image/jpeg,image/png,image/webp"
                                data-gallery-upload="{{ $side }}"
                            >

                            <span class="gallery-upload-side">
                                {{ strtoupper(substr($side, 0, 1)) }}
                            </span>

                            <strong>{{ __('gallery.create.' . $side . '_image') }}</strong>
                            <span>{{ __('gallery.create.choose_image') }}</span>

                            <img
                                src=""
                                alt=""
                                data-gallery-preview="{{ $side }}"
                            >

                            <small data-gallery-filename="{{ $side }}">
                                {{ __('gallery.create.no_file') }}
                            </small>
                        </label>
                    @endforeach
                </div>
            </section>

            <section class="gallery-form-panel">
                <h2>{{ __('gallery.create.details_title') }}</h2>

                <div class="gallery-form-grid">
                    <label>
                        <span>{{ __('gallery.create.title_field') }}</span>
                        <input
                            type="text"
                            name="title"
                            value="{{ old('title') }}"
                            maxlength="160"
                            required
                        >
                    </label>

                    <label>
                        <span>{{ __('gallery.create.author_name') }}</span>
                        <input
                            type="text"
                            name="author_name"
                            value="{{ old('author_name', auth()->user()->name) }}"
                            maxlength="120"
                            required
                        >
                    </label>

                    <label>
                        <span>{{ __('gallery.create.license') }}</span>
                        <select name="license" required>
                            @foreach (['all_rights_reserved', 'cc_by', 'cc_by_sa', 'cc0'] as $license)
                                <option
                                    value="{{ $license }}"
                                    @selected(old('license', 'all_rights_reserved') === $license)
                                >
                                    {{ __('gallery.licenses.' . $license) }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="gallery-form-full">
                        <span>{{ __('gallery.create.description_field') }}</span>
                        <textarea
                            name="description"
                            rows="5"
                            maxlength="2000"
                        >{{ old('description') }}</textarea>
                    </label>
                </div>
            </section>

            <section class="gallery-form-panel gallery-rights-panel">
                <label class="gallery-rights-checkbox">
                    <input
                        type="checkbox"
                        name="rights_confirmation"
                        value="1"
                        required
                    >

                    <span>
                        <strong>{{ __('gallery.create.rights_title') }}</strong>
                        {{ __('gallery.create.rights_text') }}
                    </span>
                </label>

                <p>{{ __('gallery.create.moderation_note') }}</p>
            </section>

            <div class="gallery-submit-actions">
                <a
                    class="gallery-secondary-button"
                    href="{{ route('gallery.index', ['locale' => app()->getLocale()]) }}"
                >
                    {{ __('gallery.create.cancel') }}
                </a>

                <button class="gallery-primary-button" type="submit">
                    {{ __('gallery.create.submit') }}
                </button>
            </div>
        </form>
    </div>
</section>
@endsection
