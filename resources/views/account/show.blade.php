@extends('layouts.app')

@section('title', __('portal_auth.account.title') . ' — ' . __('site.title'))

@section('content')
<section class="account-page">
    <div class="site-container account-container">
        <div class="account-heading">
            <div>
                <span class="auth-kicker">{{ __('portal_auth.common.account') }}</span>
                <h1>{{ __('portal_auth.account.title') }}</h1>
                <p>{{ __('portal_auth.account.welcome', ['name' => $user->name]) }}</p>
            </div>

            <div class="account-heading-actions">
                @if ($user->canAccessAdminPanel())
                    <a class="admin-panel-button" href="{{ route('admin.dashboard') }}">
                        {{ __('portal_auth.account.admin_panel') }}
                    </a>
                @endif

                <form method="post" action="{{ route('logout', ['locale' => app()->getLocale()]) }}">
                    @csrf
                    <button class="logout-button" type="submit">{{ __('portal_auth.account.logout') }}</button>
                </form>
            </div>
        </div>

        @if (session('status'))
            <div class="form-alert form-alert-success account-alert">{{ session('status') }}</div>
        @endif

        <div class="account-grid">
            <section class="account-card">
                <div class="account-card-heading">
                    <h2>{{ __('portal_auth.account.profile_title') }}</h2>
                    <span>{{ __('portal_auth.account.role') }}: {{ __('portal_auth.roles.' . $user->role) }}</span>
                </div>

                <form method="post" action="{{ route('account.profile.update', ['locale' => app()->getLocale()]) }}" class="auth-form">
                    @csrf
                    @method('PUT')

                    <div class="form-field">
                        <label for="name">{{ __('portal_auth.fields.name') }}</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autocomplete="name">
                        @error('name') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-field">
                        <label for="email">{{ __('portal_auth.fields.email') }}</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="email">
                        @error('email') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <button class="auth-submit auth-submit-secondary" type="submit">
                        {{ __('portal_auth.account.save_profile') }}
                    </button>
                </form>
            </section>

            <section class="account-card">
                <div class="account-card-heading">
                    <h2>{{ __('portal_auth.account.password_title') }}</h2>
                    <span>{{ __('portal_auth.account.password_description') }}</span>
                </div>

                <form method="post" action="{{ route('account.password.update', ['locale' => app()->getLocale()]) }}" class="auth-form">
                    @csrf
                    @method('PUT')

                    <div class="form-field">
                        <label for="current_password">{{ __('portal_auth.fields.current_password') }}</label>
                        <input id="current_password" name="current_password" type="password" required autocomplete="current-password">
                        @error('current_password') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-field">
                        <label for="new_password">{{ __('portal_auth.fields.new_password') }}</label>
                        <input id="new_password" name="password" type="password" required autocomplete="new-password">
                        @error('password') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-field">
                        <label for="new_password_confirmation">{{ __('portal_auth.fields.password_confirmation') }}</label>
                        <input id="new_password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
                    </div>

                    <button class="auth-submit auth-submit-secondary" type="submit">
                        {{ __('portal_auth.account.save_password') }}
                    </button>
                </form>
            </section>
        </div>
    </div>
</section>
@endsection
