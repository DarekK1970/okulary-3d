@extends('layouts.app')

@section('title', __('portal_auth.reset.title') . ' — ' . __('site.title'))

@section('content')
<section class="auth-page">
    <div class="site-container auth-container">
        <div class="auth-card">
            <div class="auth-heading">
                <span class="auth-kicker">{{ __('portal_auth.common.security') }}</span>
                <h1>{{ __('portal_auth.reset.title') }}</h1>
                <p>{{ __('portal_auth.reset.description') }}</p>
            </div>

            <form method="post" action="{{ route('password.update', ['locale' => app()->getLocale()]) }}" class="auth-form">
                @csrf

                <input type="hidden" name="token" value="{{ $token }}">

                <div class="form-field">
                    <label for="email">{{ __('portal_auth.fields.email') }}</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required autocomplete="email">
                    @error('email') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-field">
                    <label for="password">{{ __('portal_auth.fields.new_password') }}</label>
                    <input id="password" name="password" type="password" required autocomplete="new-password">
                    @error('password') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-field">
                    <label for="password_confirmation">{{ __('portal_auth.fields.password_confirmation') }}</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
                </div>

                <button class="auth-submit" type="submit">{{ __('portal_auth.reset.submit') }}</button>
            </form>
        </div>
    </div>
</section>
@endsection
