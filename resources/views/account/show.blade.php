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
                <a class="admin-panel-button" href="{{ route('plans.index', ['locale' => app()->getLocale()]) }}">{{ __('plans.title') }}</a>
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
            <section class="account-card account-token-card">
                <div class="account-card-heading">
                    <div class="account-token-title-row">
                        <h2>{{ __('portal_auth.wallet.title') }}</h2>
                        <button class="account-token-help" type="button" aria-label="{{ __('portal_auth.wallet.help_label') }}">
                            ?
                            <span class="account-token-tooltip" role="tooltip">{{ __('portal_auth.wallet.help') }}</span>
                        </button>
                    </div>
                    <span>{{ __('portal_auth.wallet.description') }}</span>
                </div>
                <strong class="account-token-balance">{{ $tokenLensBalance }} <small>TOKEN_LENS</small></strong>
                @if ($tokenLensBalance === 0)
                    <div class="account-token-empty-balance">
                        <p>{{ __($lenticularPlan === \App\Services\LenticularAccessService::PREMIUM ? 'portal_auth.wallet.zero_balance_premium' : 'portal_auth.wallet.zero_balance') }}</p>
                        <div class="account-token-actions">
                            @if ($lenticularPlan !== \App\Services\LenticularAccessService::PREMIUM)
                                <a href="{{ route('plans.index', ['locale' => app()->getLocale()]) }}">{{ __('portal_auth.wallet.change_plan') }}</a>
                            @endif
                            <a href="{{ route('account', ['locale' => app()->getLocale(), 'purchase' => 'tokens']) }}">{{ __('portal_auth.wallet.buy_tokens') }}</a>
                        </div>
                    </div>
                @endif
                <div class="account-token-history">
                    @forelse($tokenLensTransactions as $transaction)
                        <div><span>{{ $transaction->description ?: __('portal_auth.wallet.types.'.$transaction->type) }}</span><strong class="{{ $transaction->amount > 0 ? 'is-credit' : 'is-debit' }}">{{ $transaction->amount > 0 ? '+' : '' }}{{ $transaction->amount }} TL</strong></div>
                    @empty<p>{{ __('portal_auth.wallet.empty') }}</p>@endforelse
                </div>
            </section>

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

            <section class="account-card account-projects-card">
                <div class="account-card-heading">
                    <h2>{{ __('portal_auth.projects.title') }}</h2>
                    <span>{{ __('portal_auth.projects.description') }}</span>
                </div>

                <div class="account-projects-table-wrap">
                    <table class="account-projects-table">
                        <thead><tr><th>{{ __('portal_auth.projects.number') }}</th><th>{{ __('portal_auth.projects.created_at') }}</th><th>{{ __('portal_auth.projects.name') }}</th><th>{{ __('portal_auth.projects.preview') }}</th><th>{{ __('portal_auth.projects.actions') }}</th></tr></thead>
                        <tbody>
                        @forelse ($projects as $project)
                            @php
                                $preview = $project->files->sortByDesc('created_at')->first(fn ($file) => str_starts_with($file->kind, 'final_preview_'))
                                    ?? $project->files->sortByDesc('created_at')->first(fn ($file) => str_starts_with($file->kind, 'alignment_preview_'))
                                    ?? $project->files->sortByDesc('created_at')->first(fn ($file) => str_starts_with($file->kind, 'timeline_thumbnail_') || str_starts_with($file->kind, 'analysis_thumbnail_'));
                                $finalArtifact = $project->jobs->flatMap->artifacts->firstWhere('kind', 'final');
                                $hasFinal = $finalArtifact && Storage::disk($finalArtifact->disk)->exists($finalArtifact->path);
                            @endphp
                            <tr>
                                <td>{{ $projects->firstItem() + $loop->index }}</td>
                                <td><time datetime="{{ $project->created_at->toIso8601String() }}">{{ $project->created_at->format('d.m.Y H:i') }}</time></td>
                                <td><strong>{{ $project->name }}</strong></td>
                                <td>
                                    @if ($preview)
                                        <img class="account-project-preview" src="{{ Storage::disk($preview->disk)->temporaryUrl($preview->path, now()->addMinutes(15)) }}" alt="{{ __('portal_auth.projects.preview_alt', ['name' => $project->name]) }}">
                                    @else
                                        <span class="account-project-no-preview">{{ __('portal_auth.projects.no_preview') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="account-project-actions">
                                        @if ($hasFinal)
                                            <a href="{{ route('lab.projects.download', ['locale' => app()->getLocale(), 'project' => $project]) }}" title="{{ __('portal_auth.projects.download') }}" aria-label="{{ __('portal_auth.projects.download') }}">
                                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m0 0 5-5m-5 5-5-5M5 19h14"/></svg>
                                            </a>
                                        @endif
                                        <a href="{{ route('lab.projects.show', ['locale' => app()->getLocale(), 'project' => $project]) }}" title="{{ __('portal_auth.projects.edit') }}" aria-label="{{ __('portal_auth.projects.edit') }}">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m4 16-.8 4.8L8 20l10.5-10.5-4-4L4 16Z"/><path d="m13.5 6.5 4 4"/></svg>
                                        </a>
                                        <a class="is-order" href="{{ route('marketplace.index', ['locale' => app()->getLocale(), 'project' => $project]) }}" title="{{ __('portal_auth.projects.order') }}" aria-label="{{ __('portal_auth.projects.order') }}">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h2l2 10h10l2-7H6"/><circle cx="9" cy="20" r="1"/><circle cx="17" cy="20" r="1"/></svg>
                                        </a>
                                        <form method="post" action="{{ route('lab.projects.destroy', ['locale' => app()->getLocale(), 'project' => $project]) }}" onsubmit="return confirm(@js(__('portal_auth.projects.delete_confirm', ['name' => $project->name])))">
                                            @csrf
                                            @method('DELETE')
                                            <button class="is-danger" type="submit" title="{{ __('portal_auth.projects.delete') }}" aria-label="{{ __('portal_auth.projects.delete') }}">
                                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3m3 0-1 14H7L6 7m4 4v6m4-6v6"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td class="account-projects-empty" colspan="5">{{ __('portal_auth.projects.empty') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($projects->hasPages())
                    <div class="account-projects-pagination">{{ $projects->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</section>
@endsection
