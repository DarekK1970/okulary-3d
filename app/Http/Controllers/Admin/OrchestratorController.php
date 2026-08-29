<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrchestratorPlan;
use App\Models\OrchestratorPlanItem;
use App\Services\OrchestratorService;
use App\Services\OrchestratorSettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class OrchestratorController extends Controller
{
    public function index(
        OrchestratorService $orchestrator,
        OrchestratorSettingsService $settings
    ): View {
        return view(
            'admin.orchestrator.index',
            [
                'settings' => $settings,
                'availableCandidates' =>
                    $orchestrator
                        ->availableAcceptedCount(),
                'usage' =>
                    $orchestrator
                        ->usageSummary(7),
                'plans' =>
                    OrchestratorPlan::query()
                        ->withCount('items')
                        ->with([
                            'creator',
                            'approver',
                        ])
                        ->latest(
                            'week_start'
                        )
                        ->limit(12)
                        ->get(),
                'defaultWeek' =>
                    CarbonImmutable::now(
                        config(
                            'app.timezone',
                            'UTC'
                        )
                    )
                        ->startOfWeek()
                        ->addWeek()
                        ->toDateString(),
            ]
        );
    }

    public function createPlan(
        Request $request,
        OrchestratorService $orchestrator,
        OrchestratorSettingsService $settings
    ): RedirectResponse {
        $validated = $request->validate([
            'week_start' => [
                'required',
                'date',
            ],
            'article_limit' => [
                'required',
                'integer',
                'min:1',
                'max:7',
            ],
        ]);

        if (! $settings->configured()) {
            return back()
                ->withErrors([
                    'orchestrator' =>
                        __('orchestrator.errors.not_configured'),
                ])
                ->withInput();
        }

        try {
            $plan =
                $orchestrator->createPlan(
                    $validated['week_start'],
                    (int) $validated[
                        'article_limit'
                    ],
                    $request->user()
                );
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withErrors([
                    'orchestrator' =>
                        $exception->getMessage(),
                ])
                ->withInput();
        }

        return redirect()
            ->route(
                'admin.orchestrator.plans.show',
                $plan
            )
            ->with(
                'status',
                __('orchestrator.messages.plan_created')
            );
    }

    public function show(
        OrchestratorPlan $plan
    ): View {
        $plan->load([
            'items.candidate.sources',
            'items.article.translations',
            'creator',
            'approver',
        ]);

        return view(
            'admin.orchestrator.show',
            compact('plan')
        );
    }

    public function approve(
        Request $request,
        OrchestratorPlan $plan,
        OrchestratorService $orchestrator
    ): RedirectResponse {
        try {
            $orchestrator->approvePlan(
                $plan,
                $request->user()
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'orchestrator' =>
                    $exception->getMessage(),
            ]);
        }

        return back()->with(
            'status',
            __('orchestrator.messages.plan_approved')
        );
    }

    public function destroy(
        OrchestratorPlan $plan,
        OrchestratorService $orchestrator
    ): RedirectResponse {
        try {
            $orchestrator->deleteDraftPlan(
                $plan
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'orchestrator' =>
                    $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route(
                'admin.orchestrator.index'
            )
            ->with(
                'status',
                __('orchestrator.messages.plan_deleted')
            );
    }

    public function generateDraft(
        Request $request,
        OrchestratorPlanItem $item,
        OrchestratorService $orchestrator
    ): RedirectResponse {
        try {
            $article =
                $orchestrator->generateDraft(
                    $item,
                    $request->user()
                );
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'orchestrator' =>
                    $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route(
                'admin.articles.edit',
                $article
            )
            ->with(
                'status',
                __('orchestrator.messages.draft_created')
            );
    }
}
