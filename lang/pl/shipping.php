<?php

return [
    'admin' => [
        'menu' => 'Dostawy',
        'kicker' => 'Logistyka sklepu',
        'title' => 'Dostawy i cenniki wysyłek',
        'description' => 'Kraje dostawy, metody, przedziały wagowe, ceny bazowe PLN i marża logistyczna.',
        'back_to_products' => 'Produkty',

        'stats' => [
            'active_countries' => 'Aktywne kraje',
            'active_methods' => 'Aktywne metody',
            'rates' => 'Reguły cenowe',
            'missing_weight' => 'Warianty bez wagi',
        ],

        'settings' => [
            'kicker' => 'Konfiguracja',
            'title' => 'Kraje i metody dostawy',
            'description' => 'Polska jest zawsze aktywnym krajem domyślnym. Pozostałe kraje udostępniasz klientom ręcznie.',
            'logistics_margin' => 'Marża logistyczna [%]',
            'logistics_margin_help' => 'Domyślnie 10,00%. Jest naliczana wyłącznie dla dostaw poza Polskę, przed przeliczeniem waluty i marżą przewalutowania.',
            'countries' => 'Dostępne kraje dostawy',
            'methods' => 'Aktywne metody dostawy',
            'pickup_point' => 'wymaga wyboru punktu',
            'default_country' => 'kraj domyślny — zawsze aktywny',
            'save' => 'Zapisz ustawienia dostaw',
        ],

        'rates' => [
            'kicker' => 'Cennik ręczny',
            'title' => 'Koszty dostawy według kraju i wagi',
            'description' => 'Cena bazowa jest przechowywana w PLN. Przedziały dla tej samej pary kraj + metoda nie mogą się nakładać.',
            'margin_note' => 'K87.1 przechowuje ceny bazowe PLN. W K87.2 dostawa poza Polskę otrzyma automatycznie marżę logistyczną :margin%, a następnie — jeśli klient płaci w innej walucie — przeliczenie NBP i marżę przewalutowania.',
            'country' => 'Kraj',
            'method' => 'Metoda',
            'from_kg' => 'Od [kg]',
            'to_kg' => 'Do [kg]',
            'price_pln' => 'Cena PLN',
            'active' => 'Aktywna',
            'add' => 'Dodaj regułę',
            'save' => 'Zapisz',
            'delete' => 'Usuń',
            'delete_confirm' => 'Czy usunąć tę regułę kosztu dostawy?',
            'empty' => 'Nie ma jeszcze reguł kosztów dostawy.',
        ],

        'weights' => [
            'kicker' => 'Waga produktów',
            'title' => 'Wagi wariantów SKU',
            'description' => 'Waga jest przechowywana w gramach i w K87.2 posłuży do automatycznego wyboru właściwego przedziału kosztu dostawy.',
            'warning' => 'Brakuje wagi dla :count wariantów. Przed uruchomieniem dynamicznego checkoutu uzupełnij wszystkie warianty sprzedawane online.',
            'product' => 'Produkt',
            'variant' => 'Wariant',
            'weight' => 'Waga [g]',
            'placeholder' => 'np. 250',
            'save' => 'Zapisz wagi',
            'empty' => 'Brak wariantów produktów.',
        ],

        'next_step' => [
            'title' => 'K87.2 — dynamiczny checkout',
            'description' => 'Ten etap tylko przygotowuje dane administracyjne. Obecny checkout nadal korzysta z dotychczasowych metod dostawy. K87.2 podłączy kraj klienta, wagę koszyka, cenniki oraz marżę logistyczną do kalkulacji checkoutu.',
        ],

        'messages' => [
            'settings_saved' => 'Ustawienia dostaw zostały zapisane.',
            'rate_created' => 'Reguła kosztu dostawy została dodana.',
            'rate_updated' => 'Reguła kosztu dostawy została zapisana.',
            'rate_deleted' => 'Reguła kosztu dostawy została usunięta.',
            'weights_saved' => 'Wagi wariantów zostały zapisane.',
        ],

        'validation' => [
            'overlap' => 'Ten przedział wagowy nakłada się na istniejącą regułę dla tego samego kraju i metody dostawy.',
            'variant_missing' => 'Co najmniej jeden wariant produktu nie istnieje.',
        ],
    ],

    'checkout' => [
        'country' => 'Kraj dostawy',
        'weight' => 'Waga zamówienia: :weight kg',
        'loading' => 'Przeliczam dostępne metody i koszt dostawy…',
        'no_methods' => 'Dla wybranego kraju i wagi zamówienia nie ma obecnie dostępnej metody dostawy.',
        'quote_error' => 'Nie udało się przeliczyć kosztu dostawy. Wybierz kraj ponownie lub spróbuj za chwilę.',
        'weight_missing' => 'Nie można jeszcze obliczyć dostawy, ponieważ co najmniej jeden wariant produktu nie ma zdefiniowanej wagi. Skontaktuj się z obsługą sklepu.',
        'method_unavailable' => 'Wybrana metoda dostawy nie jest dostępna dla tego kraju i wagi zamówienia.',
        'same_address_country_mismatch' => 'Jeżeli adres dostawy jest taki sam jak rozliczeniowy, kraj adresu rozliczeniowego i kraj dostawy muszą być identyczne.',
    ],

    'admin_order' => [
        'snapshot' => 'Snapshot kalkulacji dostawy',
        'country' => 'Kraj dostawy',
        'weight' => 'Waga przesyłki',
        'base_before_margin' => 'Cena bazowa przed marżą',
        'logistics_margin' => 'Marża logistyczna',
        'base_after_margin' => 'Cena bazowa po marży',
    ],
];
