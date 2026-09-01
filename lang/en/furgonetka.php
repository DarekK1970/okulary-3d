<?php

return [
    'settings' => [
        'kicker' => 'Logistics integration',
        'title' => 'Furgonetka.pl',
        'description' => '“Custom” Universal integration: Furgonetka pulls orders directly from the shop and can return shipment tracking data.',
        'runtime' => 'Integration activation',
        'enabled' => 'Enable Furgonetka.pl integration',
        'enabled_help' => 'When enabled, /orders accepts requests carrying the correct integration token.',
        'save' => 'Save settings',
    ],

    'universal' => [
        'title' => 'Universal E-commerce “Custom” integration',
        'description' => 'OAuth2, Client ID and Client Secret are not required. The shop generates the token and Furgonetka sends it in the Authorization header.',
        'furgonetka_form' => 'In Furgonetka.pl → Integrations → Custom enter:',
        'display_name' => 'Display name',
        'shop_url' => 'Shop URL',
        'token' => 'Token',
        'copy_token_below' => 'paste the token generated below',
        'enable_order_sync' => 'Enable order synchronization',
        'enable_tracking_callback' => 'Send shipment information',
        'orders_endpoint' => 'Order synchronization endpoint',
        'tracking_endpoint' => 'Shipment tracking callback endpoint',
        'token_help' => 'The token is an integration secret and is stored encrypted in the database. After regeneration, paste the new value into Furgonetka.pl as well.',
        'generate_token' => 'Generate integration token',
        'regenerate_token' => 'Generate a new token',
        'security_title' => 'Security',
        'security_text' => 'Integration endpoints do not use session or CSRF. Access requires an enabled integration and the correct token, compared in constant time. Legacy OAuth2 credentials are removed when settings are saved or a token is generated.',
    ],

    'map' => [
        'title' => 'Furgonetka Map',
        'key' => 'Furgonetka Map API key',
        'key_placeholder' => 'Map API key',
        'help' => 'The map remains a separate checkout component used to select pickup points and parcel lockers.',
        'key_help' => 'The map key is used in the browser. Restrict it at Furgonetka to the shop domains.',
        'choose' => 'Choose point on map',
        'not_ready' => 'Furgonetka Map is not ready yet. Try again shortly.',
        'selected' => 'Selected point',
    ],

    'tracking' => [
        'title' => 'Shipment / tracking',
        'carrier' => 'Carrier',
        'number' => 'Tracking number',
        'updated_at' => 'Updated',
        'open' => 'Track shipment',
    ],

    'messages' => [
        'settings_saved' => 'Furgonetka settings saved.',
        'token_generated' => 'A new integration token was generated. Paste it into Furgonetka.pl now.',
    ],
];
