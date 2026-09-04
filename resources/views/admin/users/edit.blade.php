@extends('admin.layout')

@section('title', __('admin.users.edit.title') . ' — ' . __('admin.title'))
@section('page_heading', __('admin.users.edit.title'))

@section('content')
    <a class="admin-users-back" href="{{ route('admin.users') }}">← {{ __('admin.users.edit.back') }}</a>
    <section class="admin-user-edit-card">
        <header><div><span class="admin-eyebrow">{{ __('admin.users.edit.kicker') }}</span><h1>{{ $user->name }}</h1><p>{{ $user->email }}</p></div><span class="admin-user-state {{ $user->suspended_at ? 'is-suspended' : 'is-active' }}">{{ __('admin.users.status.'.($user->suspended_at ? 'suspended' : 'active')) }}</span></header>
        <form method="post" action="{{ route('admin.users.update', $user) }}">@csrf @method('PATCH')
            <label><span>{{ __('admin.users.edit.plan') }}</span><select name="lenticular_plan" required>@foreach(['free','pro','premium'] as $plan)<option value="{{ $plan }}" @selected(old('lenticular_plan', $user->lenticular_plan) === $plan)>{{ strtoupper($plan) }}</option>@endforeach</select></label>
            <label><span>{{ __('admin.users.edit.expires') }}</span><input type="date" name="plan_expires_at" value="{{ old('plan_expires_at', $user->plan_expires_at?->format('Y-m-d')) }}"><small>{{ __('admin.users.edit.expires_help') }}</small></label>
            <button class="admin-user-save" type="submit">{{ __('admin.users.edit.save') }}</button>
        </form>
    </section>
    <section class="admin-user-edit-card admin-token-card">
        <header><div><span class="admin-eyebrow">TOKEN_LENS</span><h2>{{ __('admin.users.wallet.title') }}</h2><p>{{ __('admin.users.wallet.description') }}</p></div><strong class="admin-token-balance">{{ $tokenLensBalance }} TL</strong></header>
        <form method="post" action="{{ route('admin.users.tokens.adjust', $user) }}">@csrf
            <label><span>{{ __('admin.users.wallet.amount') }}</span><input type="number" name="amount" min="-10000" max="10000" step="1" required value="{{ old('amount') }}"><small>{{ __('admin.users.wallet.amount_help') }}</small></label>
            <label><span>{{ __('admin.users.wallet.reason') }}</span><input type="text" name="reason" maxlength="255" required value="{{ old('reason') }}"></label>
            @error('token_lens')<p class="admin-token-error">{{ $message }}</p>@enderror
            <button class="admin-user-save" type="submit">{{ __('admin.users.wallet.adjust') }}</button>
        </form>
        <div class="admin-token-history"><h3>{{ __('admin.users.wallet.history') }}</h3>
            @forelse($tokenLensTransactions as $transaction)
                <div><span><strong class="{{ $transaction->amount > 0 ? 'is-credit' : 'is-debit' }}">{{ $transaction->amount > 0 ? '+' : '' }}{{ $transaction->amount }} TL</strong>{{ $transaction->description ?: __('admin.users.wallet.types.'.$transaction->type) }}</span><time>{{ $transaction->created_at->format('d.m.Y H:i') }}</time></div>
            @empty<p>{{ __('admin.users.wallet.empty') }}</p>@endforelse
        </div>
    </section>
@endsection
