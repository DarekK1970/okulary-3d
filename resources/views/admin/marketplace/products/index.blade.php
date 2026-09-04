@extends('admin.layout')
@section('title', __('marketplace.admin.products.title'))
@section('content')
<section class="market-admin">
    <header class="market-head"><div><span class="admin-eyebrow">MARKETPLACE</span><h1>{{ __('marketplace.admin.products.title') }}</h1><p>{{ __('marketplace.admin.products.description') }}</p></div><a class="market-button" href="{{ route('admin.marketplace.products.create') }}">+ {{ __('marketplace.admin.products.new') }}</a></header>
    <div class="market-table-wrap"><table class="market-table"><thead><tr><th>{{ __('marketplace.admin.products.image') }}</th><th>{{ __('marketplace.admin.products.name') }}</th><th>{{ __('marketplace.admin.products.languages') }}</th><th>{{ __('marketplace.admin.products.category') }}</th><th>{{ __('marketplace.admin.products.print_size') }}</th><th>TOKEN_LENS</th><th>{{ __('marketplace.admin.products.status') }}</th><th>{{ __('marketplace.admin.common.actions') }}</th></tr></thead><tbody>
    @forelse($products as $product)
        @php($source = $product->sourceTranslation())
        <tr><td>@if($product->imageUrl())<img class="market-thumb" src="{{ $product->imageUrl() }}" alt="">@else—@endif</td>
            <td><strong>{{ $source?->name ?? $product->name }}</strong><small class="market-table-subtitle">{{ $source?->short_description ?? $product->short_description }}</small></td>
            <td><div class="market-language-badges">@foreach(config('locales.supported', []) as $locale => $language) @php($translation = $product->translation($locale)) <span class="market-language-badge {{ $translation ? 'is-present' : '' }}">{{ strtoupper($locale) }} · {{ $translation ? __('ai_translator.target_statuses.'.$translation->translation_status->value) : __('ai_translator.target_statuses.missing') }}</span>@endforeach</div></td>
            <td>{{ $product->category->sourceTranslation()?->name ?? $product->category->name }}</td><td>{{ $product->print_size }}</td><td><strong>{{ $product->token_cost }} TL</strong></td><td>{{ __('marketplace.admin.common.'.($product->is_active ? 'active' : 'inactive')) }}</td>
            <td>@include('admin.marketplace._action-icons', ['item' => $product, 'editUrl' => route('admin.marketplace.products.edit', $product), 'translationType' => \App\Services\AiTranslationService::TYPE_MARKETPLACE_PRODUCT, 'canDelete' => true, 'deleteUrl' => route('admin.marketplace.products.destroy', $product)])</td>
        </tr>
    @empty<tr><td colspan="8">{{ __('marketplace.admin.products.empty') }}</td></tr>@endforelse
    </tbody></table></div>{{ $products->links() }}
</section>
@endsection
