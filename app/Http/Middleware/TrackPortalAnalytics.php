<?php

namespace App\Http\Middleware;

use App\Services\PortalAnalyticsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TrackPortalAnalytics
{
    public function __construct(
        private PortalAnalyticsService $analytics
    ) {
    }

    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $response = $next($request);

        if (
            ! $this->analytics
                ->shouldTrackPageView(
                    $request,
                    $response
                )
        ) {
            return $response;
        }

        try {
            $this->analytics
                ->trackPageView(
                    $request
                );
        } catch (Throwable $exception) {
            report($exception);
        }

        return $response;
    }
}
