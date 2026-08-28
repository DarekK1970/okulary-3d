<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DiscoveryDecision;
use App\Http\Controllers\Controller;
use App\Models\DiscoveryCandidate;
use App\Models\DiscoveryRun;
use App\Services\DiscoveryService;
use App\Services\DiscoverySettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class DiscoveryController extends Controller
{
    public function index(
        Request $request,
        DiscoverySettingsService $settings
    ): View {
        $query = DiscoveryCandidate::query()
            ->with([
                'run',
                'sources',
                'decisionUser',
            ])
            ->latest();

        if ($request->filled('decision')) {
            $query->where(
                'decision',
                $request->string('decision')->toString()
            );
        }

        if ($request->filled('section')) {
            $query->where(
                'suggested_section',
                $request->string('section')->toString()
            );
        }

        if ($request->filled('q')) {
            $search = trim(
                $request->string('q')->toString()
            );

            $query->where(function ($builder) use ($search) {
                $builder
                    ->where(
                        'title',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'summary',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'cluster_key',
                        'like',
                        '%' . $search . '%'
                    );
            });
        }

        return view(
            'admin.discovery.index',
            [
                'settings' => $settings,
                'topics' => $settings->topics(),
                'candidates' => $query
                    ->paginate(20)
                    ->withQueryString(),
                'runs' => DiscoveryRun::query()
                    ->latest()
                    ->limit(8)
                    ->get(),
            ]
        );
    }

    public function run(
        Request $request,
        DiscoveryService $discovery,
        DiscoverySettingsService $settings
    ): RedirectResponse {
        $validated = $request->validate([
            'topic' => [
                'required',
                'string',
                'max:190',
            ],
            'query' => [
                'nullable',
                'string',
                'max:3000',
            ],
            'freshness_days' => [
                'required',
                'integer',
                'min:1',
                'max:365',
            ],
            'candidate_limit' => [
                'required',
                'integer',
                'min:1',
                'max:25',
            ],
        ]);

        if (! $settings->configured()) {
            return back()->withErrors([
                'discovery' =>
                    __('discovery.errors.not_configured'),
            ])->withInput();
        }

        try {
            $run = $discovery->run(
                $validated['topic'],
                $validated['query'] ?? '',
                $request->user(),
                (int) $validated['freshness_days'],
                (int) $validated['candidate_limit']
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'discovery' => $exception->getMessage(),
            ])->withInput();
        }

        return redirect()
            ->route('admin.discovery.index')
            ->with(
                'status',
                __('discovery.messages.completed', [
                    'saved' => $run->saved_candidates,
                    'skipped' => $run->skipped_candidates,
                    'duplicates' => $run->duplicate_candidates,
                ])
            );
    }

    public function show(
        DiscoveryCandidate $candidate
    ): View {
        $candidate->load([
            'run.user',
            'sources',
            'decisionUser',
        ]);

        return view(
            'admin.discovery.show',
            compact('candidate')
        );
    }

    public function decision(
        Request $request,
        DiscoveryCandidate $candidate
    ): RedirectResponse {
        $validated = $request->validate([
            'decision' => [
                'required',
                Rule::in(
                    DiscoveryDecision::values()
                ),
            ],
            'decision_note' => [
                'nullable',
                'string',
                'max:4000',
            ],
        ]);

        $candidate->update([
            'decision' => DiscoveryDecision::from(
                $validated['decision']
            ),
            'decision_by' => $request->user()->id,
            'decided_at' => now(),
            'decision_note' =>
                filled($validated['decision_note'] ?? null)
                    ? trim($validated['decision_note'])
                    : null,
        ]);

        return back()->with(
            'status',
            __('discovery.messages.decision_saved')
        );
    }
}
