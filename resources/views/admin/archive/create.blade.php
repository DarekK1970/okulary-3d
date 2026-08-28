@extends('admin.layout')

@section('title', __('archive.admin.create_title') . ' — ' . __('archive.admin.title'))
@section('page_heading', __('archive.admin.create_title'))

@push('head')
    @vite('resources/js/archive-admin.js')
@endpush

@section('content')
<section class="admin-archive-page">
    <div class="cms-page-heading">
        <div>
            <span class="admin-eyebrow">{{ __('archive.admin.kicker') }}</span>
            <h1>{{ __('archive.admin.create_title') }}</h1>
            <p>{{ __('archive.admin.create_description') }}</p>
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
        action="{{ route('admin.archive.store') }}"
        enctype="multipart/form-data"
        data-archive-admin-form
    >
        @csrf

        @include('admin.archive._form', [
            'archiveItem' => null
        ])

        <div class="admin-archive-submit-row">
            <button class="cms-primary-button" type="submit">
                {{ __('archive.admin.create') }}
            </button>
        </div>
    </form>
</section>
@endsection
