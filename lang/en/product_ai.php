<?php

return [
    'actions' => [
        'seo_fill' => 'Auto-fill SEO',
        'translate' => 'Auto Translator',
        'edit' => 'Edit product',
        'delete' => 'Delete product',
    ],

    'tooltips' => [
        'seo_complete' => 'Auto-fill SEO — the SEO fields are already complete',
        'translate_needs_seo' => 'Auto Translator — complete the product SEO fields first',
        'translate_locked' => 'Auto Translator — the target version is Ready/Source and protected from overwrite',
    ],

    'messages' => [
        'seo_generated' => 'Product SEO fields were automatically completed by :provider / :model.',
    ],

    'errors' => [
        'not_configured' => 'AI Translator is not configured or is disabled.',
        'provider' => 'Unsupported AI provider.',
        'http' => 'The AI provider returned HTTP error :status.',
        'empty_response' => 'The AI provider did not return complete SEO fields.',
        'invalid_json' => 'The AI provider returned an invalid data structure.',
        'source_missing' => 'The product source-language version is missing.',
        'seo_complete' => 'This product already has complete SEO fields. Auto-fill does not overwrite manually prepared content.',
    ],
];
