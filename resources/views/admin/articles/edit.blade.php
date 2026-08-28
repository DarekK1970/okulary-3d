@extends('admin.layout')

@php
    $source = $article->sourceTranslation();
@endphp

@section('title', __('cms.articles.edit_title') . ' — ' . __('admin.title'))
@section('page_heading', __('cms.articles.edit_title'))

@section('content')
<section class="cms-page">
    <div class="cms-page-heading">
        <div>
            <span class="admin-eyebrow">{{ __('cms.articles.kicker') }}</span>
            <h1>{{ __('cms.articles.edit_title') }}</h1>
            <p>{{ $source?->title ?? $article->title }}</p>
        </div>

        <div class="cms-heading-actions">
            @foreach ($article->translations as $translation)
                @if ($article->status->value === 'published' && $translation->isPubliclyReady())
                    <a
                        class="cms-secondary-button"
                        target="_blank"
                        href="{{ route('articles.show', ['locale' => $translation->locale, 'slug' => $translation->slug]) }}"
                    >
                        {{ __('cms.articles.actions.preview') }} {{ strtoupper($translation->locale) }}
                    </a>
                @endif
            @endforeach

            <span class="cms-status cms-status-{{ $article->status->value }}">
                {{ __('cms.articles.statuses.' . $article->status->value) }}
            </span>
        </div>
    </div>

    <form
        method="post"
        action="{{ route('admin.articles.update', $article) }}"
        enctype="multipart/form-data"
        data-article-form
    >
        @csrf
        @method('PUT')
        @include('admin.articles._form')
    </form>
</section>
@endsection
