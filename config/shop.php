<?php

return [
    'shipping' => [
        'methods' => [
            'courier' => [
                'active' => true,
                'name' => [
                    'pl' => 'Kurier',
                    'en' => 'Courier',
                ],
                'base_price_pln' => 18.99,
                'requires_point' => false,
            ],

            'parcel_locker' => [
                'active' => true,
                'name' => [
                    'pl' => 'Paczkomat / punkt odbioru',
                    'en' => 'Parcel locker / pickup point',
                ],
                'base_price_pln' => 16.99,
                'requires_point' => true,
            ],

            'pickup' => [
                'active' => true,
                'name' => [
                    'pl' => 'Odbiór osobisty',
                    'en' => 'Local pickup',
                ],
                'base_price_pln' => 0.00,
                'requires_point' => false,
            ],
        ],
    ],

    'payments' => [
        'bank_transfer' => [
            'active' => true,
            'name' => [
                'pl' => 'Przelew tradycyjny',
                'en' => 'Bank transfer',
            ],
            'currencies' => [
                'PLN',
                'EUR',
                'GBP',
                'USD',
            ],
        ],

        'paynow' => [
            'active' => true,
            'name' => [
                'pl' => 'PayNow — szybka płatność online',
                'en' => 'PayNow — online payment',
            ],
            'currencies' => ['PLN'],
        ],
    ],
];
