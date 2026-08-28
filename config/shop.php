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
                'prices' => [
                    'PLN' => 18.99,
                    'EUR' => 4.50,
                ],
                'requires_point' => false,
            ],

            'parcel_locker' => [
                'active' => true,
                'name' => [
                    'pl' => 'Paczkomat / punkt odbioru',
                    'en' => 'Parcel locker / pickup point',
                ],
                'prices' => [
                    'PLN' => 16.99,
                    'EUR' => 4.10,
                ],
                'requires_point' => true,
            ],

            'pickup' => [
                'active' => true,
                'name' => [
                    'pl' => 'Odbiór osobisty',
                    'en' => 'Local pickup',
                ],
                'prices' => [
                    'PLN' => 0.00,
                    'EUR' => 0.00,
                ],
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
            'currencies' => ['PLN', 'EUR'],
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
