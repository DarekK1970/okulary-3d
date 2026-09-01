<?php

namespace App\Http\Middleware;

use App\Services\FurgonetkaSettingsService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyFurgonetkaUniversalToken
{
    public function __construct(
        private readonly FurgonetkaSettingsService $settings
    ) {
    }

    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $expected =
            $this->settings
                ->universalToken();

        if (
            ! $this->settings
                ->enabled()
            || blank($expected)
        ) {
            return $this
                ->unauthorized();
        }

        $provided = trim(
            (string) $request
                ->header(
                    'Authorization',
                    ''
                )
        );

        /*
         * The official Universal example uses the raw token
         * in the Authorization header. Bearer is accepted as
         * a harmless compatibility fallback.
         */
        if (
            str_starts_with(
                $provided,
                'Bearer '
            )
        ) {
            $provided = trim(
                substr(
                    $provided,
                    7
                )
            );
        }

        if (
            $provided === ''
            || ! hash_equals(
                (string) $expected,
                $provided
            )
        ) {
            return $this
                ->unauthorized();
        }

        return $next($request);
    }

    private function unauthorized(): JsonResponse
    {
        return response()->json(
            [
                'message' =>
                    'Unauthorized.',
            ],
            401
        );
    }
}
