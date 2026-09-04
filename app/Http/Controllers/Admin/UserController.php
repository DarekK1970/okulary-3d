<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LenticularAccessService;
use App\Services\TokenLensWalletService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'email' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'plan' => ['nullable', Rule::in($this->plans())],
            'status' => ['nullable', Rule::in(['active', 'suspended'])],
        ]);

        $users = User::query()
            ->when($filters['email'] ?? null, fn (Builder $query, string $email) => $query->where('email', 'like', '%'.$email.'%'))
            ->when($filters['name'] ?? null, fn (Builder $query, string $name) => $query->where('name', 'like', '%'.$name.'%'))
            ->when($filters['plan'] ?? null, fn (Builder $query, string $plan) => $query->where('lenticular_plan', $plan))
            ->when(($filters['status'] ?? null) === 'active', fn (Builder $query) => $query->whereNull('suspended_at'))
            ->when(($filters['status'] ?? null) === 'suspended', fn (Builder $query) => $query->whereNotNull('suspended_at'))
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'filters'));
    }

    public function edit(User $user, TokenLensWalletService $wallet): View
    {
        return view('admin.users.edit', [
            'user' => $user,
            'tokenLensBalance' => $wallet->balance($user),
            'tokenLensTransactions' => $user->tokenLensTransactions()->latest('created_at')->limit(20)->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'lenticular_plan' => ['required', Rule::in($this->plans())],
            'plan_expires_at' => ['nullable', 'date', 'after:today'],
        ]);

        $user->update($validated);

        return redirect()->route('admin.users.edit', $user)
            ->with('status', __('admin.users.messages.updated'));
    }

    public function suspend(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->is($user), 422, __('admin.users.messages.cannot_suspend_self'));
        abort_if($user->role === User::ROLE_SUPER_ADMIN, 403);

        $user->forceFill(['suspended_at' => now()])->save();

        return back()->with('status', __('admin.users.messages.suspended'));
    }

    public function restore(User $user): RedirectResponse
    {
        $user->forceFill(['suspended_at' => null])->save();

        return back()->with('status', __('admin.users.messages.restored'));
    }

    public function adjustTokens(Request $request, User $user, TokenLensWalletService $wallet): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'integer', 'between:-10000,10000', 'not_in:0'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $wallet->adminAdjust($user, (int) $validated['amount'], $validated['reason'], $request->user());

        return back()->with('status', __('admin.users.messages.tokens_adjusted'));
    }

    /** @return list<string> */
    private function plans(): array
    {
        return [
            LenticularAccessService::FREE,
            LenticularAccessService::PRO,
            LenticularAccessService::PREMIUM,
        ];
    }
}
