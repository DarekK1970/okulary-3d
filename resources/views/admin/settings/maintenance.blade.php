@extends('admin.layout')

@section('title', __('maintenance.title') . ' — ' . __('admin.title'))
@section('page_heading', __('maintenance.title'))

@section('content')
<section class="commerce-settings-page">
    <div class="cms-page-heading">
        <div>
            <span class="admin-eyebrow">{{ __('maintenance.kicker') }}</span>
            <h1>{{ __('maintenance.title') }}</h1>
            <p>{{ __('maintenance.description') }}</p>
        </div>
    </div>

    <form
        method="post"
        action="{{ route('admin.settings.maintenance.update') }}"
        class="commerce-settings-form"
    >
        @csrf
        @method('PUT')

        <section class="cms-panel commerce-settings-panel">
            <div class="commerce-settings-heading">
                <div>
                    <span class="admin-eyebrow">
                        {{ __('maintenance.section.kicker') }}
                    </span>
                    <h2>{{ __('maintenance.section.title') }}</h2>
                    <p>{{ __('maintenance.section.description') }}</p>
                </div>

                <span class="commerce-config-status {{ $maintenanceEnabled ? 'is-ready' : '' }}">
                    {{ $maintenanceEnabled
                        ? __('maintenance.status.enabled')
                        : __('maintenance.status.disabled') }}
                </span>
            </div>

            <div class="commerce-settings-grid">
                <label class="cms-checkbox commerce-toggle commerce-full-width">
                    <input
                        type="checkbox"
                        name="enabled"
                        value="1"
                        @checked(old('enabled', $maintenanceEnabled))
                    >
                    <span>{{ __('maintenance.form.enabled') }}</span>
                </label>

                <div class="cms-field commerce-full-width">
                    <label for="maintenance-allowed-ips">
                        {{ __('maintenance.form.allowed_ips') }}
                    </label>
                    <textarea
                        id="maintenance-allowed-ips"
                        name="allowed_ips"
                        rows="8"
                        maxlength="5000"
                        spellcheck="false"
                        placeholder="203.0.113.10&#10;2001:db8::10"
                    >{{ old('allowed_ips', $allowedIpText) }}</textarea>
                    <small>
                        {{ __('maintenance.form.allowed_ips_help') }}
                    </small>
                </div>
            </div>

            <div class="commerce-security-note">
                <strong>{{ __('maintenance.current_ip.title') }}</strong>
                <p>
                    {{ __('maintenance.current_ip.description') }}
                    <code>{{ $currentIp }}</code>
                </p>
            </div>

            <div class="commerce-security-note">
                <strong>{{ __('maintenance.safety.title') }}</strong>
                <p>{{ __('maintenance.safety.description') }}</p>
            </div>
        </section>

        <div class="commerce-settings-actions">
            <button class="cms-primary-button" type="submit">
                {{ __('maintenance.form.save') }}
            </button>
        </div>
    </form>
</section>
@endsection
