<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FalAiClient;
use App\Services\FalAiSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class FalAiSettingsController extends Controller
{
    public function edit(FalAiSettingsService $settings): View
    {
        return view('admin.settings.fal-ai', [
            'settings' => $settings,
            'apiKeyMasked' => $settings->maskedSecret(),
        ]);
    }

    public function update(Request $request, FalAiSettingsService $settings): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'api_key' => ['nullable', 'string', 'max:500'],
            'clear_api_key' => ['nullable', 'boolean'],
            'timeout' => ['required', 'integer', 'min:10', 'max:180'],
            'seedance_model' => ['required', 'string', 'max:160'],
            'resolution' => ['required', Rule::in(['480p', '720p'])],
            'duration' => ['required', 'integer', 'min:4', 'max:30'],
            'generate_audio' => ['nullable', 'boolean'],
            'upscaling_enabled' => ['nullable', 'boolean'],
            'upscaler_model' => ['required', 'string', 'max:160'],
            'upscale_resolution' => ['required', Rule::in(['1080p', '2k', '4k', '6k', '8k'])],
            'maximum_job_cost_usd' => ['required', 'numeric', 'min:0.01', 'max:1000'],
            'daily_budget_usd' => ['required', 'numeric', 'min:0.01', 'max:10000'],
        ]);

        foreach (['enabled', 'generate_audio', 'upscaling_enabled'] as $field) {
            $settings->set($field, $request->boolean($field) ? '1' : '0');
        }
        foreach (['timeout', 'seedance_model', 'resolution', 'duration', 'upscaler_model', 'upscale_resolution', 'maximum_job_cost_usd', 'daily_budget_usd'] as $field) {
            $settings->set($field, trim((string) $validated[$field]));
        }

        if ($request->boolean('clear_api_key')) {
            $settings->set('api_key', null, true);
        } elseif (filled($validated['api_key'] ?? null)) {
            $settings->set('api_key', trim((string) $validated['api_key']), true);
        }

        return back()->with('status', __('fal_ai.messages.saved'));
    }

    public function test(FalAiClient $client): RedirectResponse
    {
        try {
            $client->testConnection();
        } catch (RuntimeException $exception) {
            return back()->withErrors(['fal_ai' => $exception->getMessage()]);
        }

        return back()->with('status', __('fal_ai.messages.test_success'));
    }
}
