@extends('admin.layout')

@section('title', __('admin.articles.edit_title') . ' — ' . __('admin.title'))
@section('page_heading', __('admin.articles.edit_title'))

@section('content')
<section class="cms-page">
    <div class="cms-page-heading">
        <div>
            <span class="admin-eyebrow">{{ __('admin.articles.kicker') }}</span>
            <h1>{{ __('admin.articles.edit_title') }}</h1>
            <p>{{ $article->title }}</p>
        </div>

        <span class="cms-status cms-status-{{ $article->status->value }}">
            {{ __('admin.articles.statuses.' . $article->status->value) }}
        </span>
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
