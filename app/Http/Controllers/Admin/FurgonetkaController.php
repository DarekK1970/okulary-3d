<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FurgonetkaSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FurgonetkaController extends Controller
{
    public function settings(
        FurgonetkaSettingsService $settings
    ): View {
        return view(
            'admin.shipping.furgonetka-settings',
            [
                'settings' =>
                    $settings,
                'universalToken' =>
                    $settings
                        ->universalToken(),
            ]
        );
    }

    public function updateSettings(
        Request $request,
        FurgonetkaSettingsService $settings
    ): RedirectResponse {
        $validated = $request->validate([
            'enabled' => [
                'nullable',
                'boolean',
            ],
            'map_api_key' => [
                'nullable',
                'string',
                'max:512',
            ],
        ]);

        $settings->set(
            'enabled',
            $request->boolean(
                'enabled'
            )
                ? '1'
                : '0'
        );

        if (
            filled(
                $validated[
                    'map_api_key'
                ] ?? null
            )
        ) {
            $settings->set(
                'map_api_key',
                $validated[
                    'map_api_key'
                ],
                true
            );
        }

        $settings
            ->removeLegacyOAuthCredentials();

        return back()->with(
            'status',
            __(
                'furgonetka.messages.settings_saved'
            )
        );
    }

    public function generateToken(
        FurgonetkaSettingsService $settings
    ): RedirectResponse {
        $settings
            ->generateUniversalToken();

        return back()->with(
            'status',
            __(
                'furgonetka.messages.token_generated'
            )
        );
    }
}
