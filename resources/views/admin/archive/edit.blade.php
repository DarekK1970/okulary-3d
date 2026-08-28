@extends('admin.layout')

@section('title', __('archive.admin.edit_title') . ' — ' . __('archive.admin.title'))
@section('page_heading', __('archive.admin.edit_title'))

@push('head')
    @vite('resources/js/archive-admin.js')
@endpush

@section('content')
<section class="admin-archive-page">
    <div class="cms-page-heading">
        <div>
            <span class="admin-eyebrow">{{ __('archive.admin.kicker') }}</span>
            <h1>{{ __('archive.admin.edit_title') }}</h1>
            <p>{{ $archiveItem->translation($archiveItem->source_locale)?->title }}</p>
        </div>

        <a
            class="cms-secondary-button"
            href="{{ route('admin.archive.index') }}"
        >
            ← {{ __('archive.admin.back') }}
        </a>
    </div>

    <form
        method="post"
        action="{{ route('admin.archive.update', $archiveItem) }}"
        enctype="multipart/form-data"
        data-archive-admin-form
    >
        @csrf
        @method('PUT')

        @include('admin.archive._form', [
            'archiveItem' => $archiveItem
        ])

        <div class="admin-archive-submit-row">
            <button class="cms-primary-button" type="submit">
                {{ __('archive.admin.save') }}
            </button>
        </div>
    </form>

    <form
        class="admin-archive-delete-form"
        method="post"
        action="{{ route('admin.archive.destroy', $archiveItem) }}"
        onsubmit="return confirm('{{ __('archive.admin.delete_confirm') }}')"
    >
        @csrf
        @method('DELETE')

        <button class="admin-archive-delete-button" type="submit">
            {{ __('archive.admin.delete') }}
        </button>
    </form>
</section>
@endsection
