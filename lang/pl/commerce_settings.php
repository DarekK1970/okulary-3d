<?php

return [
    'kicker' => 'Konfiguracja sprzedaży',
    'title' => 'Ustawienia sklepu i płatności',
    'description' => 'Dane operatora płatności, przelewu i sprzedawcy są zapisywane w bazie. Sekrety PayNow są szyfrowane przez Laravel i nie trafiają do pliku .env.',

    'save' => 'Zapisz ustawienia',

    'secret_saved' => 'Zapisany klucz:',
    'secret_placeholder' => 'Pozostaw puste, aby zachować obecny klucz',
    'clear_secret' => 'Usuń zapisany klucz',

    'security' => [
        'title' => 'Bezpieczny zapis',
        'description' => 'Klucze API są przechowywane w zaszyfrowanej postaci w bazie danych z wykorzystaniem APP_KEY. W panelu nie wyświetlamy pełnej wartości sekretu.',
    ],

    'paynow' => [
        'title' => 'Ustawienia PayNow',
        'description' => 'Konfiguracja środowiska, kluczy API i webhooka płatności.',
        'ready' => 'Gotowe do użycia',
        'not_ready' => 'Nie skonfigurowano',
        'enabled' => 'Włącz PayNow w checkout',
        'sandbox' => 'Tryb Sandbox',
        'api_key' => 'API Key',
        'signature_key' => 'Signature Key',
        'timeout' => 'Timeout API [sekundy]',
        'notification_url' => 'Notification URL',
        'notification_help' => 'Ten adres należy ustawić jako URL powiadomień w panelu PayNow.',
    ],

    'bank' => [
        'kicker' => 'Przelew tradycyjny',
        'title' => 'Dane rachunku bankowego',
        'description' => 'Dane wyświetlane klientowi po wyborze przelewu tradycyjnego.',
        'recipient' => 'Odbiorca przelewu',
        'bank_name' => 'Nazwa banku',
        'account' => 'Numer rachunku',
    ],

    'seller' => [
        'kicker' => 'Sprzedawca',
        'title' => 'Dane sprzedawcy',
        'description' => 'Dane umieszczane na potwierdzeniu zamówienia.',
        'name' => 'Nazwa sprzedawcy',
        'tax_id' => 'NIP / numer podatkowy',
        'address' => 'Adres',
        'email' => 'E-mail',
    ],

    'messages' => [
        'saved' => 'Ustawienia sklepu zostały zapisane.',
    ],
];
