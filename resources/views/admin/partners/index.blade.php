@extends('admin.layout')

@section('title', __('partners.admin.title') . ' — ' . __('admin.title'))
@section('page_heading', __('partners.admin.title'))

@section('content')
<style>
    .partner-admin-head{display:flex;justify-content:space-between;gap:24px;align-items:flex-end;margin-bottom:22px}.partner-admin-head h1{margin:4px 0 6px}.partner-admin-head p{margin:0;color:#657189}.partner-admin-kicker{font-size:.72rem;font-weight:850;letter-spacing:.1em;text-transform:uppercase;color:#0787b7}.partner-admin-filters{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:18px;padding:14px;border:1px solid #e2e8f0;border-radius:14px;background:#fff}.partner-admin-filters input,.partner-admin-filters select{min-height:42px;padding:0 11px;border:1px solid #d7dee8;border-radius:9px;background:#fff}.partner-admin-filters input{min-width:280px;flex:1}.partner-admin-filters button,.partner-action{min-height:38px;padding:0 12px;border:1px solid #d4dce7;border-radius:8px;background:#fff;color:#27354d;font-weight:750;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center}.partner-action-primary{background:#13213b;color:#fff;border-color:#13213b}.partner-action-check{background:#edf8fc;border-color:#b8deeb;color:#075f7e}.partner-admin-table-wrap{overflow:auto;border:1px solid #e1e7ef;border-radius:14px;background:#fff}.partner-admin-table{width:100%;min-width:1420px;border-collapse:collapse;font-size:.76rem}.partner-admin-table th,.partner-admin-table td{padding:12px 11px;border-bottom:1px solid #edf1f5;text-align:left;vertical-align:top}.partner-admin-table th{background:#f7f9fc;color:#536078;font-size:.68rem;text-transform:uppercase;letter-spacing:.045em}.partner-admin-row.is-suspended td{background:#fff5f5}.partner-admin-row.is-unreachable .partner-admin-link,.partner-admin-row.is-unreachable .partner-admin-domain{text-decoration:line-through}.partner-admin-logo{width:64px;height:46px;object-fit:contain;border:1px solid #e5eaf0;border-radius:8px;background:#fff}.partner-admin-link{font-weight:800;color:#17243e;text-decoration:none}.partner-admin-domain{display:block;margin-top:4px;color:#7b8799}.partner-admin-contact span{display:block;margin-top:3px}.partner-admin-actions{display:flex;flex-wrap:wrap;gap:6px;min-width:230px}.partner-admin-actions form{display:inline}.partner-status-pill{display:inline-flex;padding:5px 8px;border-radius:999px;background:#eef3f8;color:#33435c;font-weight:800;white-space:nowrap}.partner-status-pill.is-active{background:#eaf8ef;color:#22633a}.partner-status-pill.is-banned,.partner-status-pill.is-rejected,.partner-status-pill.is-suspended_backlink,.partner-status-pill.is-suspended_unreachable{background:#fff0f1;color:#9a2634}.partner-status-pill.is-email_pending{background:#fff7e5;color:#815d13}.partner-backlink-health{display:grid;gap:4px;min-width:170px}.partner-backlink-health a{font-weight:750;color:#1f4d75;text-decoration:none}.partner-health-ok{color:#25733c;font-weight:800}.partner-health-bad{color:#a12838;font-weight:800}.partner-health-muted{color:#7a8799}.partner-empty{padding:30px;text-align:center;color:#7c8799}.partner-pager{margin-top:16px}
</style>

<section>
    <div class="partner-admin-head">
        <div>
            <span class="partner-admin-kicker">{{ __('partners.admin.kicker') }}</span>
            <h1>{{ __('partners.admin.title') }}</h1>
            <p>{{ __('partners.admin.description') }}</p>
        </div>
    </div>

    <form class="partner-admin-filters" method="get" action="{{ route('admin.partners.index') }}">
        <input type="search" name="q" value="{{ $query }}" placeholder="{{ __('partners.admin.filters.search') }}">
        <select name="status">
            <option value="">{{ __('partners.admin.filters.all') }}</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected($selectedStatus === $status->value)>
                    {{ __('partners.admin.status.' . $status->value) }}
                </option>
            @endforeach
        </select>
        <button type="submit">{{ __('partners.admin.filters.apply') }}</button>
    </form>

    <div class="partner-admin-table-wrap">
        <table class="partner-admin-table">
            <thead>
                <tr>
                    <th>{{ __('partners.admin.columns.lp') }}</th>
                    <th>{{ __('partners.admin.columns.date') }}</th>
                    <th>{{ __('partners.admin.columns.logo') }}</th>
                    <th>{{ __('partners.admin.columns.partner') }}</th>
                    <th>{{ __('partners.admin.columns.description') }}</th>
                    <th>{{ __('partners.admin.columns.contact') }}</th>
                    <th>{{ __('partners.admin.columns.verified') }}</th>
                    <th>{{ __('partners.admin.columns.backlink') }}</th>
                    <th>{{ __('partners.admin.columns.status') }}</th>
                    <th>{{ __('partners.admin.columns.clicks') }}</th>
                    <th>{{ __('partners.admin.columns.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($partners as $partner)
                    @php
                        $isSuspended = in_array($partner->status, [
                            \App\Enums\PartnerLinkStatus::SuspendedBacklink,
                            \App\Enums\PartnerLinkStatus::SuspendedUnreachable,
                        ], true);
                        $isUnreachable = $partner->status === \App\Enums\PartnerLinkStatus::SuspendedUnreachable;
                    @endphp
                    <tr class="partner-admin-row {{ $isSuspended ? 'is-suspended' : '' }} {{ $isUnreachable ? 'is-unreachable' : '' }}">
                        <td>{{ ($partners->firstItem() ?? 1) + $loop->index }}</td>
                        <td>{{ $partner->created_at?->format('Y-m-d H:i') }}</td>
                        <td>
                            @if ($partner->logoUrl())
                                <img class="partner-admin-logo" src="{{ $partner->logoUrl() }}" alt="{{ $partner->name }}">
                            @endif
                        </td>
                        <td>
                            <a class="partner-admin-link" href="{{ $partner->website_url }}" target="_blank" rel="noopener noreferrer">{{ $partner->name }}</a>
                            <span class="partner-admin-domain">{{ $partner->domain }}</span>
                        </td>
                        <td>{{ \Illuminate\Support\Str::limit($partner->description, 150) }}</td>
                        <td class="partner-admin-contact">
                            @if ($partner->contact_person)<strong>{{ $partner->contact_person }}</strong>@endif
                            <span>{{ $partner->email }}</span>
                            @if ($partner->phone)<span>{{ $partner->phone }}</span>@endif
                        </td>
                        <td>
                            {{ $partner->email_verified_at ? $partner->email_verified_at->format('Y-m-d H:i') : __('partners.admin.not_verified') }}
                        </td>
                        <td>
                            <div class="partner-backlink-health">
                                <a href="{{ $partner->backlink_url ?: $partner->website_url }}" target="_blank" rel="noopener noreferrer">{{ __('partners.admin.backlink.open') }}</a>
                                @if ($partner->last_checked_at)
                                    <span class="{{ $partner->last_check_error ? 'partner-health-bad' : 'partner-health-ok' }}">
                                        {{ $partner->last_check_error ? __('partners.admin.backlink.problem') : __('partners.admin.backlink.ok') }}
                                    </span>
                                    <span class="partner-health-muted">{{ $partner->last_checked_at->format('Y-m-d H:i') }} · HTTP {{ $partner->last_http_status ?? '—' }}</span>
                                    @if ($partner->last_check_error)
                                        <span class="partner-health-muted">{{ __('partners.admin.backlink.error') }}: {{ $partner->last_check_error }}</span>
                                    @endif
                                @else
                                    <span class="partner-health-muted">{{ __('partners.admin.backlink.not_checked') }}</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <span class="partner-status-pill is-{{ $partner->status->value }}">
                                {{ __('partners.admin.status.' . $partner->status->value) }}
                            </span>
                        </td>
                        <td>{{ number_format($partner->click_count) }}</td>
                        <td>
                            <div class="partner-admin-actions">
                                @if ($partner->email_verified_at && $partner->status !== \App\Enums\PartnerLinkStatus::Active)
                                    <form method="post" action="{{ route('admin.partners.approve', $partner) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="partner-action partner-action-primary" type="submit">{{ __('partners.admin.actions.approve') }}</button>
                                    </form>
                                @endif
                                @if (! in_array($partner->status, [\App\Enums\PartnerLinkStatus::Banned, \App\Enums\PartnerLinkStatus::Rejected], true))
                                    <form method="post" action="{{ route('admin.partners.check-backlink', $partner) }}">
                                        @csrf
                                        <button class="partner-action partner-action-check" type="submit">{{ __('partners.admin.actions.check_now') }}</button>
                                    </form>
                                @endif
                                <a class="partner-action" href="{{ route('admin.partners.edit', $partner) }}">{{ __('partners.admin.actions.edit') }}</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td class="partner-empty" colspan="11">{{ __('partners.admin.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="partner-pager">{{ $partners->links() }}</div>
</section>
@endsection
