@extends('admin.layout')

@section('title', ($campaign->exists ? __('newsletter.admin.edit_campaign') : __('newsletter.admin.new_campaign')) . ' — ' . __('admin.title'))
@section('page_heading', __('newsletter.admin.campaign'))

@section('content')
@php
    $locked = $campaign->exists && in_array(
        $campaign->status,
        [
            \App\Enums\NewsletterCampaignStatus::Sending,
            \App\Enums\NewsletterCampaignStatus::Sent,
        ],
        true
    );
@endphp

<section class="admin-newsletter-page">
    <div class="cms-page-heading">
        <div>
            <span class="admin-eyebrow">{{ __('newsletter.admin.campaigns_kicker') }}</span>
            <h1>{{ $campaign->exists ? __('newsletter.admin.edit_campaign') : __('newsletter.admin.new_campaign') }}</h1>
            <p>{{ __('newsletter.admin.campaign_help') }}</p>
        </div>

        <a class="cms-secondary-button" href="{{ route('admin.newsletter.index') }}">
            ← {{ __('newsletter.admin.back') }}
        </a>
    </div>

    @if ($errors->any())
        <div class="newsletter-admin-error">
            {{ $errors->first() }}
        </div>
    @endif

    @if ($campaign->exists)
        <div class="newsletter-campaign-summary">
            <span class="newsletter-status status-{{ $campaign->status->value }}">
                {{ __('newsletter.campaign_statuses.' . $campaign->status->value) }}
            </span>
            <span>{{ __('newsletter.admin.recipients') }}: <strong>{{ $campaign->recipient_count }}</strong></span>
            <span>{{ __('newsletter.admin.sent') }}: <strong>{{ $campaign->sent_count }}</strong></span>
            <span>{{ __('newsletter.admin.failed') }}: <strong>{{ $campaign->failed_count }}</strong></span>
        </div>
    @endif

    <form
        class="newsletter-campaign-editor"
        method="post"
        action="{{ $campaign->exists
            ? route('admin.newsletter.campaigns.update', $campaign)
            : route('admin.newsletter.campaigns.store') }}"
    >
        @csrf
        @if ($campaign->exists)
            @method('PUT')
        @endif

        <div class="newsletter-campaign-grid">
            <div>
                <section class="cms-panel">
                    <div class="cms-field">
                        <label for="newsletter-subject">{{ __('newsletter.admin.subject') }}</label>
                        <input
                            id="newsletter-subject"
                            type="text"
                            name="subject"
                            maxlength="255"
                            value="{{ old('subject', $campaign->subject) }}"
                            required
                            @disabled($locked)
                        >
                    </div>

                    <div class="cms-field">
                        <label for="newsletter-preheader">{{ __('newsletter.admin.preheader') }}</label>
                        <input
                            id="newsletter-preheader"
                            type="text"
                            name="preheader"
                            maxlength="500"
                            value="{{ old('preheader', $campaign->preheader) }}"
                            @disabled($locked)
                        >
                        <small>{{ __('newsletter.admin.preheader_help') }}</small>
                    </div>

                    <div class="cms-field">
                        <label>{{ __('newsletter.admin.content') }}</label>
                        <div class="wysiwyg" data-wysiwyg>
                            <div class="wysiwyg-toolbar" role="toolbar">
                                <button type="button" data-command="formatBlock" data-value="p">P</button>
                                <button type="button" data-command="formatBlock" data-value="h2">H2</button>
                                <button type="button" data-command="formatBlock" data-value="h3">H3</button>
                                <button type="button" data-command="bold"><strong>B</strong></button>
                                <button type="button" data-command="italic"><em>I</em></button>
                                <button type="button" data-command="insertUnorderedList">•</button>
                                <button type="button" data-command="insertOrderedList">1.</button>
                                <button type="button" data-link>🔗</button>
                            </div>
                            <div class="wysiwyg-editor" contenteditable="{{ $locked ? 'false' : 'true' }}" data-editor>{!! old('body_html', $campaign->body_html) !!}</div>
                            <textarea name="body_html" data-editor-output hidden>{{ old('body_html', $campaign->body_html) }}</textarea>
                        </div>
                    </div>
                </section>
            </div>

            <aside>
                <section class="cms-panel">
                    <div class="cms-field">
                        <label for="newsletter-locale">{{ __('newsletter.admin.language') }}</label>
                        <select id="newsletter-locale" name="locale" required @disabled($locked)>
                            @foreach ($supportedLocales as $code => $language)
                                <option value="{{ $code }}" @selected(old('locale', $campaign->locale) === $code)>
                                    {{ strtoupper($code) }} — {{ $language['native'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="newsletter-delivery-note">
                        <strong>{{ __('newsletter.admin.double_optin_title') }}</strong>
                        <p>{{ __('newsletter.admin.double_optin_text') }}</p>
                    </div>

                    @if (!$locked)
                        <button class="cms-primary-button newsletter-save-button" type="submit">
                            {{ __('newsletter.admin.save') }}
                        </button>
                    @endif
                </section>
            </aside>
        </div>
    </form>

    @if ($campaign->exists)
        <div class="newsletter-actions-grid">
            <section class="cms-panel">
                <h2>{{ __('newsletter.admin.test_heading') }}</h2>
                <p>{{ __('newsletter.admin.test_help') }}</p>
                <form method="post" action="{{ route('admin.newsletter.campaigns.send-test', $campaign) }}" class="newsletter-inline-form">
                    @csrf
                    <input type="email" name="test_email" placeholder="test@example.com" required>
                    <button class="cms-secondary-button" type="submit">{{ __('newsletter.admin.send_test') }}</button>
                </form>
            </section>

            @if (!$locked)
                <section class="cms-panel">
                    <h2>{{ __('newsletter.admin.schedule_heading') }}</h2>
                    <p>{{ __('newsletter.admin.schedule_help') }}</p>
                    <form method="post" action="{{ route('admin.newsletter.campaigns.schedule', $campaign) }}" class="newsletter-inline-form">
                        @csrf
                        <input
                            type="datetime-local"
                            name="scheduled_at"
                            value="{{ old('scheduled_at', $campaign->scheduled_at?->format('Y-m-d\TH:i')) }}"
                            required
                        >
                        <button class="cms-secondary-button" type="submit">{{ __('newsletter.admin.schedule_campaign') }}</button>
                    </form>
                </section>

                <section class="cms-panel newsletter-send-now-panel">
                    <h2>{{ __('newsletter.admin.send_now_heading') }}</h2>
                    <p>{{ __('newsletter.admin.send_now_help') }}</p>
                    <form method="post" action="{{ route('admin.newsletter.campaigns.send-now', $campaign) }}" onsubmit="return confirm('{{ __('newsletter.admin.send_confirm') }}')">
                        @csrf
                        <button class="cms-primary-button" type="submit">{{ __('newsletter.admin.send_now') }}</button>
                    </form>
                </section>

                @if ($campaign->status === \App\Enums\NewsletterCampaignStatus::Draft)
                    <section class="cms-panel newsletter-delete-panel">
                        <h2>{{ __('newsletter.admin.delete_heading') }}</h2>
                        <form method="post" action="{{ route('admin.newsletter.campaigns.destroy', $campaign) }}" onsubmit="return confirm('{{ __('newsletter.admin.delete_confirm') }}')">
                            @csrf
                            @method('DELETE')
                            <button class="newsletter-danger-button" type="submit">{{ __('newsletter.admin.delete_campaign') }}</button>
                        </form>
                    </section>
                @endif
            @endif
        </div>
    @endif
</section>
@endsection
