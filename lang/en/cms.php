<?php

return [
    'translation_statuses' => [
        'source' => 'Source',
        'draft' => 'Draft',
        'review' => 'Needs review',
        'ready' => 'Ready',
    ],

    'articles' => [
        'kicker' => 'Multilingual CMS',
        'title' => 'Articles',
        'description' => 'A single publication can contain independent PL and EN language versions.',
        'new' => 'New article',
        'create_title' => 'New article',
        'create_description' => 'Prepare source content, optional translation, SEO and publication date.',
        'edit_title' => 'Edit article',
        'empty' => 'There are no articles yet.',

        'statuses' => [
            'draft' => 'Draft',
            'scheduled' => 'Scheduled',
            'published' => 'Published',
        ],

        'filters' => [
            'search' => 'Search all language versions…',
            'all_statuses' => 'All statuses',
            'all_categories' => 'All categories',
            'apply' => 'Filter',
            'clear' => 'Clear',
        ],

        'table' => [
            'title' => 'Article',
            'category' => 'Category',
            'status' => 'Publication',
            'languages' => 'Languages',
            'publication' => 'Date',
            'actions' => 'Actions',
        ],

        'actions' => [
            'edit' => 'Edit',
            'preview' => 'Preview',
            'delete' => 'Delete',
            'delete_confirm' => 'Delete this article and all its translations?',
        ],

        'form' => [
            'languages' => 'Language versions',
            'localized_content' => 'Content and SEO per language',
            'source_language_badge' => 'Source language',
            'title' => 'Title',
            'slug' => 'URL slug',
            'slug_help' => 'Each language has an independent slug. Leave blank to generate automatically.',
            'excerpt' => 'Lead / short description',
            'body' => 'Article body',
            'seo_heading' => 'Search engine metadata',
            'seo_title' => 'SEO title',
            'seo_title_help' => 'Max 70 characters. The article title is used when empty.',
            'seo_description' => 'Meta description',
            'translation_status' => 'Translation status',
            'publication' => 'Publication',
            'category' => 'Category',
            'choose_category' => 'Choose category',
            'source_locale' => 'Source language',
            'source_locale_help' => 'The source version is always marked as Source and does not use the translation workflow.',
            'status' => 'Publication status',
            'published_at' => 'Publication date and time',
            'published_at_help' => 'Scheduled requires a future date. Published uses the current time when empty.',
            'hero' => 'Hero image',
            'hero_upload' => 'Choose file',
            'hero_help' => 'Shared by all languages. JPG, PNG or WEBP, max 5 MB.',
            'hero_preview' => 'Image preview',
            'remove_hero' => 'Remove current hero image',
            'save' => 'Save changes',
            'create' => 'Create article',
            'cancel' => 'Cancel',
        ],

        'validation' => [
            'translation_complete' => 'When adding a translation, both title and body are required.',
        ],

        'messages' => [
            'created' => 'Multilingual article created.',
            'updated' => 'Article and language versions saved.',
            'deleted' => 'Article and translations deleted.',
        ],
    ],
];
