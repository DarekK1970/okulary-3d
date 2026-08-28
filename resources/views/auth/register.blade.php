@extends('layouts.app')

@section('title', __('portal_auth.register.title') . ' — ' . __('site.title'))

@section('content')
<section class="auth-page">
    <div class="site-container auth-container">
        <div class="auth-card auth-card-wide">
            <div class="auth-heading">
                <span class="auth-kicker">{{ __('portal_auth.common.account') }}</span>
                <h1>{{ __('portal_auth.register.title') }}</h1>
                <p>{{ __('portal_auth.register.description') }}</p>
            </div>

            <form method="post" action="{{ route('register.store', ['locale' => app()->getLocale()]) }}" class="auth-form">
                @csrf

                <div class="form-field">
                    <label for="name">{{ __('portal_auth.fields.name') }}</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name">
                    @error('name') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-field">
                    <label for="email">{{ __('portal_auth.fields.email') }}</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email">
                    @error('email') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-grid-2">
                    <div class="form-field">
                        <label for="password">{{ __('portal_auth.fields.password') }}</label>
                        <input id="password" name="password" type="password" required autocomplete="new-password">
                        @error('password') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-field">
                        <label for="password_confirmation">{{ __('portal_auth.fields.password_confirmation') }}</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
                    </div>
                </div>

                <p class="form-help">{{ __('portal_auth.register.password_help') }}</p>

                <label class="checkbox-field checkbox-terms">
                    <input type="checkbox" name="terms" value="1" required>
                    <span>{!! __('portal_auth.register.terms') !!}</span>
                </label>
                @error('terms') <span class="field-error field-error-block">{{ $message }}</span> @enderror

                <button class="auth-submit" type="submit">{{ __('portal_auth.register.submit') }}</button>
            </form>

            <div class="auth-switch">
                {{ __('portal_auth.register.have_account') }}
                <a href="{{ route('login', ['locale' => app()->getLocale()]) }}">
                    {{ __('portal_auth.register.login') }}
                </a>
            </div>
        </div>
    </div>
</section>
@endsection
