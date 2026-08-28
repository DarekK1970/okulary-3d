<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiTranslationRun;
use App\Services\AiTranslationService;
use App\Services\AiTranslationSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class AiTranslationController extends Controller
{
    public function index(
        Request $request,
        AiTranslationService $translator,
        AiTranslationSettingsService $settings
    ): View {
        $allowedTypes = $translator->allowedTypesFor(
            $request->user()
        );

        $type = (string) $request->input(
            'type',
            $allowedTypes[0]
        );

        if (! in_array($type, $allowedTypes, true)) {
            abort(403);
        }

        $modelClass = $translator->modelClass($type);

        /** @var LengthAwarePaginator $items */
        $items = $modelClass::query()
            ->with('translations')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $rows = $items->getCollection()
            ->map(
                fn ($item) => $translator->describe(
                    $type,
                    $item
                )
            );

        $items->setCollection($rows);

        return view('admin.translations.index', [
            'items' => $items,
            'type' => $type,
            'allowedTypes' => $allowedTypes,
            'settings' => $settings,
            'recentRuns' => AiTranslationRun::query()
                ->with('user')
                ->latest()
                ->limit(12)
                ->get(),
        ]);
    }

    public function translate(
        Request $request,
        string $type,
        int $id,
        AiTranslationService $translator
    ): RedirectResponse {
        if (! in_array(
            $type,
            $translator->allowedTypesFor($request->user()),
            true
        )) {
            abort(403);
        }

        try {
            $run = $translator->translate(
                $type,
                $id,
                $request->user()
            );
        } catch (\Throwable $exception) {
            return back()->withErrors([
                'translation' => $exception->getMessage(),
            ]);
        }

        return back()->with(
            'status',
            __('ai_translator.messages.generated', [
                'provider' => strtoupper($run->provider),
                'model' => $run->model,
            ])
        );
    }
}
