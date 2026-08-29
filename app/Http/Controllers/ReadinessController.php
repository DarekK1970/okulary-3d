<?php

namespace App\Http\Controllers;

use App\Services\ReleaseReadinessService;
use Illuminate\Http\JsonResponse;

class ReadinessController extends Controller
{
    public function __invoke(
        ReleaseReadinessService $readiness
    ): JsonResponse {
        $checks = $readiness->runtimeChecks();
        $ready = $readiness->requiredChecksPass(
            $checks
        );

        return response()->json(
            [
                'status' => $ready
                    ? 'ok'
                    : 'unavailable',
                'checks' => collect($checks)
                    ->mapWithKeys(
                        static fn (
                            array $check,
                            string $key
                        ): array => [
                            $key => (bool) $check['ok'],
                        ]
                    )
                    ->all(),
            ],
            $ready ? 200 : 503
        );
    }
}
