@extends('admin.layout')

@section('title', __('partners.admin.edit.title') . ' — ' . __('admin.title'))
@section('page_heading', __('partners.admin.edit.title'))

@section('content')
<style>
    .partner-edit-shell{display:grid;grid-template-columns:minmax(0,1fr) 340px;gap:22px}.partner-edit-card{padding:22px;border:1px solid #e1e7ef;border-radius:15px;background:#fff}.partner-edit-card h1,.partner-edit-card h2{margin-top:0}.partner-edit-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:15px}.partner-edit-field{display:grid;gap:6px}.partner-edit-field.full{grid-column:1/-1}.partner-edit-field span{font-size:.72rem;font-weight:800;color:#44526a}.partner-edit-field input,.partner-edit-field textarea,.partner-edit-field select{width:100%;padding:10px 11px;border:1px solid #d8e0ea;border-radius:9px;font:inherit}.partner-edit-field textarea{min-height:96px;resize:vertical}.partner-edit-logo{max-width:120px;max-height:90px;object-fit:contain;border:1px solid #e2e8ef;border-radius:8px;padding:6px}.partner-edit-actions{display:flex;flex-wrap:wrap;gap:8px;margin-top:20px}.partner-btn{min-height:40px;padding:0 13px;border:1px solid #d2dbe7;border-radius:8px;background:#fff;color:#263650;font-weight:800;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center}.partner-btn-primary{background:#13213b;color:#fff;border-color:#13213b}.partner-btn-check{background:#edf8fc;border-color:#b8deeb;color:#075f7e}.partner-btn-danger{background:#a32938;color:#fff;border-color:#a32938}.partner-side-meta{display:grid;gap:10px;margin-bottom:20px;font-size:.78rem;color:#59677e}.partner-side-meta strong{color:#17233d}.partner-moderation{display:grid;gap:9px}.partner-moderation form{display:grid;gap:8px}.partner-moderation textarea{min-height:78px;padding:9px;border:1px solid #d9e0e9;border-radius:8px}.partner-note{padding:11px;border-radius:9px;background:#f5f8fb;color:#6a778b;font-size:.72rem;line-height:1.5}.partner-note.is-problem{background:#fff1f2;color:#922b3a}@media(max-width:900px){.partner-edit-shell{grid-template-columns:1fr}.partner-edit-grid{grid-template-columns:1fr}.partner-edit-field.full{grid-column:auto}}
</style>

<div class="partner-edit-shell">
    <section class="partner-edit-card">
        <h1>{{ __('partners.admin.edit.title') }} — {{ $partner->name }}</h1>

        <form method="post" action="{{ route('admin.partners.update', $partner) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="partner-edit-grid">
                <label class="partner-edit-field full"><span>{{ __('partners.form.name') }}</span><input type="text" name="name" maxlength="60" required value="{{ old('name', $partner->name) }}"></label>
                <label class="partner-edit-field full"><span>{{ __('partners.form.website_url') }}</span><input type="url" name="website_url" maxlength="2048" required value="{{ old('website_url', $partner->website_url) }}"></label>
                <label class="partner-edit-field full"><span>{{ __('partners.form.backlink_url') }}</span><input type="url" name="backlink_url" maxlength="2048" value="{{ old('backlink_url', $partner->backlink_url) }}"></label>
                <label class="partner-edit-field full"><span>{{ __('partners.form.description') }}</span><textarea name="description" maxlength="300" required>{{ old('description', $partner->description) }}</textarea></label>
                <label class="partner-edit-field"><span>{{ __('partners.form.email') }}</span><input type="email" name="email" required value="{{ old('email', $partner->email) }}"></label>
                <label class="partner-edit-field"><span>{{ __('partners.form.commercial') }}</span><select name="commercial" required><option value="1" @selected((string) old('commercial', (int) $partner->commercial) === '1')>{{ __('partners.admin.commercial_yes') }}</option><option value="0" @selected((string) old('commercial', (int) $partner->commercial) === '0')>{{ __('partners.admin.commercial_no') }}</option></select></label>
                <label class="partner-edit-field"><span>{{ __('partners.form.contact_person') }}</span><input type="text" name="contact_person" maxlength="120" value="{{ old('contact_person', $partner->contact_person) }}"></label>
                <label class="partner-edit-field"><span>{{ __('partners.form.phone') }}</span><input type="text" name="phone" maxlength="60" value="{{ old('phone', $partner->phone) }}"></label>
                <label class="partner-edit-field full"><span>{{ __('partners.admin.edit.logo_optional') }}</span><input type="file" name="logo" accept="image/jpeg,image/png,image/webp"></label>
            </div>

            <div class="partner-edit-actions">
                <button class="partner-btn partner-btn-primary" type="submit">{{ __('partners.admin.actions.save') }}</button>
                <a class="partner-btn" href="{{ route('admin.partners.index') }}">{{ __('partners.admin.actions.back') }}</a>
            </div>
        </form>
    </section>

    <aside class="partner-edit-card">
        <h2>{{ __('partners.admin.edit.moderation') }}</h2>
        <div class="partner-side-meta">
            @if ($partner->logoUrl())
                <div><img class="partner-edit-logo" src="{{ $partner->logoUrl() }}" alt="{{ $partner->name }}"></div>
            @endif
            <div><strong>{{ __('partners.admin.columns.status') }}:</strong> {{ __('partners.admin.status.' . $partner->status->value) }}</div>
            <div><strong>{{ __('partners.admin.columns.verified') }}:</strong> {{ $partner->email_verified_at?->format('Y-m-d H:i') ?? __('partners.admin.not_verified') }}</div>
            <div><strong>{{ __('partners.admin.columns.clicks') }}:</strong> {{ number_format($partner->click_count) }}</div>
            <div><strong>{{ __('partners.admin.backlink.last_check') }}:</strong> {{ $partner->last_checked_at?->format('Y-m-d H:i') ?? __('partners.admin.backlink.not_checked') }}</div>
            <div><strong>HTTP:</strong> {{ $partner->last_http_status ?? '—' }}</div>
            <div><strong>{{ __('partners.admin.backlink.last_found') }}:</strong> {{ $partner->last_backlink_found_at?->format('Y-m-d H:i') ?? '—' }}</div>
            <div><strong>{{ __('partners.admin.backlink.failures') }}:</strong> {{ $partner->consecutive_failures }}</div>
        </div>

        @if ($partner->last_check_error)
            <div class="partner-note is-problem">{{ __('partners.admin.backlink.error') }}: {{ $partner->last_check_error }}</div>
        @endif

        <div class="partner-moderation">
            @if (! in_array($partner->status, [\App\Enums\PartnerLinkStatus::Banned, \App\Enums\PartnerLinkStatus::Rejected], true))
                <form method="post" action="{{ route('admin.partners.check-backlink', $partner) }}">
                    @csrf
                    <button class="partner-btn partner-btn-check" type="submit">{{ __('partners.admin.actions.check_now') }}</button>
                </form>
            @endif

            @if ($partner->email_verified_at && $partner->status !== \App\Enums\PartnerLinkStatus::Active)
                <form method="post" action="{{ route('admin.partners.approve', $partner) }}">@csrf @method('PATCH')<button class="partner-btn partner-btn-primary" type="submit">{{ __('partners.admin.actions.approve') }}</button></form>
            @endif

            @if ($partner->status === \App\Enums\PartnerLinkStatus::Active)
                <form method="post" action="{{ route('admin.partners.revoke', $partner) }}">@csrf @method('PATCH')<button class="partner-btn" type="submit">{{ __('partners.admin.actions.revoke') }}</button></form>
            @endif

            @if (! in_array($partner->status, [\App\Enums\PartnerLinkStatus::Rejected, \App\Enums\PartnerLinkStatus::Banned], true))
                <form method="post" action="{{ route('admin.partners.reject', $partner) }}">@csrf @method('PATCH')<button class="partner-btn" type="submit">{{ __('partners.admin.actions.reject') }}</button></form>
            @endif

            @if ($partner->status !== \App\Enums\PartnerLinkStatus::Banned)
                <form method="post" action="{{ route('admin.partners.ban', $partner) }}">
                    @csrf
                    @method('PATCH')
                    <label class="partner-edit-field"><span>{{ __('partners.admin.edit.ban_reason') }}</span><textarea name="banned_reason" maxlength="1000" required></textarea></label>
                    <small>{{ __('partners.admin.edit.ban_reason_help') }}</small>
                    <button class="partner-btn partner-btn-danger" type="submit">{{ __('partners.admin.actions.ban') }}</button>
                </form>
            @elseif ($partner->banned_reason)
                <div class="partner-note">{{ $partner->banned_reason }}</div>
            @endif

            <div class="partner-note">{{ __('partners.admin.edit.backlink_check_info') }}</div>

            <form method="post" action="{{ route('admin.partners.destroy', $partner) }}" onsubmit="return confirm('{{ __('partners.admin.actions.delete') }}?');">
                @csrf
                @method('DELETE')
                <button class="partner-btn partner-btn-danger" type="submit">{{ __('partners.admin.actions.delete') }}</button>
            </form>
        </div>
    </aside>
</div>
@endsection
