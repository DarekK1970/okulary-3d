<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MaintenanceModeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MaintenanceSettingsController extends Controller
{
    public function edit(
        Request $request,
        MaintenanceModeService $maintenance
    ): View {
        $allowedIpText = $maintenance->allowedIpText();

        if ($allowedIpText === '') {
            $allowedIpText = (string) $request->ip();
        }

        return view(
            'admin.settings.maintenance',
            [
                'maintenanceEnabled' => $maintenance->enabled(),
                'allowedIpText' => $allowedIpText,
                'currentIp' => (string) $request->ip(),
            ]
        );
    }

    public function update(
        Request $request,
        MaintenanceModeService $maintenance
    ): RedirectResponse {
        $validated = $request->validate([
            'enabled' => [
                'nullable',
                'boolean',
            ],
            'allowed_ips' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        [$allowedIps, $invalidIps] = $this->parseIpList(
            (string) ($validated['allowed_ips'] ?? '')
        );

        if ($invalidIps !== []) {
            throw ValidationException::withMessages([
                'allowed_ips' => __(
                    'maintenance.errors.invalid_ips',
                    [
                        'ips' => implode(', ', $invalidIps),
                    ]
                ),
            ]);
        }

        if (
            $request->boolean('enabled')
            && $allowedIps === []
        ) {
            throw ValidationException::withMessages([
                'allowed_ips' => __(
                    'maintenance.errors.ip_required_when_enabled'
                ),
            ]);
        }

        $maintenance->save(
            $request->boolean('enabled'),
            $allowedIps
        );

        return redirect()
            ->route('admin.settings.maintenance')
            ->with(
                'status',
                __('maintenance.messages.saved')
            );
    }

    /**
     * @return array{0: list<string>, 1: list<string>}
     */
    private function parseIpList(string $input): array
    {
        $tokens = preg_split(
            '/[\s,;]+/',
            trim($input)
        ) ?: [];

        $valid = [];
        $invalid = [];

        foreach ($tokens as $token) {
            $ip = trim($token);

            if ($ip === '') {
                continue;
            }

            if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
                $invalid[] = $ip;
                continue;
            }

            $valid[] = $ip;
        }

        return [
            array_values(array_unique($valid)),
            array_values(array_unique($invalid)),
        ];
    }
}
