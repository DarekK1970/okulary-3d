@extends('layouts.app')

@section('title', __('portal_auth.login.title') . ' — ' . __('site.title'))

@section('content')
<section class="auth-page">
    <div class="site-container auth-container">
        <div class="auth-card">
            <div class="auth-heading">
                <span class="auth-kicker">{{ __('portal_auth.common.account') }}</span>
                <h1>{{ __('portal_auth.login.title') }}</h1>
                <p>{{ __('portal_auth.login.description') }}</p>
            </div>

            @if (session('status'))
                <div class="form-alert form-alert-success">{{ session('status') }}</div>
            @endif

            <form method="post" action="{{ route('login.store', ['locale' => app()->getLocale()]) }}" class="auth-form">
                @csrf

                <div class="form-field">
                    <label for="email">{{ __('portal_auth.fields.email') }}</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email">
                    @error('email') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-field">
                    <div class="label-row">
                        <label for="password">{{ __('portal_auth.fields.password') }}</label>
                        <a href="{{ route('password.request', ['locale' => app()->getLocale()]) }}">
                            {{ __('portal_auth.login.forgot') }}
                        </a>
                    </div>
                    <input id="password" name="password" type="password" required autocomplete="current-password">
                    @error('password') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <label class="checkbox-field">
                    <input type="checkbox" name="remember" value="1">
                    <span>{{ __('portal_auth.login.remember') }}</span>
                </label>

                <button class="auth-submit" type="submit">{{ __('portal_auth.login.submit') }}</button>
            </form>

            <div class="auth-switch">
                {{ __('portal_auth.login.no_account') }}
                <a href="{{ route('register', ['locale' => app()->getLocale()]) }}">
                    {{ __('portal_auth.login.register') }}
                </a>
            </div>
        </div>
    </div>
</section>
@endsection
