<?php

return [
    'admin' => [
        'menu' => 'Shipping',
        'kicker' => 'Shop logistics',
        'title' => 'Shipping and delivery rates',
        'description' => 'Shipping countries, methods, weight ranges, PLN base prices and logistics margin.',
        'back_to_products' => 'Products',

        'stats' => [
            'active_countries' => 'Active countries',
            'active_methods' => 'Active methods',
            'rates' => 'Rate rules',
            'missing_weight' => 'Variants without weight',
        ],

        'settings' => [
            'kicker' => 'Configuration',
            'title' => 'Shipping countries and methods',
            'description' => 'Poland is always the active default country. Other countries are manually enabled for customers.',
            'logistics_margin' => 'Logistics margin [%]',
            'logistics_margin_help' => 'Default 10.00%. Applied only to shipments outside Poland, before currency conversion and the currency conversion margin.',
            'countries' => 'Available shipping countries',
            'methods' => 'Active shipping methods',
            'pickup_point' => 'requires pickup point',
            'default_country' => 'default country — always active',
            'save' => 'Save shipping settings',
        ],

        'rates' => [
            'kicker' => 'Manual rate table',
            'title' => 'Shipping cost by country and weight',
            'description' => 'The base price is stored in PLN. Ranges for the same country + method pair cannot overlap.',
            'margin_note' => 'K87.1 stores PLN base prices. In K87.2 shipments outside Poland will automatically receive the :margin% logistics margin and then — when the customer uses another currency — NBP conversion plus the currency conversion margin.',
            'country' => 'Country',
            'method' => 'Method',
            'from_kg' => 'From [kg]',
            'to_kg' => 'To [kg]',
            'price_pln' => 'Price PLN',
            'active' => 'Active',
            'add' => 'Add rule',
            'save' => 'Save',
            'delete' => 'Delete',
            'delete_confirm' => 'Delete this shipping rate rule?',
            'empty' => 'There are no shipping rate rules yet.',
        ],

        'weights' => [
            'kicker' => 'Product weight',
            'title' => 'SKU variant weights',
            'description' => 'Weight is stored in grams and will be used by K87.2 to select the correct shipping rate range.',
            'warning' => ':count variants do not have a weight. Complete all online-sale variants before enabling dynamic checkout.',
            'product' => 'Product',
            'variant' => 'Variant',
            'weight' => 'Weight [g]',
            'placeholder' => 'e.g. 250',
            'save' => 'Save weights',
            'empty' => 'No product variants.',
        ],

        'next_step' => [
            'title' => 'K87.2 — dynamic checkout',
            'description' => 'This stage prepares administration data only. The current checkout still uses the existing shipping methods. K87.2 will connect customer country, cart weight, rate rules and logistics margin to checkout calculations.',
        ],

        'messages' => [
            'settings_saved' => 'Shipping settings saved.',
            'rate_created' => 'Shipping rate rule created.',
            'rate_updated' => 'Shipping rate rule saved.',
            'rate_deleted' => 'Shipping rate rule deleted.',
            'weights_saved' => 'Variant weights saved.',
        ],

        'validation' => [
            'overlap' => 'This weight range overlaps an existing rule for the same country and shipping method.',
            'variant_missing' => 'At least one product variant does not exist.',
        ],
    ],

    'checkout' => [
        'country' => 'Shipping country',
        'weight' => 'Order weight: :weight kg',
        'loading' => 'Calculating available shipping methods and cost…',
        'no_methods' => 'There is currently no shipping method available for the selected country and order weight.',
        'quote_error' => 'Shipping cost could not be calculated. Select the country again or try again shortly.',
        'weight_missing' => 'Shipping cannot be calculated because at least one product variant does not have a defined weight. Please contact the shop.',
        'method_unavailable' => 'The selected shipping method is not available for this country and order weight.',
        'same_address_country_mismatch' => 'When shipping and billing addresses are the same, the billing country and shipping country must match.',
    ],

    'admin_order' => [
        'snapshot' => 'Shipping calculation snapshot',
        'country' => 'Shipping country',
        'weight' => 'Shipment weight',
        'base_before_margin' => 'Base price before margin',
        'logistics_margin' => 'Logistics margin',
        'base_after_margin' => 'Base price after margin',
    ],
];
