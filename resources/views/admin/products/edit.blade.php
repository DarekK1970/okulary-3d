@extends('admin.layout')

@section('title', __('catalog.admin.products.edit_title') . ' — ' . __('admin.title'))
@section('page_heading', __('catalog.admin.products.edit_title'))

@section('content')
<section class="catalog-admin-page">
    <div class="cms-page-heading">
        <div>
            <span class="admin-eyebrow">{{ __('catalog.admin.kicker') }}</span>
            <h1>{{ __('catalog.admin.products.edit_title') }}</h1>
            <p>{{ $product->sourceTranslation()?->name }}</p>
        </div>

        <div class="catalog-heading-actions">
            @foreach ($product->translations as $translation)
                @if ($product->status->value === 'active' && $translation->isPubliclyReady())
                    <a
                        class="cms-secondary-button"
                        target="_blank"
                        href="{{ route('shop.show', ['locale' => $translation->locale, 'slug' => $translation->slug]) }}"
                    >
                        {{ __('catalog.admin.products.preview') }} {{ strtoupper($translation->locale) }}
                    </a>
                @endif
            @endforeach
        </div>
    </div>

    <form
        method="post"
        action="{{ route('admin.products.update', $product) }}"
        enctype="multipart/form-data"
    >
        @csrf
        @method('PUT')
        @include('admin.products._form')
    </form>
</section>
@endsection
