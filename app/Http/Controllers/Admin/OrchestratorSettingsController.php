<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArticleCategory;
use App\Services\AiTranslationSettingsService;
use App\Services\OrchestratorSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrchestratorSettingsController extends Controller
{
    public function edit(
        OrchestratorSettingsService $settings,
        AiTranslationSettingsService $aiSettings
    ): View {
        return view(
            'admin.settings.orchestrator',
            [
                'settings' => $settings,
                'aiSettings' => $aiSettings,
                'categories' =>
                    ArticleCategory::query()
                        ->where(
                            'is_active',
                            true
                        )
                        ->orderBy(
                            'sort_order'
                        )
                        ->orderBy('name')
                        ->get(),
                'supportedLocales' =>
                    config(
                        'locales.supported',
                        []
                    ),
            ]
        );
    }

    public function update(
        Request $request,
        OrchestratorSettingsService $settings
    ): RedirectResponse {
        $supportedLocales =
            array_keys(
                config(
                    'locales.supported',
                    ['pl' => []]
                )
            );

        $validated =
            $request->validate([
                'enabled' => [
                    'nullable',
                    'boolean',
                ],
                'provider' => [
                    'required',
                    Rule::in([
                        'openai',
                        'gemini',
                    ]),
                ],
                'openai_model' => [
                    'required',
                    'string',
                    'max:120',
                ],
                'gemini_model' => [
                    'required',
                    'string',
                    'max:120',
                ],
                'timeout' => [
                    'required',
                    'integer',
                    'min:20',
                    'max:300',
                ],
                'weekly_article_limit' => [
                    'required',
                    'integer',
                    'min:1',
                    'max:7',
                ],
                'min_relevance' => [
                    'required',
                    'integer',
                    'min:0',
                    'max:100',
                ],
                'target_words' => [
                    'required',
                    'integer',
                    'min:450',
                    'max:2200',
                ],
                'source_locale' => [
                    'required',
                    Rule::in(
                        $supportedLocales
                    ),
                ],
                'default_category_id' => [
                    'required',
                    'integer',
                    Rule::exists(
                        'article_categories',
                        'id'
                    )->where(
                        fn ($query) =>
                            $query->where(
                                'is_active',
                                true
                            )
                    ),
                ],
                'schedule_slots' => [
                    'required',
                    'string',
                    'max:2000',
                ],
                'extra_instructions' => [
                    'nullable',
                    'string',
                    'max:30000',
                ],
            ]);

        $slots =
            collect(
                preg_split(
                    '/\R+/',
                    $validated[
                        'schedule_slots'
                    ]
                ) ?: []
            )
                ->map(
                    fn ($line) =>
                        trim(
                            (string) $line
                        )
                )
                ->filter()
                ->values();

        foreach ($slots as $index => $slot) {
            if (
                ! preg_match(
                    '/^[1-7]@(?:[01]\d|2[0-3]):[0-5]\d$/',
                    $slot
                )
            ) {
                throw ValidationException::withMessages([
                    'schedule_slots' =>
                        __('orchestrator.settings.slot_invalid', [
                            'line' =>
                                $index + 1,
                        ]),
                ]);
            }
        }

        if (
            $slots->unique()->count()
            < (int) $validated[
                'weekly_article_limit'
            ]
        ) {
            throw ValidationException::withMessages([
                'schedule_slots' =>
                    __('orchestrator.settings.slot_count'),
            ]);
        }

        $settings->set(
            'enabled',
            $request->boolean(
                'enabled'
            ) ? '1' : '0'
        );

        $settings->set(
            'provider',
            $validated['provider']
        );

        $settings->set(
            'openai.model',
            trim(
                $validated[
                    'openai_model'
                ]
            )
        );

        $settings->set(
            'gemini.model',
            trim(
                $validated[
                    'gemini_model'
                ]
            )
        );

        foreach ([
            'timeout',
            'weekly_article_limit',
            'min_relevance',
            'target_words',
            'default_category_id',
        ] as $key) {
            $settings->set(
                $key,
                (string) $validated[$key]
            );
        }

        $settings->set(
            'source_locale',
            $validated['source_locale']
        );

        $settings->set(
            'schedule_slots',
            $slots->implode("\n")
        );

        $extra = trim(
            (string) (
                $validated[
                    'extra_instructions'
                ]
                ?? ''
            )
        );

        $settings->set(
            'extra_instructions',
            $extra === ''
                ? null
                : $extra
        );

        return back()->with(
            'status',
            __('orchestrator.settings.saved')
        );
    }
}
