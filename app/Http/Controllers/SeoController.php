<?php

namespace App\Http\Controllers;

use App\Services\SitemapService;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function sitemap(
        SitemapService $sitemap
    ): Response {
        return response()
            ->view(
                'seo.sitemap',
                [
                    'entries' =>
                        $sitemap->entries(),
                    'defaultLocale' =>
                        config(
                            'locales.default',
                            'pl'
                        ),
                ]
            )
            ->header(
                'Content-Type',
                'application/xml; charset=UTF-8'
            );
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /*/account',
            'Disallow: /*/cart',
            'Disallow: /*/checkout',
            'Disallow: /*/login',
            'Disallow: /*/register',
            'Disallow: /*/forgot-password',
            'Disallow: /*/reset-password',
            'Disallow: /*/order/',
            'Disallow: /*/payment/',
            '',
            'Sitemap: ' . route('sitemap'),
        ];

        return response(
            implode("\n", $lines),
            200,
            [
                'Content-Type' =>
                    'text/plain; charset=UTF-8',
            ]
        );
    }
}
