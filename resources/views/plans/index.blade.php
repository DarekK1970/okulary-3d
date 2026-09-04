@extends('layouts.app')
@section('title', __('plans.title').' — '.__('site.title'))
@push('head')
    @vite('resources/css/plans.css')
@endpush
@section('content')
<section class="plans-page"><div class="site-container plans-container">
<header class="plans-heading"><span class="plans-kicker">AI LENTICULAR STUDIO</span><h1>{{ __('plans.title') }}</h1><p>{{ __('plans.intro') }}</p><div class="plans-trust-row"><span>✓ {{ __('plans.benefits.three_months') }}</span><span>✓ {{ __('plans.benefits.secure_payment') }}</span><span>✓ {{ __('plans.benefits.file_storage') }}</span></div></header>
@if($errors->any())<div class="form-alert plans-alert">{{ $errors->first() }}</div>@endif
<div class="plans-grid">@foreach(['free','pro','premium'] as $key)
<article class="plan-card is-{{ $key }}">@if($key === 'premium')<span class="plan-recommended">{{ __('plans.recommended') }}</span>@endif
<header class="plan-card-header"><span class="plan-icon" aria-hidden="true">{{ $key === 'free' ? '◇' : ($key === 'pro' ? '✦' : '◆') }}</span><span class="plan-label">{{ strtoupper($key) }}</span><h2>{{ __('plans.'.$key.'.title') }}</h2><p>{{ __('plans.'.$key.'.description') }}</p></header>
<div class="plan-price-wrap"><strong class="plan-price">{{ number_format($plans[$key]['price'],2,',',' ') }}</strong><span><b>PLN</b>{{ __('plans.period') }}</span></div>
<div class="plan-tokens"><span aria-hidden="true">◉</span><div><strong>{{ $plans[$key]['tokens'] }} TOKEN_LENS</strong><small>{{ __('plans.tokens_included') }}</small></div></div>
<div class="plan-features"><h3>{{ __('plans.included') }}</h3><ul>@foreach(__('plans.'.$key.'.features') as $feature)<li><span aria-hidden="true">✓</span>{{ $feature }}</li>@endforeach</ul></div>
<div class="plan-card-action">@if($key === 'free')<a class="plan-button is-free" href="{{ route('lab.lenticular.studio',['locale'=>app()->getLocale()]) }}">{{ __('plans.use_free') }} <span>→</span></a>@else
<form method="post" action="{{ route('plans.purchase',['locale'=>app()->getLocale()]) }}">@csrf<input type="hidden" name="plan" value="{{ $key }}"><label class="plan-renew"><input type="hidden" name="auto_renew" value="0"><input type="checkbox" name="auto_renew" value="1" checked><span>{{ __('plans.auto_renew') }}</span></label><button class="plan-button" type="submit">{{ __('plans.choose',['plan'=>strtoupper($key)]) }} <span>→</span></button><small class="plan-payment-note">{{ __('plans.paynow_note') }}</small></form>@endif</div>
</article>@endforeach</div><div class="plans-storage"><span aria-hidden="true">⌁</span><p>{{ __('plans.storage') }}</p></div>
</div></section>
@endsection
