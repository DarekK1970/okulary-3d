<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AiTranslationSettingsService;
use App\Services\DiscoverySettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DiscoverySettingsController extends Controller
{
    public function edit(
        DiscoverySettingsService $settings,
        AiTranslationSettingsService $aiSettings
    ): View {
        return view(
            'admin.settings.discovery',
            [
                'settings' => $settings,
                'openAiKeyMasked' =>
                    $aiSettings->maskedSecret('openai'),
                'geminiKeyMasked' =>
                    $aiSettings->maskedSecret('gemini'),
            ]
        );
    }

    public function update(
        Request $request,
        DiscoverySettingsService $settings
    ): RedirectResponse {
        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'provider' => [
                'required',
                Rule::in(['openai', 'gemini']),
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
            'min_sources' => [
                'required',
                'integer',
                'min:1',
                'max:6',
            ],
            'min_domains' => [
                'required',
                'integer',
                'min:1',
                'max:6',
            ],
            'exclude_polish_sources' => [
                'nullable',
                'boolean',
            ],
            'topics' => [
                'required',
                'string',
                'max:20000',
            ],
            'preferred_domains' => [
                'nullable',
                'string',
                'max:20000',
            ],
            'excluded_domains' => [
                'nullable',
                'string',
                'max:20000',
            ],
            'extra_instructions' => [
                'nullable',
                'string',
                'max:30000',
            ],
        ]);

        $settings->set(
            'enabled',
            $request->boolean('enabled') ? '1' : '0'
        );
        $settings->set('provider', $validated['provider']);
        $settings->set(
            'openai.model',
            trim($validated['openai_model'])
        );
        $settings->set(
            'gemini.model',
            trim($validated['gemini_model'])
        );
        $settings->set('timeout', (string) $validated['timeout']);
        $settings->set(
            'freshness_days',
            (string) $validated['freshness_days']
        );
        $settings->set(
            'candidate_limit',
            (string) $validated['candidate_limit']
        );
        $settings->set(
            'min_sources',
            (string) $validated['min_sources']
        );
        $settings->set(
            'min_domains',
            (string) $validated['min_domains']
        );
        $settings->set(
            'exclude_polish_sources',
            $request->boolean('exclude_polish_sources')
                ? '1'
                : '0'
        );
        $settings->set(
            'topics',
            trim($validated['topics'])
        );
        $settings->set(
            'preferred_domains',
            $this->nullable($validated['preferred_domains'] ?? null)
        );
        $settings->set(
            'excluded_domains',
            $this->nullable($validated['excluded_domains'] ?? null)
        );
        $settings->set(
            'extra_instructions',
            $this->nullable($validated['extra_instructions'] ?? null)
        );

        return back()->with(
            'status',
            __('discovery.settings.saved')
        );
    }

    private function nullable(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
