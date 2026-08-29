<?php

return [
    'ranges' => [
        'today' => 'Dzisiaj',
        'days_7' => '7 dni',
        'days_30' => '30 dni',
    ],

    'metrics' => [
        'pageviews' => 'Odsłony',
        'sessions' => 'Sesje',
        'anonymous_sessions' => 'anonimowe sesje',
        'pages_per_session' => 'Odsłony / sesję',
        'average' => 'średnio',
        'active_sessions' => 'Aktywne teraz',
        'last_5_minutes' => 'ostatnie 5 minut',
        'events' => 'Zdarzenia',
        'interactions' => 'interakcje użytkowników',
        'lab_actions' => 'Akcje LAB',
        'recommendation_clicks' => 'Kliknięcia rekomendacji',
    ],

    'page_types' => [
        'home' => 'Strona główna',
        'article' => 'Artykuły',
        'shop' => 'Sklep — lista',
        'product' => 'Produkty',
        'lab' => '3D LAB',
        'archive' => 'Archiwum',
        'gallery' => 'Galeria',
        'cart' => 'Koszyk',
        'checkout' => 'Checkout',
        'other' => 'Pozostałe',
    ],

    'sources' => [
        'direct' => 'Wejścia bezpośrednie',
        'campaign' => 'Kampanie UTM',
        'search' => 'Wyszukiwarki',
        'social' => 'Social media',
        'referral' => 'Odesłania',
        'internal' => 'Ruch wewnętrzny',
        'other' => 'Pozostałe',
    ],

    'devices' => [
        'desktop' => 'Desktop',
        'mobile' => 'Telefon',
        'tablet' => 'Tablet',
        'other' => 'Inne',
    ],

    'events' => [
        'lab_action' => 'Akcja w 3D LAB',
        'recommendation_click' => 'Kliknięcie rekomendacji',
        'add_to_cart' => 'Dodanie do koszyka',
        'checkout_submit' => 'Wysłanie checkoutu',
        'gallery_mode' => 'Zmiana trybu galerii',
        'archive_view_mode' => 'Zmiana trybu archiwum',
        'newsletter_subscribe' => 'Próba zapisu do newslettera',
    ],

    'funnel' => [
        'product_views' => 'Wyświetlenia produktu',
        'add_to_cart' => 'Dodania do koszyka',
        'cart_views' => 'Wejścia do koszyka',
        'checkout_views' => 'Wejścia do checkoutu',
        'checkout_submit' => 'Wysłania zamówienia',
    ],

    'table' => [
        'time' => 'Czas',
        'event' => 'Zdarzenie',
        'category' => 'Kategoria',
        'label' => 'Etykieta',
        'page' => 'Strona',
        'language' => 'Język',
    ],

    'admin' => [
        'kicker' => 'Analityka własna / first-party',
        'title' => 'Portal Analytics',
        'description' => 'Ruch, popularność treści, źródła odwiedzin, 3D LAB, rekomendacje kontekstowe i podstawowy lejek sklepu — bez zewnętrznego systemu analitycznego.',
        'privacy_title' => 'Privacy-first:',
        'privacy_text' => 'nie zapisujemy adresów IP i nie tworzymy dodatkowego cookies analitycznego. Sesja analityczna powstaje z jednokierunkowego HMAC istniejącego identyfikatora sesji Laravel i wygasa po 30 minutach bez aktywności. Nagłówek DNT=1 wyłącza pomiar.',
        'traffic_kicker' => 'Ruch w czasie',
        'traffic_title' => 'Odsłony',
        'top_pages_kicker' => 'Najczęściej oglądane',
        'top_pages' => 'Popularne strony',
        'hourly_kicker' => 'Rozkład dobowy',
        'hourly' => 'Godziny odwiedzin',
        'page_types' => 'Moduły portalu',
        'sources' => 'Źródła ruchu',
        'devices' => 'Urządzenia',
        'languages' => 'Języki',
        'referrers_kicker' => 'Źródła szczegółowe',
        'referrers' => 'Domeny / kampanie',
        'events_kicker' => 'Interakcje',
        'events' => 'Najczęstsze zdarzenia',
        'funnel_kicker' => 'E-commerce',
        'funnel' => 'Podstawowy lejek sklepu',
        'recent_kicker' => 'Live log',
        'recent_events' => 'Ostatnie zdarzenia',
        'no_data' => 'Brak danych w wybranym okresie.',
        'no_events' => 'Nie zarejestrowano jeszcze zdarzeń.',
    ],
];
