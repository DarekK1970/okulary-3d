@extends('admin.layout')

@section('title', __('catalog.admin.products.create_title') . ' — ' . __('admin.title'))
@section('page_heading', __('catalog.admin.products.create_title'))

@section('content')
<section class="catalog-admin-page">
    <div class="cms-page-heading">
        <div>
            <span class="admin-eyebrow">{{ __('catalog.admin.kicker') }}</span>
            <h1>{{ __('catalog.admin.products.create_title') }}</h1>
            <p>{{ __('catalog.admin.products.create_description') }}</p>
        </div>
    </div>

    <form
        method="post"
        action="{{ route('admin.products.store') }}"
        enctype="multipart/form-data"
    >
        @csrf
        @include('admin.products._form')
    </form>
</section>
@endsection
