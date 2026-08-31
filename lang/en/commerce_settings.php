<?php

return [
    'kicker' => 'Commerce configuration',
    'title' => 'Shop and payment settings',
    'description' => 'Payment provider, currency, bank transfer and seller data are stored in the database. PayNow secrets are encrypted by Laravel and are not written to .env.',

    'save' => 'Save settings',

    'secret_saved' => 'Stored key:',
    'secret_placeholder' => 'Leave blank to keep the current key',
    'clear_secret' => 'Delete stored key',

    'security' => [
        'title' => 'Secure storage',
        'description' => 'API keys are stored encrypted in the database using APP_KEY. The full secret value is never displayed in the admin panel.',
    ],

    'currencies' => [
        'kicker' => 'Currencies',
        'title' => 'Currencies and exchange rates',
        'description' => 'Shop currency configuration. Base product prices remain in PLN, while EUR, GBP and USD will be converted using stored exchange rates.',
        'base' => 'Base currency',
        'base_help' => 'The base currency is the source of truth for product prices and cannot be disabled.',
        'base_badge' => 'Base: :code',
        'default' => 'Default shop currency',
        'default_help' => 'Currency selected for a new customer session. The storefront switcher will be added in K86.4C.',
        'auto_update' => 'Fetch exchange rates automatically',
        'refresh_now' => 'Fetch rates now',
        'refresh_help' => 'Save the current settings and fetch the latest published NBP Table A.',
        'update_time' => 'Update time',
        'update_time_help' => 'Default 06:00. The NBP schedule will be activated in K86.4B.',
        'provider' => 'Automatic rate source',
        'provider_help' => 'EUR, GBP and USD rates will be retrieved from the National Bank of Poland API.',
        'markup' => 'Currency conversion margin',
        'markup_help' => 'Default 5.00%. The margin offsets bank and currency-conversion costs for payments in currencies other than PLN. It is never applied to PLN.',
        'base_source' => 'BASE',
        'manual' => 'manual',
        'stage_note_title' => 'K86.4B — automatic NBP rates',
        'stage_note' => 'Automatic updates use the latest published NBP Table A. On weekends, holidays or before a new table is published, the exchange-rate date can be earlier than the fetch date. Storefront selection and price conversion will be added in K86.4C.',
        'table' => [
            'enabled' => 'Enabled',
            'currency' => 'Currency',
            'symbol' => 'Symbol',
            'rate' => '1 unit = PLN',
            'source' => 'Source',
            'date' => 'Rate date',
        ],
        'messages' => [
            'updated' => 'Fetched :count NBP rate(s). Table date: :date.',
            'no_foreign' => 'There are no enabled foreign currencies to update.',
        ],
        'errors' => [
            'default_disabled' => 'The default shop currency must also be enabled.',
            'refresh_failed' => 'Could not fetch NBP exchange rates. Existing rates were left unchanged.',
            'invalid_nbp_response' => 'NBP returned an invalid Table A response.',
            'missing_nbp_rates' => 'The NBP response is missing rates for: :codes.',
            'nbp_http' => 'NBP API returned HTTP error :status.',
        ],
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
        'foreign_title' => 'PayNow foreign-currency payments',
        'foreign_description' => 'PayNow API supports EUR, GBP and USD for card payments. Enable a currency only after PayNow confirms the service is active for your payment point.',
        'card_only' => 'card payments only',
        'foreign_warning_title' => 'PayNow-side activation required',
        'foreign_warning' => 'Selecting a currency here does not activate the service at the payment provider. EUR, GBP and USD require PayNow to enable foreign-currency card processing for your merchant account. Keep these options disabled until PayNow confirms activation.',
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
