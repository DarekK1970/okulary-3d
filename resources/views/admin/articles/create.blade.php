@extends('admin.layout')

@section('title', __('admin.articles.create_title') . ' — ' . __('admin.title'))
@section('page_heading', __('admin.articles.create_title'))

@section('content')
<section class="cms-page">
    <div class="cms-page-heading">
        <div>
            <span class="admin-eyebrow">{{ __('admin.articles.kicker') }}</span>
            <h1>{{ __('admin.articles.create_title') }}</h1>
            <p>{{ __('admin.articles.create_description') }}</p>
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
