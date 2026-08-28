@extends('layouts.app')

@section('title', __('portal_auth.forgot.title') . ' — ' . __('site.title'))

@section('content')
<section class="auth-page">
    <div class="site-container auth-container">
        <div class="auth-card">
            <div class="auth-heading">
                <span class="auth-kicker">{{ __('portal_auth.common.security') }}</span>
                <h1>{{ __('portal_auth.forgot.title') }}</h1>
                <p>{{ __('portal_auth.forgot.description') }}</p>
            </div>

            @if (session('status'))
                <div class="form-alert form-alert-success">{{ session('status') }}</div>
            @endif

            <form method="post" action="{{ route('password.email', ['locale' => app()->getLocale()]) }}" class="auth-form">
                @csrf

                <div class="form-field">
                    <label for="email">{{ __('portal_auth.fields.email') }}</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email">
                    @error('email') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <button class="auth-submit" type="submit">{{ __('portal_auth.forgot.submit') }}</button>
            </form>

            <div class="auth-switch">
                <a href="{{ route('login', ['locale' => app()->getLocale()]) }}">
                    ← {{ __('portal_auth.forgot.back') }}
                </a>
            </div>
        </div>
    </div>
</section>
@endsection
