<?php

namespace App\Services;

class ArticleHtmlSanitizer
{
    public function sanitize(?string $html): string
    {
        $html = (string) $html;

        $html = preg_replace(
            '#<(script|style|iframe|object|embed)[^>]*>.*?</\1>#is',
            '',
            $html
        ) ?? '';

        $html = strip_tags(
            $html,
            '<p><br><h2><h3><strong><b><em><i><ul><ol><li><blockquote><a>'
        );

        $html = preg_replace(
            '/\s(?:on[a-z]+|style|class|id)\s*=\s*(["\']).*?\1/isu',
            '',
            $html
        ) ?? $html;

        $html = preg_replace(
            '/href\s*=\s*(["\'])\s*(?:javascript:|data:).*?\1/isu',
            'href="#"',
            $html
        ) ?? $html;

        return trim($html);
    }
}
