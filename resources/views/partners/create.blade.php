@extends('layouts.app')

@section('title', ($translation->seo_title ?: $translation->title) . ' — ' . __('site.title'))
@section('meta_description', $translation->seo_description ?: __('partners.meta_description'))

@push('head')
<style>
    .partner-program-page {
        padding: 56px 0 84px;
        background:
            radial-gradient(circle at 10% 8%, rgba(0, 174, 234, .07), transparent 27%),
            radial-gradient(circle at 92% 20%, rgba(255, 48, 72, .05), transparent 24%),
            #fbfcfe;
    }
    .partner-program-grid {
        display: grid;
        grid-template-columns: minmax(0, .86fr) minmax(520px, 1.14fr);
        gap: 42px;
        align-items: start;
    }
    .partner-program-copy,
    .partner-form-card {
        border: 1px solid #e5eaf1;
        border-radius: 22px;
        background: rgba(255,255,255,.96);
        box-shadow: 0 18px 50px rgba(20, 34, 58, .06);
    }
    .partner-program-copy {
        padding: 34px;
    }
    .partner-kicker {
        display: block;
        margin-bottom: 12px;
        color: #0489bd;
        font-size: .72rem;
        font-weight: 850;
        letter-spacing: .12em;
        text-transform: uppercase;
    }
    .partner-program-copy h1 {
        margin: 0 0 20px;
        color: #101a33;
        font-size: clamp(2rem, 4vw, 3.5rem);
        line-height: 1.02;
    }
    .partner-editorial-copy {
        color: #5f6d82;
        font-size: .96rem;
        line-height: 1.75;
    }
    .partner-editorial-copy p:first-child {
        margin-top: 0;
    }
    .partner-editorial-copy p:last-child {
        margin-bottom: 0;
    }
    .partner-link-rule {
        display: grid;
        grid-template-columns: 44px 1fr;
        gap: 13px;
        margin-top: 26px;
        padding: 18px;
        border-radius: 16px;
        background: #f7fafc;
    }
    .partner-link-rule-icon {
        width: 44px;
        height: 44px;
        display: grid;
        place-items: center;
        border-radius: 50%;
        background: #fff;
        color: #0b86b6;
        font-weight: 900;
        box-shadow: 0 5px 14px rgba(16, 24, 44, .08);
    }
    .partner-link-rule strong,
    .partner-link-rule span {
        display: block;
    }
    .partner-link-rule strong {
        color: #17233d;
        font-size: .84rem;
    }
    .partner-link-rule span {
        margin-top: 4px;
        color: #6d788c;
        font-size: .76rem;
        line-height: 1.5;
    }
    .partner-form-card {
        padding: 30px;
    }
    .partner-form-card h2 {
        margin: 0;
        color: #101a33;
        font-size: 1.55rem;
    }
    .partner-form-intro {
        margin: 9px 0 24px;
        color: #6a7689;
        font-size: .84rem;
        line-height: 1.6;
    }
    .partner-status,
    .partner-errors {
        margin-bottom: 20px;
        padding: 14px 16px;
        border-radius: 12px;
        font-size: .78rem;
        line-height: 1.55;
    }
    .partner-status {
        border: 1px solid #b8e5c8;
        background: #f0fbf4;
        color: #22653b;
    }
    .partner-resend {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px;
        margin: -8px 0 20px;
    }
    .partner-resend button {
        min-height: 38px;
        padding: 0 14px;
        border: 1px solid #cfd8e4;
        border-radius: 9px;
        background: #fff;
        color: #24344d;
        font-weight: 750;
        cursor: pointer;
    }
    .partner-resend small {
        color: #7f8a9d;
        font-size: .68rem;
    }
    .partner-errors {
        border: 1px solid #f0c2c8;
        background: #fff5f6;
        color: #8d2633;
    }
    .partner-errors ul {
        margin: 8px 0 0 18px;
        padding: 0;
    }
    .partner-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 17px;
    }
    .partner-field {
        display: grid;
        gap: 7px;
        min-width: 0;
    }
    .partner-field-full {
        grid-column: 1 / -1;
    }
    .partner-field > span:first-child,
    .partner-choice-title {
        color: #27344b;
        font-size: .74rem;
        font-weight: 750;
    }
    .partner-field input[type="text"],
    .partner-field input[type="url"],
    .partner-field input[type="email"],
    .partner-field input[type="tel"],
    .partner-field input[type="file"],
    .partner-field textarea {
        width: 100%;
        min-height: 46px;
        padding: 11px 13px;
        border: 1px solid #dce3ec;
        border-radius: 11px;
        background: #fff;
        color: #26344c;
        font: inherit;
        font-size: .82rem;
        outline: none;
        transition: border-color .16s ease, box-shadow .16s ease;
    }
    .partner-field textarea {
        min-height: 104px;
        resize: vertical;
    }
    .partner-field input:focus,
    .partner-field textarea:focus {
        border-color: #18a6d7;
        box-shadow: 0 0 0 3px rgba(24, 166, 215, .10);
    }
    .partner-help {
        color: #8893a4;
        font-size: .66rem;
        line-height: 1.45;
    }
    .partner-radio-row {
        display: flex;
        flex-wrap: wrap;
        gap: 9px;
    }
    .partner-radio {
        display: flex;
        align-items: center;
        gap: 8px;
        min-height: 42px;
        padding: 0 13px;
        border: 1px solid #dce3ec;
        border-radius: 10px;
        background: #fff;
        color: #4c596e;
        font-size: .76rem;
        cursor: pointer;
    }
    .partner-consents {
        display: grid;
        gap: 12px;
        margin-top: 22px;
        padding-top: 20px;
        border-top: 1px solid #edf0f4;
    }
    .partner-check {
        display: grid;
        grid-template-columns: 20px 1fr;
        gap: 10px;
        align-items: start;
        color: #526077;
        font-size: .74rem;
        line-height: 1.55;
    }
    .partner-check input {
        width: 17px;
        height: 17px;
        margin-top: 2px;
        accent-color: #0b9dcc;
    }
    .partner-check a {
        color: #087fa9;
        font-weight: 750;
    }
    .partner-backlink-box {
        display: inline-flex;
        flex-wrap: wrap;
        gap: 7px;
        margin-top: 5px;
        padding: 7px 10px;
        border-radius: 8px;
        background: #f5f8fb;
        color: #1e2b44;
        font-weight: 750;
    }
    .partner-submit-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-top: 24px;
    }
    .partner-submit-row small {
        color: #929bab;
        font-size: .66rem;
    }
    .partner-submit-button {
        min-height: 48px;
        padding: 0 22px;
        border: 0;
        border-radius: 11px;
        background: #ff3048;
        color: #fff;
        font-weight: 850;
        cursor: pointer;
        box-shadow: 0 8px 20px rgba(255, 48, 72, .18);
    }
    .partner-honeypot {
        position: absolute !important;
        width: 1px !important;
        height: 1px !important;
        overflow: hidden !important;
        clip: rect(0 0 0 0) !important;
        white-space: nowrap !important;
    }
    @media (max-width: 1020px) {
        .partner-program-grid {
            grid-template-columns: 1fr;
        }
    }
    @media (max-width: 640px) {
        .partner-program-page {
            padding: 30px 0 56px;
        }
        .partner-program-copy,
        .partner-form-card {
            padding: 22px;
            border-radius: 17px;
        }
        .partner-form-grid {
            grid-template-columns: 1fr;
        }
        .partner-field-full {
            grid-column: auto;
        }
        .partner-submit-row {
            align-items: stretch;
            flex-direction: column;
        }
        .partner-submit-button {
            width: 100%;
        }
    }
