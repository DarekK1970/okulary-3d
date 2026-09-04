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
                <section class="marketplace-category"><header><h2>{{ $category->name }}</h2>@if($category->description)<p>{{ $category->description }}</p>@endif</header>
                    <div class="marketplace-grid">@foreach($category->products as $product)<article class="marketplace-product">@if($product->imageUrl())<img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}">@else<div class="marketplace-placeholder">3D</div>@endif<div><span>{{ $product->print_size }}</span><h3>{{ $product->name }}</h3><p>{{ $product->short_description }}</p><footer><strong>{{ $product->token_cost }} TOKEN_LENS</strong><span>{{ __('marketplace.public.order_soon') }}</span></footer></div></article>@endforeach</div>
                </section>
            @endif
        @empty
            <div class="marketplace-empty"><h2>{{ __('marketplace.public.empty_title') }}</h2><p>{{ __('marketplace.public.empty_text') }}</p></div>
        @endforelse
    </div>
</section>
@endsection
