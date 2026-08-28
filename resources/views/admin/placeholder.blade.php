@extends('admin.layout')

@section('title', __('admin.sections.' . $section . '.title') . ' — ' . __('admin.title'))
@section('page_heading', __('admin.sections.' . $section . '.title'))

@section('content')
    <section class="admin-placeholder">
        <span class="admin-eyebrow">{{ __('admin.sections.' . $section . '.kicker') }}</span>
        <h1>{{ __('admin.sections.' . $section . '.title') }}</h1>
        <p>{{ __('admin.sections.' . $section . '.description') }}</p>

        <div class="admin-placeholder-box">
            <strong>{{ __('admin.placeholder.title') }}</strong>
            <p>{{ __('admin.placeholder.description') }}</p>
        </div>

        <a class="admin-back-button" href="{{ route('admin.dashboard') }}">
            ← {{ __('admin.placeholder.back') }}
        </a>
    </section>
@endsection