</style>
@endpush

@section('content')
<section class="partner-program-page">
    <div class="site-container partner-program-grid">
        <article class="partner-program-copy">
            <span class="partner-kicker">{{ __('partners.form.kicker') }}</span>
            <h1>{{ $translation->title }}</h1>

            <div class="partner-editorial-copy">
                {!! $translation->body_html !!}
            </div>

            <div class="partner-link-rule">
                <div class="partner-link-rule-icon" aria-hidden="true">↔</div>
                <div>
                    <strong>{{ __('partners.form.backlink_commitment') }}</strong>
                    <span>{{ __('partners.form.portal_label') }} — okulary-3d.pl</span>
                </div>
            </div>
        </article>

        <div class="partner-form-card">
            <h2>{{ __('partners.form.title') }}</h2>
            <p class="partner-form-intro">{{ __('partners.form.intro') }}</p>

            @if (session('status'))
                <div class="partner-status" role="status">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('partner_verification_id'))
                <form
                    class="partner-resend"
                    method="post"
                    action="{{ route('partners.resend', ['locale' => app()->getLocale(), 'partner' => session('partner_verification_id')]) }}"
                >
                    @csrf
                    <button type="submit">{{ __('partners.form.resend_verification') }}</button>
                    <small>{{ __('partners.form.resend_help') }}</small>
                </form>
            @endif

            @if ($errors->any())
                <div class="partner-errors" role="alert">
                    <strong>{{ __('partners.form.validation_errors') }}</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form
                method="post"
                action="{{ route('partners.store', ['locale' => app()->getLocale()]) }}"
                enctype="multipart/form-data"
            >
                @csrf

                <div class="partner-honeypot" aria-hidden="true">
                    <label>
                        Website confirmation
                        <input type="text" name="website_confirm" value="" tabindex="-1" autocomplete="off">
                    </label>
                </div>

                <div class="partner-form-grid">
                    <label class="partner-field partner-field-full">
                        <span>{{ __('partners.form.name') }} *</span>
                        <input type="text" name="name" value="{{ old('name') }}" maxlength="60" required>
                        <small class="partner-help">{{ __('partners.form.name_help') }}</small>
                    </label>

                    <label class="partner-field partner-field-full">
                        <span>{{ __('partners.form.website_url') }} *</span>
                        <input
                            type="url"
                            name="website_url"
                            value="{{ old('website_url') }}"
                            maxlength="2048"
                            placeholder="{{ __('partners.form.website_url_placeholder') }}"
                            required
                        >
                    </label>

                    <label class="partner-field partner-field-full">
                        <span>{{ __('partners.form.backlink_url') }}</span>
                        <input type="url" name="backlink_url" value="{{ old('backlink_url') }}" maxlength="2048">
                        <small class="partner-help">{{ __('partners.form.backlink_url_help') }}</small>
                    </label>

                    <label class="partner-field partner-field-full">
                        <span>{{ __('partners.form.description') }} *</span>
                        <textarea name="description" maxlength="300" required>{{ old('description') }}</textarea>
                        <small class="partner-help">{{ __('partners.form.description_help') }}</small>
                    </label>

                    <label class="partner-field">
                        <span>{{ __('partners.form.logo') }} *</span>
                        <input type="file" name="logo" accept="image/jpeg,image/png,image/webp" required>
                        <small class="partner-help">{{ __('partners.form.logo_help') }}</small>
                    </label>

                    <label class="partner-field">
                        <span>{{ __('partners.form.email') }} *</span>
                        <input type="email" name="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" required>
                    </label>

                    <div class="partner-field partner-field-full">
                        <span class="partner-choice-title">{{ __('partners.form.commercial') }} *</span>
                        <div class="partner-radio-row">
                            <label class="partner-radio">
                                <input type="radio" name="commercial" value="1" @checked((string) old('commercial') === '1') required>
                                {{ __('partners.form.commercial_yes') }}
                            </label>
                            <label class="partner-radio">
                                <input type="radio" name="commercial" value="0" @checked((string) old('commercial') === '0') required>
                                {{ __('partners.form.commercial_no') }}
                            </label>
                        </div>
                        <small class="partner-help">{{ __('partners.form.commercial_help') }}</small>
                    </div>

                    <label class="partner-field">
                        <span>{{ __('partners.form.contact_person') }}</span>
                        <input type="text" name="contact_person" value="{{ old('contact_person') }}" maxlength="120" autocomplete="name">
                        <small class="partner-help">{{ __('partners.form.contact_person_help') }}</small>
                    </label>

                    <label class="partner-field">
                        <span>{{ __('partners.form.phone') }}</span>
                        <input type="tel" name="phone" value="{{ old('phone') }}" maxlength="60" autocomplete="tel">
                        <small class="partner-help">{{ __('partners.form.phone_help') }}</small>
                    </label>
                </div>

                <div class="partner-consents">
                    <label class="partner-check">
                        <input type="checkbox" name="backlink_commitment" value="1" @checked(old('backlink_commitment')) required>
                        <span>
                            {{ __('partners.form.backlink_commitment') }}
                            <span class="partner-backlink-box">
                                {{ __('partners.form.portal_label') }}
                                <strong>okulary-3d.pl</strong>
                            </span>
                        </span>
                    </label>

                    <label class="partner-check">
                        <input type="checkbox" name="privacy_consent" value="1" @checked(old('privacy_consent')) required>
                        <span>
                            {{ __('partners.form.privacy_consent') }}
                            <a href="{{ route('static-pages.show', ['locale' => app()->getLocale(), 'key' => 'privacy-policy']) }}" target="_blank" rel="noopener">
                                {{ __('partners.form.privacy_link') }}
                            </a>
                        </span>
                    </label>
                </div>

                <div class="partner-submit-row">
                    <small>{{ __('partners.form.required_note') }}</small>
                    <button class="partner-submit-button" type="submit">
                        {{ __('partners.form.submit') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
