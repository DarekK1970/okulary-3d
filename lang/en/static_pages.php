<?php

return [
    'admin' => [
        'menu' => 'Static pages',
        'kicker' => 'Fixed content',
        'title' => 'Static pages',
        'description' => 'Edit information pages and portal/shop terms. Every page supports all languages configured in the portal.',
        'edit_title' => 'Edit static page',
        'edit_description' => 'Content is edited separately for each language. The main content field uses a WYSIWYG editor.',
    ],
    'groups' => [
        'content' => 'Static pages',
        'shop' => 'Shop',
    ],

    'table' => [
        'page' => 'Page',
        'languages' => 'Languages',
        'updated' => 'Updated',
        'actions' => 'Actions',
    ],

    'actions' => [
        'edit' => 'Edit',
        'auto_translate' => 'Automatic translation',
        'preview' => 'Preview',
        'save' => 'Save changes',
        'cancel' => 'Cancel',
        'back' => 'Static pages',
    ],
    'editor' => [
        'languages' => 'Language versions',
        'content' => 'Page content',
        'source' => 'Source language',
        'title' => 'Title',
        'body' => 'Content — WYSIWYG',
        'seo' => 'Page metadata',
        'seo_title' => 'SEO title',
        'seo_description' => 'SEO description',
        'page_info' => 'Page information',
        'key' => 'System key',
        'group' => 'Section',
        'public_url' => 'Public URL',
    ],
    'messages' => [
        'saved' => 'Static page saved.',
        'translated' => 'Missing language versions added: :locales.',
        'no_missing_translations' => 'This page already has complete versions in all configured languages.',
    ],
    'errors' => [
        'source_missing' => 'The source-language version of this page is missing.',
        'source_title_required' => 'The source-language page title is required.',
        'translation_title_required' => 'When creating a language version, its title is required.',
    ],
    'public' => [
        'breadcrumbs' => 'Breadcrumb navigation',
        'home' => 'Home',
        'languages' => 'Languages:',
        'content_pending' => 'The content of this page is being prepared.',
        'back' => 'Back to home page',
    ],

    'footer' => [
        'portal_terms' => 'Portal terms',
        'editorial_policy' => 'Editorial team',
        'shop_terms' => 'Shop terms and conditions',
        'secure_payments' => 'Secure payments',
    ],
];
