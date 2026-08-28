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
        'media' => 'Media',
        'admins' => 'Administrators',
        'modules_kicker' => 'Modules',
        'modules' => 'Application management',
    ],

    'modules' => [
        'content' => ['title' => 'Content & articles', 'description' => 'Articles, categories, drafts and editorial publications.'],
        'media' => ['title' => 'Media library', 'description' => 'Central images, metadata, folders and reusable assets.'],
        'shop' => ['title' => 'Shop', 'description' => 'Products, categories, pricing, stock and future order handling.'],
        'users' => ['title' => 'Users', 'description' => 'Accounts, roles, permissions and access management.'],
        'translations' => ['title' => 'AI translations', 'description' => 'PL/EN language versions and future languages.'],
        'orchestrator' => ['title' => 'Orchestrator', 'description' => 'Discovery, publishing plans, generation and scheduling.'],
        'analytics' => ['title' => 'Analytics', 'description' => 'Traffic, publications, conversions and tool/shop effectiveness.'],
        'settings' => ['title' => 'System settings', 'description' => 'Critical configuration available only to Super Administrators.'],
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
