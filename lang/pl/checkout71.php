<?php

return [
    'cart' => [
        'choose_in_checkout' => 'wybór w checkout',
        'shipping_note' => 'Metodę i koszt dostawy wybierzesz w następnym kroku.',
    ],

    'payment_statuses' => [
        'unpaid' => 'Nieopłacone',
        'pending' => 'Płatność w toku',
        'paid' => 'Opłacone',
        'failed' => 'Płatność nieudana',
    ],

    'payment_methods' => [
        'bank_transfer' => 'Przelew tradycyjny',
        'paynow' => 'PayNow',
    ],

    'validation' => [
        'shipping_unavailable' => 'Wybrana metoda dostawy nie jest dostępna dla tej waluty.',
        'payment_unavailable' => 'Wybrana metoda płatności nie jest obecnie dostępna.',
        'shipping_point_required' => 'Dla tej metody dostawy podaj Paczkomat lub punkt odbioru.',
        'no_shipping_methods' => 'Brak dostępnych metod dostawy dla wybranej waluty.',
        'no_payment_methods' => 'Brak dostępnych metod płatności dla wybranej waluty.',
    ],

    'checkout' => [
        'description' => 'Wybierz dostawę i płatność, a następnie podaj dane potrzebne do realizacji zamówienia.',
        'shipping_method' => 'Metoda dostawy',
        'shipping_point' => 'Paczkomat / punkt odbioru',
        'shipping_point_placeholder' => 'np. TOR01M lub nazwa punktu',
        'payment_method' => 'Metoda płatności',
        'paynow_hint' => 'BLIK, szybki przelew lub karta — przekierowanie do PayNow.',
        'bank_hint' => 'Dane do przelewu otrzymasz po złożeniu zamówienia.',
        'final_note' => 'Cena końcowa obejmuje wybrane produkty i koszt dostawy.',
    ],

    'success' => [
        'description' => 'Zamówienie zostało zapisane. Poniżej znajdziesz status płatności, dostawę i dokument zamówienia.',
        'payment' => 'Płatność',
        'bank_transfer_title' => 'Dane do przelewu',
        'bank_transfer_description' => 'W tytule przelewu wpisz numer zamówienia.',
        'recipient' => 'Odbiorca',
        'bank' => 'Bank',
        'account' => 'Numer rachunku',
        'transfer_title' => 'Tytuł przelewu',
        'retry_paynow' => 'Ponów płatność PayNow',
        'print_document' => 'Drukuj potwierdzenie',
    ],

    'paynow' => [
        'not_configured' => 'PayNow nie jest skonfigurowany.',
        'currency_not_supported' => 'PayNow nie jest aktywne dla waluty tego zamówienia. Waluty obce wymagają osobnej aktywacji płatności kartowych po stronie PayNow.',
        'invalid_response' => 'PayNow zwrócił niepełną odpowiedź.',
        'start_failed' => 'Nie udało się uruchomić płatności PayNow. Zamówienie zostało zapisane — możesz ponowić płatność.',
    ],

    'admin' => [
        'orders_description' => 'Rejestr zamówień, statusów płatności, dostawy i realizacji.',
        'payment' => 'Płatność',
        'all_payments' => 'Wszystkie płatności',
        'paid_at' => 'Opłacono',
        'mark_paid' => 'Oznacz przelew jako opłacony',
        'mark_unpaid' => 'Cofnij oznaczenie płatności',
        'payment_updated' => 'Status płatności został zaktualizowany.',
        'manual_only_bank_transfer' => 'Ręczna zmiana płatności jest dostępna tylko dla przelewu tradycyjnego.',
        'cannot_revert_after_processing' => 'Nie można cofnąć płatności po rozpoczęciu realizacji zamówienia.',
        'payment_required_for_processing' => 'Przed rozpoczęciem realizacji zamówienie musi być opłacone.',
        'cannot_cancel_paid_order' => 'Opłaconego zamówienia nie można anulować bez obsługi zwrotu płatności.',
        'cannot_cancel_pending_online' => 'Nie można anulować zamówienia, gdy płatność online jest jeszcze w toku.',
        'print_document' => 'Drukuj potwierdzenie',
        'point' => 'Punkt odbioru',
        'currency_snapshot' => 'Snapshot waluty zamówienia',
        'exchange_rate' => 'Kurs zastosowany',
        'conversion_margin' => 'Marża przewalutowania',
        'rate_source' => 'Źródło kursu',
        'base_total' => 'Wartość bazowa zamówienia',
    ],

    'document' => [
        'title' => 'Potwierdzenie zamówienia',
        'number' => 'Numer dokumentu',
        'order' => 'Zamówienie',
        'date' => 'Data wystawienia',
        'buyer' => 'Nabywca',
        'items' => 'Pozycje',
        'item' => 'Produkt',
        'quantity' => 'Ilość',
        'unit_price' => 'Cena brutto',
        'value' => 'Wartość brutto',
        'total' => 'Razem',
        'print' => 'Drukuj',
        'notice' => 'Dokument stanowi potwierdzenie złożenia zamówienia i nie jest fakturą VAT ani paragonem fiskalnym.',
        'currency_snapshot' => 'Rozliczenie walutowe zamówienia',
        'exchange_rate' => 'Zastosowany kurs',
        'conversion_margin' => 'Marża przewalutowania',
        'rate_source' => 'Źródło kursu',
        'base_total' => 'Wartość bazowa',
    ],

    'mail' => [
        'placed_subject' => 'Zamówienie :number zostało przyjęte',
        'paid_subject' => 'Potwierdzenie płatności — :number',
        'shipped_subject' => 'Zamówienie :number zostało wysłane',
        'placed_heading' => 'Dziękujemy za zamówienie',
        'placed_intro' => 'Przyjęliśmy zamówienie :number.',
        'paid_heading' => 'Płatność została potwierdzona',
        'paid_intro' => 'Zarejestrowaliśmy płatność za zamówienie :number.',
        'shipped_heading' => 'Zamówienie zostało wysłane',
        'shipped_intro' => 'Zamówienie :number otrzymało status wysłane.',
        'total' => 'Razem',
        'payment' => 'Płatność',
        'shipping' => 'Dostawa',
        'open_order' => 'Otwórz szczegóły zamówienia',
    ],
];
