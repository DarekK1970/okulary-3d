@extends('layouts.app')

@section('title', __('marketplace.public.title').' — '.__('site.title'))
@section('meta_description', __('marketplace.public.description'))

@push('head')
    @vite('resources/css/marketplace.css')
@endpush

@section('content')
<section class="marketplace-page">
    <div class="marketplace-hero"><div class="site-container"><span>3D LAB / MARKETPLACE</span><h1>{{ __('marketplace.public.title') }}</h1><p>{{ __('marketplace.public.description') }}</p><strong>{{ __('marketplace.public.tokens_only') }}</strong></div></div>
    <div class="site-container marketplace-content">
        @forelse($categories as $category)
            @if($category->products->isNotEmpty())
                @php($categoryTranslation = $category->publicTranslation(app()->getLocale()) ?? $category->sourceTranslation())
                <section class="marketplace-category"><header><h2>{{ $categoryTranslation?->name ?? $category->name }}</h2>@if($categoryTranslation?->description)<p>{{ $categoryTranslation->description }}</p>@endif</header>
                    <div class="marketplace-grid">@foreach($category->products as $product) @php($productTranslation = $product->publicTranslation(app()->getLocale()) ?? $product->sourceTranslation()) @php($matchingProject = $projectsByPrintSize->get($product->print_size)) <article class="marketplace-product">@if($product->imageUrl())<img src="{{ $product->imageUrl() }}" alt="{{ $productTranslation?->name ?? $product->name }}">@else<div class="marketplace-placeholder">3D</div>@endif<div><span>{{ $product->print_size }}</span><h3>{{ $productTranslation?->name ?? $product->name }}</h3><p>{{ $productTranslation?->short_description ?? $product->short_description }}</p><footer><strong>{{ $product->token_cost }} TOKEN_LENS</strong>@if($matchingProject)<a class="marketplace-product-cta" href="{{ route('lab.projects.show', ['locale' => app()->getLocale(), 'project' => $matchingProject]) }}">{{ __('marketplace.public.order_print') }}</a>@else<a class="marketplace-product-cta" href="{{ route('lab.lenticular.studio', ['locale' => app()->getLocale()]) }}">{{ __('marketplace.public.create_project') }} →</a>@endif</footer></div></article>@endforeach</div>
                </section>
            @endif
        @empty
            <div class="marketplace-empty"><h2>{{ __('marketplace.public.empty_title') }}</h2><p>{{ __('marketplace.public.empty_text') }}</p></div>
        @endforelse
    </div>
</section>
@endsection
