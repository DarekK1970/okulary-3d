<?php

return [
    'title' => 'Administration — 3D Glasses Portal',
    'panel' => 'Backend',
    'navigation' => 'Administration navigation',
    'account' => 'My account',
    'logout' => 'Sign out',
    'back_to_portal' => 'Back to portal',
    'open' => 'Open module',
    'no_permission' => 'No permission',
    'validation_error' => 'The form could not be saved.',

    'menu' => [
        'dashboard' => 'Dashboard',
        'content' => 'Content',
        'articles' => 'Articles',
        'categories' => 'Article categories',
        'shop' => 'Shop',
        'users' => 'Users',
        'translations' => 'AI Translations',
        'orchestrator' => 'Orchestrator',
        'analytics' => 'Analytics',
        'settings' => 'Settings',
    ],

    'dashboard' => [
        'title' => 'Dashboard',
        'eyebrow' => '3D Glasses Portal',
        'welcome' => 'Welcome, :name',
        'description' => 'Central administration panel for portal content, shop, translations and publishing automation.',
        'your_role' => 'Your role',
        'stats' => 'System statistics',
        'users' => 'Users',
        'articles' => 'Articles',
        'published' => 'Published',
        'admins' => 'Administrators',
        'modules_kicker' => 'Modules',
        'modules' => 'Application management',
    ],

    'modules' => [
        'content' => ['title' => 'Content & articles', 'description' => 'Articles, categories, drafts and editorial publications.'],
        'shop' => ['title' => 'Shop', 'description' => 'Products, categories, pricing, stock and future order handling.'],
        'users' => ['title' => 'Users', 'description' => 'Accounts, roles, permissions and access management.'],
        'translations' => ['title' => 'AI translations', 'description' => 'PL/EN language versions and future languages.'],
        'orchestrator' => ['title' => 'Orchestrator', 'description' => 'Discovery, publishing plans, generation and scheduling.'],
        'analytics' => ['title' => 'Analytics', 'description' => 'Traffic, publications, conversions and tool/shop effectiveness.'],
        'settings' => ['title' => 'System settings', 'description' => 'Critical configuration available only to Super Administrators.'],
    ],

    'articles' => [
        'kicker' => 'CMS',
        'title' => 'Articles',
        'description' => 'Create, edit and schedule portal publications.',
        'new' => 'New article',
        'create_title' => 'New article',
        'create_description' => 'Prepare content, hero image and publication time.',
        'edit_title' => 'Edit article',
        'empty' => 'There are no articles yet.',

        'statuses' => [
            'draft' => 'Draft',
            'scheduled' => 'Scheduled',
            'published' => 'Published',
        ],

        'filters' => [
            'search' => 'Search title, slug or excerpt…',
            'all_statuses' => 'All statuses',
            'all_categories' => 'All categories',
            'apply' => 'Filter',
            'clear' => 'Clear',
        ],

        'table' => [
            'title' => 'Article',
            'category' => 'Category',
            'status' => 'Status',
            'publication' => 'Publication',
            'author' => 'Author',
            'actions' => 'Actions',
        ],

        'actions' => [
            'edit' => 'Edit',
            'delete' => 'Delete',
            'delete_confirm' => 'Delete this article?',
        ],

        'form' => [
            'title' => 'Title',
            'slug' => 'URL slug',
            'slug_help' => 'Leave blank to generate it automatically.',
            'excerpt' => 'Lead / short description',
            'body' => 'Article body',
            'editor_toolbar' => 'Text formatting toolbar',
            'publication' => 'Publication',
            'category' => 'Category',
            'choose_category' => 'Choose category',
            'status' => 'Status',
            'published_at' => 'Publication date and time',
            'published_at_help' => 'Scheduled articles require a future time. Published articles use the current time when left blank.',
            'hero' => 'Hero image',
            'hero_upload' => 'Choose file',
            'hero_help' => 'JPG, PNG or WEBP, up to 5 MB.',
            'hero_preview' => 'Hero image preview',
            'remove_hero' => 'Remove current hero image',
            'save' => 'Save changes',
            'create' => 'Create article',
            'cancel' => 'Cancel',
        ],

        'messages' => [
            'created' => 'Article created.',
            'updated' => 'Article saved.',
            'deleted' => 'Article deleted.',
        ],
    ],

    'categories' => [
        'kicker' => 'CMS',
        'title' => 'Article categories',
        'description' => 'Manage the publication topic structure.',
        'new' => 'New category',
        'list' => 'Existing categories',
        'empty' => 'There are no categories yet.',
        'articles_short' => 'articles',
        'delete' => 'Delete category',
        'delete_confirm' => 'Delete this category?',

        'form' => [
            'name' => 'Name',
            'slug' => 'Slug',
            'description' => 'Description',
            'order' => 'Order',
            'active' => 'Category active',
            'add' => 'Add category',
            'save' => 'Save category',
        ],

        'messages' => [
            'created' => 'Category added.',
            'updated' => 'Category saved.',
            'deleted' => 'Category deleted.',
            'in_use' => 'The category cannot be deleted because it contains articles.',
        ],
    ],

    'sections' => [
        'shop' => ['kicker' => 'E-commerce', 'title' => 'Shop', 'description' => 'The shop module will be implemented in later project steps.'],
        'users' => ['kicker' => 'RBAC', 'title' => 'Users & roles', 'description' => 'This section is available to Administrators and Super Administrators.'],
        'translations' => ['kicker' => 'AI', 'title' => 'AI translations', 'description' => 'Multilingual content and automated translations will be implemented later.'],
        'orchestrator' => ['kicker' => 'Automation', 'title' => 'Orchestrator', 'description' => 'Automated discovery, planning and publishing will be implemented later.'],
        'analytics' => ['kicker' => 'Data', 'title' => 'Analytics', 'description' => 'Analytics will be connected after the main application modules are available.'],
        'settings' => ['kicker' => 'Super Administrator', 'title' => 'System settings', 'description' => 'This section is reserved for the highest permission level.'],
    ],

    'placeholder' => [
        'title' => 'Module ready for implementation',
        'description' => 'Routing, RBAC access and the administration shell are ready. The module functionality will be implemented in its planned project step.',
        'back' => 'Back to dashboard',
    ],
];
