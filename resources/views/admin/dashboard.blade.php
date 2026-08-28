@extends('admin.layout')

@section('title', __('admin.dashboard.title') . ' — ' . __('admin.title'))
@section('page_heading', __('admin.dashboard.title'))

@section('content')
    <section class="admin-welcome">
        <div>
            <span class="admin-eyebrow">{{ __('admin.dashboard.eyebrow') }}</span>
            <h1>{{ __('admin.dashboard.welcome', ['name' => $user->name]) }}</h1>
            <p>{{ __('admin.dashboard.description') }}</p>
        </div>

        <div class="admin-role-badge">
            <span>{{ __('admin.dashboard.your_role') }}</span>
            <strong>{{ __('portal_auth.roles.' . $user->role) }}</strong>
        </div>
    </section>

    <section class="admin-stats" aria-label="{{ __('admin.dashboard.stats') }}">
        <article class="admin-stat">
            <span>{{ __('admin.dashboard.users') }}</span>
            <strong>{{ $stats['users'] }}</strong>
        </article>
        <article class="admin-stat">
            <span>{{ __('admin.dashboard.articles') }}</span>
            <strong>{{ $stats['articles'] }}</strong>
        </article>
        <article class="admin-stat">
            <span>{{ __('admin.dashboard.published') }}</span>
            <strong>{{ $stats['published'] }}</strong>
        </article>
        <article class="admin-stat">
            <span>{{ __('admin.dashboard.media') }}</span>
            <strong>{{ $stats['media'] }}</strong>
        </article>
    </section>

    <section class="admin-section-block">
        <div class="admin-section-heading">
            <div>
                <span>{{ __('admin.dashboard.modules_kicker') }}</span>
                <h2>{{ __('admin.dashboard.modules') }}</h2>
            </div>
        </div>

        <div class="admin-module-grid">
            @foreach ($cards as $card)
                @php
                    $allowed = in_array($user->role, $card['roles'], true);
                @endphp

                <article class="admin-module-card {{ $allowed ? '' : 'is-locked' }}">
                    <div class="admin-module-icon">{{ $card['icon'] }}</div>

                    <div class="admin-module-copy">
                        <h3>{{ __('admin.modules.' . $card['key'] . '.title') }}</h3>
                        <p>{{ __('admin.modules.' . $card['key'] . '.description') }}</p>
                    </div>

                    @if ($allowed)
                        <a href="{{ route($card['route']) }}">
                            {{ __('admin.open') }} →
                        </a>
                    @else
                        <span class="admin-locked">
                            {{ __('admin.no_permission') }}
                        </span>
                    @endif
                </article>
            @endforeach
        </div>
    </section>
@endsection
