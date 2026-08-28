@extends('admin.layout')

@section('title', __('cms.articles.create_title') . ' — ' . __('admin.title'))
@section('page_heading', __('cms.articles.create_title'))

@section('content')
<section class="cms-page">
    <div class="cms-page-heading">
        <div>
            <span class="admin-eyebrow">{{ __('cms.articles.kicker') }}</span>
            <h1>{{ __('cms.articles.create_title') }}</h1>
            <p>{{ __('cms.articles.create_description') }}</p>
        </div>
    </div>

    <form
        method="post"
        action="{{ route('admin.articles.store') }}"
        enctype="multipart/form-data"
        data-article-form
    >
        @csrf
        @include('admin.articles._form')
    </form>
</section>
@endsection
