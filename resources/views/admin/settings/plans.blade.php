@extends('admin.layout')
@section('title', __('plans.admin.title')) @section('page_heading', __('plans.admin.title'))
@section('content')<section class="admin-card"><h2>{{ __('plans.admin.heading') }}</h2><p>{{ __('plans.admin.help') }}</p>@if(session('status'))<div class="admin-alert-success">{{ session('status') }}</div>@endif
<form method="post" action="{{ route('admin.settings.plans.update') }}" class="admin-settings-form">@csrf @method('PUT')
@foreach(['free','pro','premium'] as $key)<fieldset><legend>{{ strtoupper($key) }}</legend>@if($key !== 'free')<label>{{ __('plans.admin.price') }}<input type="number" step="0.01" min="0" name="{{ $key }}_price" value="{{ old($key.'_price',$plans[$key]['price']) }}" required></label>@endif<label>{{ __('plans.admin.tokens') }}<input type="number" min="0" name="{{ $key }}_tokens" value="{{ old($key.'_tokens',$plans[$key]['tokens']) }}" required></label></fieldset>@endforeach
<button class="admin-primary-button" type="submit">{{ __('plans.admin.save') }}</button></form></section>@endsection
