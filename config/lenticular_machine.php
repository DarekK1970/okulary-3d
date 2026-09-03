<?php

return [
    'disk' => env('LENTICULAR_STORAGE_DISK', 'local'),
    'signature_tolerance_seconds' => (int) env('LENTICULAR_SIGNATURE_TOLERANCE', 300),
    'transfer_url_minutes' => (int) env('LENTICULAR_TRANSFER_URL_MINUTES', 15),
    'maximum_lease_seconds' => (int) env('LENTICULAR_MAXIMUM_LEASE_SECONDS', 600),
];
