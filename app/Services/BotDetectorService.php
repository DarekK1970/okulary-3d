<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BotDetectorService
{
    public const CATEGORY_SEARCH =
        'search_engine';

    public const CATEGORY_AI =
        'ai';

    public const CATEGORY_SEO =
        'seo';

    public const CATEGORY_SOCIAL =
        'social_preview';

    public const CATEGORY_MONITORING =
        'monitoring';

    public const CATEGORY_OTHER =
        'other';

    /**
     * Ordered from the most specific signatures to generic ones.
     *
     * @var list<array{
     *     name: string,
     *     category: string,
     *     needles: list<string>
     * }>
     */
    private const SIGNATURES = [
        // AI / generative search
        [
            'name' => 'OAI-SearchBot',
            'category' => self::CATEGORY_AI,
            'needles' => [
                'oai-searchbot',
            ],
        ],
        [
            'name' => 'ChatGPT-User',
            'category' => self::CATEGORY_AI,
            'needles' => [
                'chatgpt-user',
            ],
        ],
        [
            'name' => 'GPTBot',
            'category' => self::CATEGORY_AI,
            'needles' => [
                'gptbot',
            ],
        ],
        [
            'name' => 'PerplexityBot',
            'category' => self::CATEGORY_AI,
            'needles' => [
                'perplexitybot',
                'perplexity-user',
            ],
        ],
        [
            'name' => 'ClaudeBot',
            'category' => self::CATEGORY_AI,
            'needles' => [
                'claudebot',
                'claude-web',
                'claude-user',
                'anthropic-ai',
            ],
        ],
        [
            'name' => 'CohereBot',
            'category' => self::CATEGORY_AI,
            'needles' => [
                'cohere-ai',
                'coherebot',
            ],
        ],
        [
            'name' => 'Meta-ExternalAgent',
            'category' => self::CATEGORY_AI,
            'needles' => [
                'meta-externalagent',
                'meta-externalfetcher',
            ],
        ],
        [
            'name' => 'Applebot-Extended',
            'category' => self::CATEGORY_AI,
            'needles' => [
                'applebot-extended',
            ],
        ],
        [
            'name' => 'Amazonbot',
            'category' => self::CATEGORY_AI,
            'needles' => [
                'amazonbot',
            ],
        ],
        [
            'name' => 'Bytespider',
            'category' => self::CATEGORY_AI,
            'needles' => [
                'bytespider',
            ],
        ],
        [
            'name' => 'CCBot',
            'category' => self::CATEGORY_AI,
            'needles' => [
                'ccbot',
            ],
        ],
        [
            'name' => 'YouBot',
            'category' => self::CATEGORY_AI,
            'needles' => [
                'youbot',
            ],
        ],
        [
            'name' => 'Google-Extended',
            'category' => self::CATEGORY_AI,
            'needles' => [
                'google-extended',
            ],
        ],

        // Search engines
        [
            'name' => 'Googlebot',
            'category' => self::CATEGORY_SEARCH,
            'needles' => [
                'googlebot',
                'googleother',
                'google-inspectiontool',
            ],
        ],
        [
            'name' => 'Bingbot',
            'category' => self::CATEGORY_SEARCH,
            'needles' => [
                'bingbot',
                'bingpreview',
                'adidxbot',
            ],
        ],
        [
            'name' => 'DuckDuckBot',
            'category' => self::CATEGORY_SEARCH,
            'needles' => [
                'duckduckbot',
            ],
        ],
        [
            'name' => 'Applebot',
            'category' => self::CATEGORY_SEARCH,
            'needles' => [
                'applebot',
            ],
        ],
        [
            'name' => 'YandexBot',
            'category' => self::CATEGORY_SEARCH,
            'needles' => [
                'yandexbot',
                'yandeximages',
            ],
        ],
        [
            'name' => 'Baiduspider',
            'category' => self::CATEGORY_SEARCH,
            'needles' => [
                'baiduspider',
            ],
        ],
        [
            'name' => 'Yahoo Slurp',
            'category' => self::CATEGORY_SEARCH,
            'needles' => [
                'yahoo! slurp',
                'slurp',
            ],
        ],
        [
            'name' => 'PetalBot',
            'category' => self::CATEGORY_SEARCH,
            'needles' => [
                'petalbot',
            ],
        ],
        [
            'name' => 'Qwantify',
            'category' => self::CATEGORY_SEARCH,
            'needles' => [
                'qwantify',
            ],
        ],
        [
            'name' => 'Sogou',
            'category' => self::CATEGORY_SEARCH,
            'needles' => [
                'sogou',
            ],
        ],
        [
            'name' => 'SeznamBot',
            'category' => self::CATEGORY_SEARCH,
            'needles' => [
                'seznambot',
            ],
        ],

        // SEO / indexing tools
        [
            'name' => 'SerpstatBot',
            'category' => self::CATEGORY_SEO,
            'needles' => [
                'serpstatbot',
            ],
        ],
        [
            'name' => 'AhrefsBot',
            'category' => self::CATEGORY_SEO,
            'needles' => [
                'ahrefsbot',
            ],
        ],
        [
            'name' => 'SemrushBot',
            'category' => self::CATEGORY_SEO,
            'needles' => [
                'semrushbot',
                'semrushbot-si',
                'semrushbot-ba',
            ],
        ],
        [
            'name' => 'MJ12bot',
            'category' => self::CATEGORY_SEO,
            'needles' => [
                'mj12bot',
            ],
        ],
        [
            'name' => 'DotBot',
            'category' => self::CATEGORY_SEO,
            'needles' => [
                'dotbot',
            ],
        ],
        [
            'name' => 'BLEXBot',
            'category' => self::CATEGORY_SEO,
            'needles' => [
                'blexbot',
            ],
        ],
        [
            'name' => 'DataForSeoBot',
            'category' => self::CATEGORY_SEO,
            'needles' => [
                'dataforseobot',
                'dataforseo',
            ],
        ],
        [
            'name' => 'SeobilityBot',
            'category' => self::CATEGORY_SEO,
            'needles' => [
                'seobilitybot',
            ],
        ],
        [
            'name' => 'Screaming Frog',
            'category' => self::CATEGORY_SEO,
            'needles' => [
                'screaming frog seo spider',
                'screaming frog',
            ],
        ],
        [
            'name' => 'SiteAuditBot',
            'category' => self::CATEGORY_SEO,
            'needles' => [
                'siteauditbot',
            ],
        ],
        [
            'name' => 'Rogerbot',
            'category' => self::CATEGORY_SEO,
            'needles' => [
                'rogerbot',
            ],
        ],
        [
            'name' => 'MegaIndex',
            'category' => self::CATEGORY_SEO,
            'needles' => [
                'megaindex',
            ],
        ],

        // Social link previews
        [
            'name' => 'FacebookExternalHit',
            'category' => self::CATEGORY_SOCIAL,
            'needles' => [
                'facebookexternalhit',
                'facebot',
            ],
        ],
        [
            'name' => 'LinkedInBot',
            'category' => self::CATEGORY_SOCIAL,
            'needles' => [
                'linkedinbot',
            ],
        ],
        [
            'name' => 'Twitterbot',
            'category' => self::CATEGORY_SOCIAL,
            'needles' => [
                'twitterbot',
            ],
        ],
        [
            'name' => 'PinterestBot',
            'category' => self::CATEGORY_SOCIAL,
            'needles' => [
                'pinterestbot',
            ],
        ],
        [
            'name' => 'TelegramBot',
            'category' => self::CATEGORY_SOCIAL,
            'needles' => [
                'telegrambot',
            ],
        ],
        [
            'name' => 'WhatsApp',
            'category' => self::CATEGORY_SOCIAL,
            'needles' => [
                'whatsapp',
            ],
        ],
        [
            'name' => 'Discordbot',
            'category' => self::CATEGORY_SOCIAL,
            'needles' => [
                'discordbot',
            ],
        ],
        [
            'name' => 'Slackbot',
            'category' => self::CATEGORY_SOCIAL,
            'needles' => [
                'slackbot',
            ],
        ],

        // Monitoring / synthetic browsing
        [
            'name' => 'Google Lighthouse',
            'category' => self::CATEGORY_MONITORING,
            'needles' => [
                'lighthouse',
                'pagespeed',
            ],
        ],
        [
            'name' => 'UptimeRobot',
            'category' => self::CATEGORY_MONITORING,
            'needles' => [
                'uptimerobot',
            ],
        ],
        [
            'name' => 'Pingdom',
            'category' => self::CATEGORY_MONITORING,
            'needles' => [
                'pingdom',
            ],
        ],
        [
            'name' => 'StatusCake',
            'category' => self::CATEGORY_MONITORING,
            'needles' => [
                'statuscake',
            ],
        ],
        [
            'name' => 'GTmetrix',
            'category' => self::CATEGORY_MONITORING,
            'needles' => [
                'gtmetrix',
            ],
        ],
        [
            'name' => 'Site24x7',
            'category' => self::CATEGORY_MONITORING,
            'needles' => [
                'site24x7',
            ],
        ],
        [
            'name' => 'Checkly',
            'category' => self::CATEGORY_MONITORING,
            'needles' => [
                'checkly',
            ],
        ],
        [
            'name' => 'HeadlessChrome',
            'category' => self::CATEGORY_MONITORING,
            'needles' => [
                'headlesschrome',
            ],
        ],

        // Other known automated agents
        [
            'name' => 'AwarioBot',
            'category' => self::CATEGORY_OTHER,
            'needles' => [
                'awariobot',
            ],
        ],
        [
            'name' => 'Mediapartners-Google',
            'category' => self::CATEGORY_OTHER,
            'needles' => [
                'mediapartners-google',
            ],
        ],
        [
            'name' => 'AdsBot-Google',
            'category' => self::CATEGORY_OTHER,
            'needles' => [
                'adsbot-google',
            ],
        ],
    ];

    /**
     * @return array{
     *     name: string,
     *     category: string,
     *     user_agent_hash: string|null
     * }|null
     */
    public function detect(
        Request|string|null $requestOrAgent
    ): ?array {
        $agent =
            $requestOrAgent
                instanceof Request
                ? $requestOrAgent
                    ->userAgent()
                : $requestOrAgent;

        $agent = trim(
            (string) $agent
        );

        if ($agent === '') {
            return null;
        }

        $normalized =
            Str::lower($agent);

        foreach (
            self::SIGNATURES
            as $signature
        ) {
            foreach (
                $signature['needles']
                as $needle
            ) {
                if (
                    str_contains(
                        $normalized,
                        $needle
                    )
                ) {
                    return [
                        'name' =>
                            $signature['name'],
                        'category' =>
                            $signature[
                                'category'
                            ],
                        'user_agent_hash' =>
                            hash(
                                'sha256',
                                $agent
                            ),
                    ];
                }
            }
        }

        /*
         * Generic fallback catches automation not present in
         * the named catalogue. This deliberately includes common
         * HTTP libraries when they request normal web pages.
         */
        if (
            preg_match(
                '/(?:bot\b|crawler|crawl\b|spider|scraper|slurp|headless|'
                . 'python-requests|python-urllib|aiohttp|go-http-client|'
                . 'libwww-perl|wget\/|curl\/|java\/|okhttp|'
                . 'postmanruntime|httpclient|node-fetch|axios\/)/i',
                $agent
            ) === 1
        ) {
            return [
                'name' =>
                    $this->genericName(
                        $agent
                    ),
                'category' =>
                    self::CATEGORY_OTHER,
                'user_agent_hash' =>
                    hash(
                        'sha256',
                        $agent
                    ),
            ];
        }

        return null;
    }

    private function genericName(
        string $agent
    ): string {
        if (
            preg_match(
                '/([A-Za-z0-9._-]*(?:bot|crawler|spider|scraper)[A-Za-z0-9._-]*)/i',
                $agent,
                $matches
            ) === 1
            && filled(
                $matches[1]
            )
        ) {
            return Str::limit(
                $matches[1],
                120,
                ''
            );
        }

        $firstToken =
            preg_split(
                '/[\s\/;(]+/',
                $agent
            )[0] ?? 'Automated client';

        $firstToken = trim(
            (string) $firstToken
        );

        return Str::limit(
            $firstToken !== ''
                ? $firstToken
                : 'Automated client',
            120,
            ''
        );
    }
}
