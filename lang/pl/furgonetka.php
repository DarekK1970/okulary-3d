<?php

return [
    'settings' => [
        'kicker' => 'Integracja logistyczna',
        'title' => 'Furgonetka.pl',
        'description' => 'Integracja typu „Własne”: Furgonetka pobiera zamówienia bezpośrednio ze sklepu i może odesłać numer przesyłki.',
        'runtime' => 'Aktywacja integracji',
        'enabled' => 'Włącz integrację Furgonetka.pl',
        'enabled_help' => 'Po włączeniu endpoint /orders zaczyna akceptować żądania z poprawnym tokenem.',
        'save' => 'Zapisz ustawienia',
    ],

    'universal' => [
        'title' => 'Integracja E-commerce „Własne”',
        'description' => 'Nie wymaga OAuth2, Client ID ani Client Secret. Token generuje nasz sklep, a Furgonetka używa go w nagłówku Authorization.',
        'furgonetka_form' => 'W panelu Furgonetka.pl → Integracje → Własne wpisz:',
        'display_name' => 'Nazwa wyświetlana',
        'shop_url' => 'Adres sklepu',
        'token' => 'Token',
        'copy_token_below' => 'wklej token wygenerowany poniżej',
        'enable_order_sync' => 'Włącz synchronizację zamówień',
        'enable_tracking_callback' => 'Wysyłaj informacje o przesyłce',
        'orders_endpoint' => 'Endpoint pobierania zamówień',
        'tracking_endpoint' => 'Endpoint zwrotny numeru przesyłki',
        'token_help' => 'Token jest sekretem integracji. Jest przechowywany w bazie w postaci szyfrowanej. Po regeneracji trzeba wkleić nową wartość również w Furgonetka.pl.',
        'generate_token' => 'Wygeneruj token integracji',
        'regenerate_token' => 'Wygeneruj nowy token',
        'security_title' => 'Zasady bezpieczeństwa',
        'security_text' => 'Endpointy integracji nie korzystają z sesji ani CSRF. Dostęp jest możliwy wyłącznie przy aktywnej integracji i poprawnym tokenie porównywanym w sposób constant-time. Stare dane OAuth2 są usuwane z ustawień podczas zapisu lub generowania tokenu.',
    ],

    'map' => [
        'title' => 'Furgonetka Mapa',
        'key' => 'Klucz API Furgonetka Mapa',
        'key_placeholder' => 'Klucz API mapy',
        'help' => 'Mapa pozostaje osobnym komponentem checkoutu do wyboru punktów i automatów.',
        'key_help' => 'Klucz mapy działa w przeglądarce. Po stronie Furgonetka ogranicz go do domen sklepu.',
        'choose' => 'Wybierz punkt na mapie',
        'not_ready' => 'Mapa Furgonetka nie jest jeszcze gotowa. Spróbuj ponownie za chwilę.',
        'selected' => 'Wybrany punkt',
    ],

    'tracking' => [
        'title' => 'Przesyłka / tracking',
        'carrier' => 'Przewoźnik',
        'number' => 'Numer przesyłki',
        'updated_at' => 'Aktualizacja',
        'open' => 'Śledź przesyłkę',
    ],

    'messages' => [
        'settings_saved' => 'Ustawienia Furgonetka zostały zapisane.',
        'token_generated' => 'Wygenerowano nowy token integracji. Wklej go teraz w panelu Furgonetka.pl.',
    ],
];
