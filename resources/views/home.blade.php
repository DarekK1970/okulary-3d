@extends('layouts.app')

@section('title', __('site.title'))
@section('meta_description', __('site.meta_description'))

@section('content')
    <section class="layout-preview">
        <div class="site-container layout-preview-inner">
            <span class="eyebrow">{{ __('site.layout_preview.eyebrow') }}</span>
            <h1>{{ __('site.headline') }}</h1>
            <p>{{ __('site.description') }}</p>

            <div class="layout-preview-note">
                {{ __('site.layout_preview.note') }}
            </div>
        </div>
    </section>
@endsection
