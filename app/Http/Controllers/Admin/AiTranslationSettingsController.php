<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AiTranslationSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AiTranslationSettingsController extends Controller
{
    public function edit(
        AiTranslationSettingsService $settings
    ): View {
        return view(
            'admin.settings.ai-translation',
            [
                'settings' => $settings,
                'openAiKeyMasked' =>
                    $settings->maskedSecret('openai'),
                'geminiKeyMasked' =>
                    $settings->maskedSecret('gemini'),
            ]
        );
    }

    public function update(
        Request $request,
        AiTranslationSettingsService $settings
    ): RedirectResponse {
        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'provider' => [
                'required',
                Rule::in(['openai', 'gemini']),
            ],
            'timeout' => [
                'required',
                'integer',
                'min:10',
                'max:180',
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
            'openai_api_key' => [
                'nullable',
                'string',
                'max:500',
            ],
            'gemini_api_key' => [
                'nullable',
                'string',
                'max:500',
            ],
            'clear_openai_api_key' => [
                'nullable',
                'boolean',
            ],
            'clear_gemini_api_key' => [
                'nullable',
                'boolean',
            ],
            'glossary' => [
                'nullable',
                'string',
                'max:20000',
            ],
        ]);

        $settings->set(
            'enabled',
            $request->boolean('enabled')
                ? '1'
                : '0'
        );

        $settings->set(
            'provider',
            $validated['provider']
        );

        $settings->set(
            'timeout',
            (string) $validated['timeout']
        );

        $settings->set(
            'openai.model',
            trim($validated['openai_model'])
        );

        $settings->set(
            'gemini.model',
            trim($validated['gemini_model'])
        );

        $settings->set(
            'glossary',
            $this->nullable(
                $validated['glossary'] ?? null
            )
        );

        $this->updateSecret(
            $request,
            $settings,
            'openai'
        );

        $this->updateSecret(
            $request,
            $settings,
            'gemini'
        );

        return back()->with(
            'status',
            __('ai_translator.settings.saved')
        );
    }

    private function updateSecret(
        Request $request,
        AiTranslationSettingsService $settings,
        string $provider
    ): void {
        $keyField = $provider . '_api_key';
        $clearField = 'clear_' . $provider . '_api_key';

        if ($request->boolean($clearField)) {
            $settings->set(
                $provider . '.api_key',
                null,
                true
            );

            return;
        }

        if (filled($request->input($keyField))) {
            $settings->set(
                $provider . '.api_key',
                trim((string) $request->input($keyField)),
                true
            );
        }
    }

    private function nullable(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
