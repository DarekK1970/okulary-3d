<?php

return [
    'switcher' => 'Currency',
    'apply' => 'Apply',
    'rate_note' => 'Prices converted to :currency using the :source rate dated :date.',
    'rate_note_markup' => 'Prices converted to :currency using the :source rate dated :date with a :markup% currency conversion margin.',
    'errors' => [
        'unavailable' => 'The selected currency is disabled or does not have an exchange rate yet.',
        'source_rate_missing' => 'The base exchange rate for source currency :currency is missing.',
        'invalid_rate' => 'The stored exchange rate is invalid.',
    ],
];
