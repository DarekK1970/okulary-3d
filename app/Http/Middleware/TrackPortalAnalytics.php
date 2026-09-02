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

        try {
            $bot =
                $this->analytics
                    ->detectBot(
                        $request
                    );

            if ($bot) {
                if (
                    $this->analytics
                        ->shouldTrackBotRequest(
                            $request
                        )
                ) {
                    $this->analytics
                        ->trackBotRequest(
                            $request,
                            $response,
                            $bot
                        );
                }

                /*
                 * Critical rule:
                 * a detected bot NEVER creates a human analytics
                 * session/page view. It is already recorded in the
                 * separate bot stream above.
                 */
                return $response;
            }

            if (
                ! $this->analytics
                    ->shouldTrackPageView(
                        $request,
                        $response
                    )
            ) {
                return $response;
            }

            $this->analytics
                ->trackPageView(
                    $request
                );
        } catch (Throwable $exception) {
            /*
             * Analytics must never break the public request.
             */
            report($exception);
        }

        return $response;
    }
}
