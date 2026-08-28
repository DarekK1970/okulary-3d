<?php

return [
    'enabled' => (bool) env('PAYNOW_ENABLED', false),
    'sandbox' => (bool) env('PAYNOW_SANDBOX', true),

    'api_key' => env('PAYNOW_API_KEY'),
    'signature_key' => env('PAYNOW_SIGNATURE_KEY'),

    'sandbox_url' => 'https://api.sandbox.paynow.pl',
    'production_url' => 'https://api.paynow.pl',

    'timeout' => (int) env('PAYNOW_TIMEOUT', 15),
];
