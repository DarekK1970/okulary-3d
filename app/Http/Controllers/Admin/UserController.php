<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LenticularArtifact;
use App\Models\LenticularProject;
use App\Models\LenticularProjectFile;
use App\Models\User;
use App\Services\LenticularAccessService;
use App\Services\TokenLensWalletService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserController extends Controller
{
    public function index(Request $request, LenticularAccessService $access): View
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
            ->when($filters['plan'] ?? null, fn (Builder $query, string $plan) => $this->filterByEffectivePlan($query, $plan))
            ->when(($filters['status'] ?? null) === 'active', fn (Builder $query) => $query->whereNull('suspended_at'))
            ->when(($filters['status'] ?? null) === 'suspended', fn (Builder $query) => $query->whereNotNull('suspended_at'))
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString();

        $users->getCollection()->each(
            fn (User $user) => $user->setAttribute('effective_lenticular_plan', $access->plan($user))
        );

        return view('admin.users.index', compact('users', 'filters'));
    }

    public function projects(User $user): View
    {
        return view('admin.users.projects', [
            'user' => $user,
            'projects' => $user->lenticularProjects()
                ->with(['files', 'jobs.artifacts'])
                ->latest('created_at')
                ->paginate(20),
        ]);
    }

    public function projectFiles(User $user, LenticularProject $project): View
    {
        $this->ensureProjectOwner($user, $project);
        $project->load(['files', 'jobs.artifacts']);

        return view('admin.users.project-files', compact('user', 'project'));
    }

    public function projectFile(User $user, LenticularProject $project, LenticularProjectFile $file, Request $request): BinaryFileResponse
    {
        $this->ensureProjectOwner($user, $project);
        abort_unless($file->lenticular_project_id === $project->id, 404);
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        $path = Storage::disk($file->disk)->path($file->path);

        return $request->boolean('download')
            ? response()->download($path, $file->original_name)
            : response()->file($path, ['Content-Type' => $file->media_type ?: 'application/octet-stream']);
    }

    public function projectArtifact(User $user, LenticularProject $project, LenticularArtifact $artifact): BinaryFileResponse
    {
        $this->ensureProjectOwner($user, $project);
        abort_unless($artifact->lenticularJob?->lenticular_project_id === $project->id, 404);
        abort_unless(Storage::disk($artifact->disk)->exists($artifact->path), 404);

        return response()->download(Storage::disk($artifact->disk)->path($artifact->path));
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

    private function filterByEffectivePlan(Builder $query, string $plan): Builder
    {
        if ($plan === LenticularAccessService::PREMIUM) {
            return $query->where(function (Builder $query): void {
                $query->where('role', User::ROLE_SUPER_ADMIN)
                    ->orWhere(function (Builder $query): void {
                        $query->where('role', '!=', User::ROLE_SUPER_ADMIN)
                            ->where('lenticular_plan', LenticularAccessService::PREMIUM)
                            ->where(fn (Builder $query) => $query->whereNull('plan_expires_at')->orWhere('plan_expires_at', '>', now()));
                    });
            });
        }

        if ($plan === LenticularAccessService::PRO) {
            return $query->where('role', '!=', User::ROLE_SUPER_ADMIN)
                ->where('lenticular_plan', LenticularAccessService::PRO)
                ->where(fn (Builder $query) => $query->whereNull('plan_expires_at')->orWhere('plan_expires_at', '>', now()));
        }

        return $query->where('role', '!=', User::ROLE_SUPER_ADMIN)
            ->where(function (Builder $query): void {
                $query->whereNotIn('lenticular_plan', [LenticularAccessService::PRO, LenticularAccessService::PREMIUM])
                    ->orWhere('plan_expires_at', '<=', now());
            });
    }

    private function ensureProjectOwner(User $user, LenticularProject $project): void
    {
        abort_unless($project->user_id === $user->id, 404);
    }
}
