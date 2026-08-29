<?php

return [
    'minimum_php' => '8.3.0',

    'analytics_retention_days' => 180,

    'production' => [
        'require_https_url' => true,
        'require_secure_session_cookie' => true,
        'require_non_sync_queue' => true,
        'disallowed_mailers' => [
            'array',
            'log',
        ],
    ],
];
