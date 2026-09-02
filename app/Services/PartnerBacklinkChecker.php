<?php

namespace App\Services;

use App\Models\PartnerLink;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Support\Facades\Http;

class PartnerBacklinkChecker
{
    private const MAX_REDIRECTS = 5;
    private const MAX_BODY_BYTES = 2_097_152;
    private const CONNECT_TIMEOUT_SECONDS = 5;
    private const TIMEOUT_SECONDS = 10;

    public function __construct(
        private readonly PartnerUrlSafetyService $urlSafety
    ) {
    }

    /**
     * @return array{reachable:bool,backlink_found:bool,http_status:?int,error:?string,checked_url:string}
     */
    public function check(PartnerLink $partner): array
    {
        $url = trim((string) ($partner->backlink_url ?: $partner->website_url));

        if ($url === '') {
            return $this->failure('', null, 'missing_url');
        }

        try {
            return $this->requestAndInspect($url);
        } catch (\Throwable $exception) {
            return $this->failure($url, null, $exception->getMessage());
        }
    }

    /**
     * @return array{reachable:bool,backlink_found:bool,http_status:?int,error:?string,checked_url:string}
     */
    private function requestAndInspect(string $initialUrl): array
    {
        $currentUrl = $initialUrl;

        for ($redirect = 0; $redirect <= self::MAX_REDIRECTS; $redirect++) {
            $safe = $this->urlSafety->inspect($currentUrl);

            $options = [
                'allow_redirects' => false,
                'stream' => true,
            ];

            if (defined('CURLOPT_RESOLVE')) {
                $pinnedIp = str_contains($safe['ip'], ':')
                    ? '[' . $safe['ip'] . ']'
                    : $safe['ip'];

                $options['curl'] = [
                    CURLOPT_RESOLVE => [
                        sprintf('%s:%d:%s', $safe['host'], $safe['port'], $pinnedIp),
                    ],
                ];
            }

            $response = Http::withHeaders([
                'User-Agent' => 'Okulary-3D Partner Link Checker/1.0 (+https://okulary-3d.pl)',
                'Accept' => 'text/html,application/xhtml+xml;q=0.9,*/*;q=0.5',
            ])
                ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
                ->timeout(self::TIMEOUT_SECONDS)
                ->withOptions($options)
                ->get($currentUrl);

            $status = $response->status();

            if (in_array($status, [301, 302, 303, 307, 308], true)) {
                $location = trim((string) $response->header('Location'));

                if ($location === '') {
                    return $this->failure($currentUrl, $status, 'redirect_without_location');
                }

                if ($redirect === self::MAX_REDIRECTS) {
                    return $this->failure($currentUrl, $status, 'too_many_redirects');
                }

                $currentUrl = (string) UriResolver::resolve(
                    new Uri($currentUrl),
                    new Uri($location)
                );

                continue;
            }

            if ($status < 200 || $status >= 300) {
                return $this->failure($currentUrl, $status, 'http_' . $status);
            }

            $declaredLength = (int) ($response->header('Content-Length') ?: 0);
            if ($declaredLength > self::MAX_BODY_BYTES) {
                return $this->failure($currentUrl, $status, 'response_too_large');
            }

            $body = $this->readLimitedBody($response->toPsrResponse()->getBody());

            if ($body === null) {
                return $this->failure($currentUrl, $status, 'response_too_large');
            }

            return [
                'reachable' => true,
                'backlink_found' => $this->containsPortalBacklink($body, $currentUrl),
                'http_status' => $status,
                'error' => null,
                'checked_url' => $currentUrl,
            ];
        }

        return $this->failure($currentUrl, null, 'too_many_redirects');
    }

    private function readLimitedBody(\Psr\Http\Message\StreamInterface $stream): ?string
    {
        $body = '';

        while (! $stream->eof()) {
            $remaining = self::MAX_BODY_BYTES + 1 - strlen($body);

            if ($remaining <= 0) {
                return null;
            }

            $chunk = $stream->read(min(65_536, $remaining));

            if ($chunk === '') {
                break;
            }

            $body .= $chunk;

            if (strlen($body) > self::MAX_BODY_BYTES) {
                return null;
            }
        }

        return $body;
    }

    private function containsPortalBacklink(string $html, string $baseUrl): bool
    {
        $hrefs = [];

        if (class_exists(\DOMDocument::class)) {
            $previous = libxml_use_internal_errors(true);
            $dom = new \DOMDocument();
            $loaded = $dom->loadHTML(
                $html,
                LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR
            );
            libxml_clear_errors();
            libxml_use_internal_errors($previous);

            if ($loaded) {
                foreach ($dom->getElementsByTagName('a') as $anchor) {
                    if ($anchor->hasAttribute('href')) {
                        $hrefs[] = $anchor->getAttribute('href');
                    }
                }
            }
        } else {
            preg_match_all(
                '/<a\b[^>]*\bhref\s*=\s*(["\'])(.*?)\1/is',
                $html,
                $matches
            );
            $hrefs = $matches[2] ?? [];
        }

        foreach ($hrefs as $href) {
            if ($this->isPortalUrl((string) $href, $baseUrl)) {
                return true;
            }
        }

        return false;
    }

    private function isPortalUrl(string $href, string $baseUrl): bool
    {
        $href = trim(html_entity_decode($href, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if ($href === '' || str_starts_with($href, '#')) {
            return false;
        }

        try {
            $resolved = (string) UriResolver::resolve(
                new Uri($baseUrl),
                new Uri($href)
            );
        } catch (\Throwable) {
            return false;
        }

        $scheme = strtolower((string) parse_url($resolved, PHP_URL_SCHEME));
        $host = strtolower(trim((string) parse_url($resolved, PHP_URL_HOST), '.'));

        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            return false;
        }

        return $host === 'okulary-3d.pl'
            || $host === 'www.okulary-3d.pl'
            || str_ends_with($host, '.okulary-3d.pl');
    }

    /**
     * @return array{reachable:bool,backlink_found:bool,http_status:?int,error:?string,checked_url:string}
     */
    private function failure(string $url, ?int $status, string $error): array
    {
        return [
            'reachable' => false,
            'backlink_found' => false,
            'http_status' => $status,
            'error' => $error,
            'checked_url' => $url,
        ];
    }
}
