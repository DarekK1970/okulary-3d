<?php

return [
    'kicker' => 'Konfiguracja sprzedaży',
    'title' => 'Ustawienia sklepu i płatności',
    'description' => 'Dane operatora płatności, walut, przelewu i sprzedawcy są zapisywane w bazie. Sekrety PayNow są szyfrowane przez Laravel i nie trafiają do pliku .env.',

    'save' => 'Zapisz ustawienia',

    'secret_saved' => 'Zapisany klucz:',
    'secret_placeholder' => 'Pozostaw puste, aby zachować obecny klucz',
    'clear_secret' => 'Usuń zapisany klucz',

    'security' => [
        'title' => 'Bezpieczny zapis',
        'description' => 'Klucze API są przechowywane w zaszyfrowanej postaci w bazie danych z wykorzystaniem APP_KEY. W panelu nie wyświetlamy pełnej wartości sekretu.',
    ],

    'currencies' => [
        'kicker' => 'Waluty',
        'title' => 'Waluty i kursy walut',
        'description' => 'Konfiguracja walut sklepu. Ceny bazowe pozostają w PLN, a EUR, GBP i USD będą przeliczane według zapisanych kursów.',
        'base' => 'Waluta bazowa',
        'base_help' => 'Waluta bazowa jest źródłem prawdy dla cen produktów i nie może zostać wyłączona.',
        'base_badge' => 'Baza: :code',
        'default' => 'Domyślna waluta sklepu',
        'default_help' => 'Waluta wybierana dla nowej sesji klienta. Użytkownik będzie mógł zmienić ją na frontendzie w K86.4C.',
        'auto_update' => 'Pobieraj kursy walut automatycznie',
        'refresh_now' => 'Pobierz kursy teraz',
        'refresh_help' => 'Zapisz bieżące ustawienia i pobierz najnowszą opublikowaną tabelę A NBP.',
        'update_time' => 'Godzina aktualizacji',
        'update_time_help' => 'Domyślnie 06:00. Harmonogram NBP zostanie aktywowany w K86.4B.',
        'provider' => 'Źródło automatycznych kursów',
        'provider_help' => 'Kursy EUR, GBP i USD będą pobierane z API Narodowego Banku Polskiego.',
        'markup' => 'Marża przewalutowania',
        'markup_help' => 'Domyślnie 5,00%. Marża kompensuje koszty bankowe i przewalutowanie płatności w walutach innych niż PLN. Dla PLN nie jest naliczana.',
        'base_source' => 'BAZOWA',
        'manual' => 'ręczny',
        'stage_note_title' => 'K86.4B — automatyczne kursy NBP',
        'stage_note' => 'Automatyczna aktualizacja korzysta z najnowszej opublikowanej tabeli A NBP. W weekendy, święta lub przed publikacją nowej tabeli data kursu może być wcześniejsza niż data pobrania. Selektor waluty i przeliczanie cen wdrożymy w K86.4C.',
        'table' => [
            'enabled' => 'Aktywna',
            'currency' => 'Waluta',
            'symbol' => 'Symbol',
            'rate' => '1 jednostka = PLN',
            'source' => 'Źródło',
            'date' => 'Data kursu',
        ],
        'messages' => [
            'updated' => 'Pobrano :count kurs(y) z NBP. Data tabeli: :date.',
            'no_foreign' => 'Brak aktywnych walut obcych do aktualizacji.',
        ],
        'errors' => [
            'default_disabled' => 'Domyślna waluta sklepu musi być jednocześnie walutą aktywną.',
            'refresh_failed' => 'Nie udało się pobrać kursów z NBP. Dotychczasowe kursy pozostały bez zmian.',
            'invalid_nbp_response' => 'NBP zwrócił nieprawidłową odpowiedź dla tabeli A.',
            'missing_nbp_rates' => 'W odpowiedzi NBP brakuje kursów dla walut: :codes.',
            'nbp_http' => 'NBP API zwróciło błąd HTTP :status.',
        ],
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
        'foreign_title' => 'Płatności PayNow w walutach obcych',
        'foreign_description' => 'PayNow API obsługuje EUR, GBP i USD dla płatności kartowych. Włącz daną walutę dopiero po potwierdzeniu aktywacji usługi dla Twojego punktu płatności.',
        'card_only' => 'tylko płatność kartą',
        'foreign_warning_title' => 'Wymagana aktywacja po stronie PayNow',
        'foreign_warning' => 'Samo zaznaczenie waluty w tym panelu nie uruchamia usługi u operatora. Dla EUR, GBP i USD PayNow wymaga aktywacji obsługi płatności kartowych w walucie obcej. Do czasu potwierdzenia przez PayNow pozostaw checkboxy wyłączone.',
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
