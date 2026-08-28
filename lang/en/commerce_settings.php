<?php

return [
    'kicker' => 'Commerce configuration',
    'title' => 'Shop and payment settings',
    'description' => 'Payment provider, bank transfer and seller data are stored in the database. PayNow secrets are encrypted by Laravel and are not written to .env.',

    'save' => 'Save settings',

    'secret_saved' => 'Stored key:',
    'secret_placeholder' => 'Leave blank to keep the current key',
    'clear_secret' => 'Delete stored key',

    'security' => [
        'title' => 'Secure storage',
        'description' => 'API keys are stored encrypted in the database using APP_KEY. The full secret value is never displayed in the admin panel.',
    ],

    'paynow' => [
        'title' => 'PayNow settings',
        'description' => 'Environment, API key and payment notification configuration.',
        'ready' => 'Ready to use',
        'not_ready' => 'Not configured',
        'enabled' => 'Enable PayNow at checkout',
        'sandbox' => 'Sandbox mode',
        'api_key' => 'API Key',
        'signature_key' => 'Signature Key',
        'timeout' => 'API timeout [seconds]',
        'notification_url' => 'Notification URL',
        'notification_help' => 'Configure this address as the notification URL in the PayNow panel.',
    ],

    'bank' => [
        'kicker' => 'Bank transfer',
        'title' => 'Bank account details',
        'description' => 'Details displayed after a customer selects bank transfer.',
        'recipient' => 'Transfer recipient',
        'bank_name' => 'Bank name',
        'account' => 'Account number',
    ],

    'seller' => [
        'kicker' => 'Seller',
        'title' => 'Seller details',
        'description' => 'Details printed on the order confirmation.',
        'name' => 'Seller name',
        'tax_id' => 'Tax ID',
        'address' => 'Address',
        'email' => 'Email',
    ],

    'messages' => [
        'saved' => 'Shop settings saved.',
    ],
];
