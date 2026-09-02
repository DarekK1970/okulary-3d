<?php

return [
    'kicker' => 'System settings',
    'title' => 'Maintenance mode',
    'description' => 'Temporarily disable public access to the portal while keeping full preview access for selected IP addresses.',

    'section' => [
        'kicker' => 'Technical access',
        'title' => 'Maintenance mode',
        'description' => 'When enabled, public pages return HTTP 503. The administration panel, administrator login, health checks and technical payment endpoints remain available.',
    ],

    'status' => [
        'enabled' => 'Maintenance enabled',
        'disabled' => 'Public portal active',
    ],

    'form' => [
        'enabled' => 'Enable maintenance mode',
        'allowed_ips' => 'IP addresses allowed to preview the portal',
        'allowed_ips_help' => 'Enter one IPv4 or IPv6 address per line. Commas and semicolons are also accepted. Access is granted only to exact addresses listed here.',
        'save' => 'Save settings',
    ],

    'current_ip' => [
        'title' => 'Current IP address',
        'description' => 'Laravel identifies this connection as:',
    ],

    'safety' => [
        'title' => 'Safe maintenance access',
        'description' => 'The /admin area is never blocked by maintenance mode, so the setting can still be disabled even if the current IP address is not on the preview list.',
    ],

    'messages' => [
        'saved' => 'Maintenance mode settings have been saved.',
    ],

    'errors' => [
        'invalid_ips' => 'Invalid IP addresses: :ips',
        'ip_required_when_enabled' => 'Add at least one preview IP address before enabling maintenance mode.',
    ],

    'public' => [
        'kicker' => '3D Glasses Portal',
        'title' => 'Maintenance in progress',
        'description' => 'The portal is temporarily unavailable to the public while changes are being implemented and verified before reopening the service.',
        'retry' => 'Please check back shortly.',
        'current_ip' => 'IP address of this connection:',
        'admin' => 'Administration panel',
    ],
];
