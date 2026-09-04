@extends('admin.layout')

@section('title', __('admin.users.title') . ' — ' . __('admin.title'))
@section('page_heading', __('admin.users.title'))

@section('content')
    <section class="admin-users-head">
        <div><span class="admin-eyebrow">RBAC</span><h1>{{ __('admin.users.title') }}</h1><p>{{ __('admin.users.description') }}</p></div>
        <strong>{{ trans_choice('admin.users.results', $users->total(), ['count' => $users->total()]) }}</strong>
    </section>

    <form class="admin-users-filters" method="get">
        <label><span>{{ __('admin.users.filters.email') }}</span><input name="email" value="{{ $filters['email'] ?? '' }}" type="search"></label>
        <label><span>{{ __('admin.users.filters.name') }}</span><input name="name" value="{{ $filters['name'] ?? '' }}" type="search"></label>
        <label><span>{{ __('admin.users.filters.plan') }}</span><select name="plan"><option value="">{{ __('admin.users.filters.all') }}</option>@foreach (['free', 'pro', 'premium'] as $plan)<option value="{{ $plan }}" @selected(($filters['plan'] ?? '') === $plan)>{{ strtoupper($plan) }}</option>@endforeach</select></label>
        <label><span>{{ __('admin.users.filters.status') }}</span><select name="status"><option value="">{{ __('admin.users.filters.all') }}</option><option value="active" @selected(($filters['status'] ?? '') === 'active')>{{ __('admin.users.status.active') }}</option><option value="suspended" @selected(($filters['status'] ?? '') === 'suspended')>{{ __('admin.users.status.suspended') }}</option></select></label>
        <div class="admin-users-filter-actions"><button type="submit">{{ __('admin.users.filters.search') }}</button><a href="{{ route('admin.users') }}">{{ __('admin.users.filters.clear') }}</a></div>
    </form>

    <div class="admin-users-table-wrap"><table class="admin-users-table"><thead><tr><th>LP</th><th>{{ __('admin.users.columns.created') }}</th><th>{{ __('admin.users.columns.nick') }}</th><th>{{ __('admin.users.columns.email') }}</th><th>{{ __('admin.users.columns.plan') }}</th><th>{{ __('admin.users.columns.language') }}</th><th>{{ __('admin.users.columns.activity') }}</th><th>{{ __('admin.users.columns.status') }}</th><th>{{ __('admin.users.columns.actions') }}</th></tr></thead><tbody>
    @forelse ($users as $user)
        @php($effectivePlan = $user->effective_lenticular_plan)
        <tr class="{{ $user->suspended_at ? 'is-suspended' : '' }}">
            <td>{{ $users->firstItem() + $loop->index }}</td><td>{{ $user->created_at->format('d.m.Y H:i') }}</td><td><strong>{{ $user->name }}</strong></td><td><a href="mailto:{{ $user->email }}">{{ $user->email }}</a></td><td><span class="admin-user-plan is-{{ $effectivePlan }}">{{ strtoupper($effectivePlan) }}</span>@if($effectivePlan !== 'free' && $user->role !== \App\Models\User::ROLE_SUPER_ADMIN && $user->plan_expires_at)<small>{{ __('admin.users.until', ['date' => $user->plan_expires_at->format('d.m.Y')]) }}</small>@endif</td><td>{{ strtoupper($user->preferred_locale ?: config('locales.default', 'pl')) }}</td><td>{{ $user->last_activity_at?->format('d.m.Y H:i') ?? '—' }}</td><td><span class="admin-user-state {{ $user->suspended_at ? 'is-suspended' : 'is-active' }}">{{ __('admin.users.status.'.($user->suspended_at ? 'suspended' : 'active')) }}</span></td>
            <td><div class="admin-users-actions">
                <a class="admin-user-action-icon" href="{{ route('admin.users.edit', $user) }}" title="{{ __('admin.users.actions.edit') }}" aria-label="{{ __('admin.users.actions.edit') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m4 16-.8 4.8L8 20l10.5-10.5-4-4L4 16Z"/><path d="m13.5 6.5 4 4"/></svg></a>
                <a class="admin-user-action-icon is-projects" href="{{ route('admin.users.projects', $user) }}" title="{{ __('admin.users.actions.projects') }}" aria-label="{{ __('admin.users.actions.projects') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6.5h7l2 2h9v10.5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6.5Z"/><path d="M3 9h18"/></svg></a>
                @if($user->suspended_at)<form method="post" action="{{ route('admin.users.restore', $user) }}">@csrf @method('PATCH')<button class="admin-user-action-icon is-restore" title="{{ __('admin.users.actions.restore') }}" aria-label="{{ __('admin.users.actions.restore') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12a8 8 0 1 0 2.3-5.7L4 8.6"/><path d="M4 4v4.6h4.6"/></svg></button></form>@elseif(!auth()->user()->is($user) && $user->role !== \App\Models\User::ROLE_SUPER_ADMIN)<form method="post" action="{{ route('admin.users.suspend', $user) }}">@csrf @method('PATCH')<button class="admin-user-action-icon is-danger" title="{{ __('admin.users.actions.suspend') }}" aria-label="{{ __('admin.users.actions.suspend') }}" onclick="return confirm('{{ __('admin.users.actions.suspend_confirm') }}')"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8.5 8.5 7 7"/></svg></button></form>@endif
            </div></td>
        </tr>
    @empty<tr><td colspan="9" class="admin-users-empty">{{ __('admin.users.empty') }}</td></tr>@endforelse
    </tbody></table></div>
    <div class="admin-users-pagination">{{ $users->links() }}</div>
@endsection
