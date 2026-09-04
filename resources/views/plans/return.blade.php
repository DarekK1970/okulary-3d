@extends('layouts.app')
@section('title', __('plans.payment_title'))
@section('content')<main class="plans-page"><div class="site-container"><section class="plan-result"><h1>{{ __('plans.payment_title') }}</h1><p>{{ __('plans.status.'.$purchase->status) }}</p><a class="plan-button" href="{{ route('account',['locale'=>app()->getLocale()]) }}">{{ __('portal_auth.common.my_account') }}</a></section></div></main>@endsection
