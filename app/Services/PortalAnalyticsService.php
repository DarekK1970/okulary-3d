<?php

namespace App\Services;

use App\Models\PortalAnalyticsEvent;
use App\Models\PortalAnalyticsPageView;
use App\Models\PortalAnalyticsSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class PortalAnalyticsService
{
    private const SESSION_MINUTES = 30;

    /**
     * Routes never stored in analytics because they can contain
     * private tokens or personal account context.
     *
     * @var list<string>
     */
    private const PRIVATE_ROUTE_PATTERNS = [
        'admin.*',
        'login',
        'login.store',
        'register',
        'register.store',
        'password.*',
        'account',
        'account.*',
        'order.*',
        'payment.*',
        'analytics.event',
        'newsletter.confirm',
        'newsletter.unsubscribe.form',
        'newsletter.unsubscribe',
        'health.ready',
        'sitemap',
        'robots',
    ];

    public function shouldTrackPageView(
        Request $request,
        Response $response
    ): bool {
        if (
            ! in_array(
                strtoupper(
                    $request->method()
                ),
                ['GET', 'HEAD'],
                true
            )
        ) {
            return false;
        }

        if (
            $response->getStatusCode() < 200
            || $response->getStatusCode() >= 300
        ) {
            return false;
        }

        if (
            $request->expectsJson()
            || $request->ajax()
        ) {
            return false;
        }

        if ($this->doNotTrack($request)) {
            return false;
        }

        if ($this->isBot($request)) {
            return false;
        }

        if ($request->is('up')) {
            return false;
        }

        $routeName =
            $request->route()?->getName();

        if (
            $this->isPrivateRoute(
                $routeName
            )
        ) {
            return false;
        }

        $contentType =
            strtolower(
                (string)
                $response->headers->get(
                    'Content-Type'
                )
            );

        return $contentType === ''
            || str_contains(
                $contentType,
                'text/html'
            );
    }

    public function trackPageView(
        Request $request
    ): void {
        $session =
            $this->sessionForRequest(
                $request
            );

        $routeName =
            $request->route()?->getName();

        $referrerDomain =
            $this->referrerDomain(
                $request
            );

        DB::transaction(
            function () use (
                $session,
                $request,
                $routeName,
                $referrerDomain
            ): void {
                PortalAnalyticsPageView::create([
                    'analytics_session_id' =>
                        $session->id,
                    'route_name' =>
                        $routeName,
                    'path' =>
                        '/' . ltrim(
                            $request->path(),
                            '/'
                        ),
                    'locale' =>
                        $request->route(
                            'locale'
                        ),
                    'page_type' =>
                        $this->pageType(
                            $routeName
                        ),
                    'referrer_domain' =>
                        $referrerDomain,
                    'occurred_at' =>
                        now(),
                ]);

                $session->forceFill([
                    'last_seen_at' =>
                        now(),
                    'is_authenticated' =>
                        auth()->check(),
                    'pageviews_count' =>
                        $session
                            ->pageviews_count
                        + 1,
                ])->save();
            }
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function trackEvent(
        Request $request,
        array $payload
    ): void {
        if (
            $this->doNotTrack($request)
            || $this->isBot($request)
        ) {
            return;
        }

        $session =
            $this->sessionForRequest(
                $request
            );

        DB::transaction(
            function () use (
                $session,
                $payload
            ): void {
                PortalAnalyticsEvent::create([
                    'analytics_session_id' =>
                        $session->id,
                    'event_name' =>
                        $payload[
                            'event_name'
                        ],
                    'category' =>
                        $payload['category']
                        ?? null,
                    'label' =>
                        $payload['label']
                        ?? null,
                    'value' =>
                        $payload['value']
                        ?? null,
                    'route_name' =>
                        $payload['route_name']
                        ?? null,
                    'path' =>
                        $payload['path']
                        ?? null,
                    'locale' =>
                        $payload['locale']
                        ?? null,
                    'metadata' =>
                        $payload['metadata']
                        ?? null,
                    'occurred_at' =>
                        now(),
                ]);

                $session->forceFill([
                    'last_seen_at' =>
                        now(),
                    'is_authenticated' =>
                        auth()->check(),
                    'events_count' =>
                        $session
                            ->events_count
                        + 1,
                ])->save();
            }
        );
    }

    private function sessionForRequest(
        Request $request
    ): PortalAnalyticsSession {
        $browserSessionHash =
            $this->browserSessionHash(
                $request
            );

        $cutoff =
            now()->subMinutes(
                self::SESSION_MINUTES
            );

        $session =
            PortalAnalyticsSession::query()
                ->where(
                    'browser_session_hash',
                    $browserSessionHash
                )
                ->where(
                    'last_seen_at',
                    '>=',
                    $cutoff
                )
                ->latest('last_seen_at')
                ->first();

        if ($session) {
            return $session;
        }

        $referrerDomain =
            $this->referrerDomain(
                $request
            );

        [$sourceGroup, $sourceName] =
            $this->trafficSource(
                $request,
                $referrerDomain
            );

        return PortalAnalyticsSession::create([
            'id' => (string) Str::uuid(),
            'browser_session_hash' =>
                $browserSessionHash,
            'started_at' => now(),
            'last_seen_at' => now(),
            'landing_path' =>
                '/' . ltrim(
                    $request->path(),
                    '/'
                ),
            'landing_locale' =>
                $request->route(
                    'locale'
                ),
            'source_group' =>
                $sourceGroup,
            'source_name' =>
                $sourceName,
            'referrer_domain' =>
                $referrerDomain,
            'utm_source' =>
                $this->clean(
                    $request->query(
                        'utm_source'
                    ),
                    190
                ),
            'utm_medium' =>
                $this->clean(
                    $request->query(
                        'utm_medium'
                    ),
                    190
                ),
            'utm_campaign' =>
                $this->clean(
                    $request->query(
                        'utm_campaign'
                    ),
                    190
                ),
            'device_type' =>
                $this->deviceType(
                    $request
                ),
            'is_authenticated' =>
                auth()->check(),
            'pageviews_count' => 0,
            'events_count' => 0,
        ]);
    }

    private function browserSessionHash(
        Request $request
    ): string {
        $sessionId =
            $request->hasSession()
                ? $request->session()
                    ->getId()
                : Str::uuid()
                    ->toString();

        $key = (string) config(
            'app.key',
            'portal-analytics'
        );

        return hash_hmac(
            'sha256',
            $sessionId,
            $key
        );
    }

    private function doNotTrack(
        Request $request
    ): bool {
        return trim(
            (string)
            $request->header('DNT')
        ) === '1';
    }

    private function isBot(
        Request $request
    ): bool {
        $agent = strtolower(
            (string)
            $request->userAgent()
        );

        if ($agent === '') {
            return false;
        }

        return preg_match(
            '/bot|crawler|spider|slurp|bingpreview|facebookexternalhit|whatsapp|telegrambot|discordbot|linkedinbot|googleother|headless|lighthouse|pagespeed/i',
            $agent
        ) === 1;
    }

    private function isPrivateRoute(
        ?string $routeName
    ): bool {
        if (! $routeName) {
            return false;
        }

        foreach (
            self::PRIVATE_ROUTE_PATTERNS
            as $pattern
        ) {
            if (
                Str::is(
                    $pattern,
                    $routeName
                )
            ) {
                return true;
            }
        }

        return false;
    }

    private function pageType(
        ?string $routeName
    ): string {
        if (! $routeName) {
            return 'other';
        }

        return match (true) {
            $routeName === 'home'
                => 'home',

            Str::is(
                'articles.*',
                $routeName
            )
                => 'article',

            $routeName === 'shop.index'
                => 'shop',

            $routeName === 'shop.show'
                => 'product',

            Str::is(
                'lab.*',
                $routeName
            )
                => 'lab',

            Str::is(
                'archive.*',
                $routeName
            )
                => 'archive',

            Str::is(
                'gallery.*',
                $routeName
            )
                => 'gallery',

            Str::is(
                'cart.*',
                $routeName
            )
                => 'cart',

            Str::is(
                'checkout.*',
                $routeName
            )
                => 'checkout',

            default
                => 'other',
        };
    }

    private function referrerDomain(
        Request $request
    ): ?string {
        $referrer = trim(
            (string)
            $request->headers->get(
                'referer'
            )
        );

        if ($referrer === '') {
            return null;
        }

        $host = parse_url(
            $referrer,
            PHP_URL_HOST
        );

        return $this->clean(
            $host,
            190
        );
    }

    /**
     * @return array{0: string, 1: string|null}
     */
    private function trafficSource(
        Request $request,
        ?string $referrerDomain
    ): array {
        $utmSource =
            $this->clean(
                $request->query(
                    'utm_source'
                ),
                190
            );

        if ($utmSource) {
            return [
                'campaign',
                $utmSource,
            ];
        }

        if (! $referrerDomain) {
            return [
                'direct',
                null,
            ];
        }

        $currentHost =
            strtolower(
                (string)
                $request->getHost()
            );

        $referrer =
            strtolower(
                $referrerDomain
            );

        if ($referrer === $currentHost) {
            return [
                'internal',
                $referrerDomain,
            ];
        }

        $searchDomains = [
            'google.',
            'bing.com',
            'duckduckgo.com',
            'search.yahoo.',
            'yandex.',
            'baidu.',
        ];

        foreach (
            $searchDomains
            as $needle
        ) {
            if (
                str_contains(
                    $referrer,
                    $needle
                )
            ) {
                return [
                    'search',
                    $referrerDomain,
                ];
            }
        }

        $socialDomains = [
            'facebook.com',
            'instagram.com',
            'linkedin.com',
            'twitter.com',
            'x.com',
            'reddit.com',
            'wykop.pl',
            'pinterest.',
            'tiktok.com',
            'youtube.com',
        ];

        foreach (
            $socialDomains
            as $needle
        ) {
            if (
                str_contains(
                    $referrer,
                    $needle
                )
            ) {
                return [
                    'social',
                    $referrerDomain,
                ];
            }
        }

        return [
            'referral',
            $referrerDomain,
        ];
    }

    private function deviceType(
        Request $request
    ): string {
        $agent = strtolower(
            (string)
            $request->userAgent()
        );

        if (
            preg_match(
                '/ipad|tablet|kindle|silk/i',
                $agent
            )
        ) {
            return 'tablet';
        }

        if (
            preg_match(
                '/mobile|iphone|android.*mobile|windows phone/i',
                $agent
            )
        ) {
            return 'mobile';
        }

        if ($agent !== '') {
            return 'desktop';
        }

        return 'other';
    }

    private function clean(
        mixed $value,
        int $limit
    ): ?string {
        $value = trim(
            (string) $value
        );

        if ($value === '') {
            return null;
        }

        return Str::limit(
            $value,
            $limit,
            ''
        );
    }
}
