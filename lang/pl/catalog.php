<?php

return [
    'product_statuses' => [
        'draft' => 'Szkic',
        'active' => 'Aktywny',
        'archived' => 'Archiwalny',
    ],

    'translation_statuses' => [
        'source' => 'Źródłowa',
        'draft' => 'Robocza',
        'review' => 'Do weryfikacji',
        'ready' => 'Gotowa',
    ],

    'validation' => [
        'translation_complete' => 'Jeżeli dodajesz tłumaczenie produktu, podaj zarówno nazwę, jak i opis.',
        'variant_not_owned' => 'Wariant nie należy do edytowanego produktu.',
        'primary_must_be_selected' => 'Zdjęcie główne musi być zaznaczone jako używane w galerii produktu.',
    ],

    'admin' => [
        'kicker' => 'E-commerce',

        'common' => [
            'active' => 'Aktywny',
            'inactive' => 'Nieaktywny',
            'source_locale' => 'Język źródłowy',
            'translation_status' => 'Status tłumaczenia',
            'edit' => 'Edytuj',
            'delete' => 'Usuń',
            'save' => 'Zapisz',
            'cancel' => 'Anuluj',
        ],

        'categories' => [
            'title' => 'Kategorie produktów',
            'description' => 'Wielojęzyczna struktura katalogu sklepu.',
            'new' => 'Nowa kategoria',
            'existing' => 'Istniejące kategorie',
            'empty' => 'Nie ma jeszcze kategorii produktów.',
            'products_short' => 'produktów',
            'delete_confirm' => 'Czy na pewno usunąć tę kategorię?',

            'form' => [
                'name' => 'Nazwa kategorii',
                'slug' => 'Slug URL',
                'description' => 'Opis',
                'order' => 'Kolejność',
                'active' => 'Kategoria aktywna',
                'create' => 'Utwórz kategorię',
            ],

            'messages' => [
                'created' => 'Kategoria produktu została utworzona.',
                'updated' => 'Kategoria produktu została zapisana.',
                'deleted' => 'Kategoria produktu została usunięta.',
                'in_use' => 'Nie można usunąć kategorii, ponieważ zawiera produkty.',
            ],
        ],

        'products' => [
            'title' => 'Produkty',
            'description' => 'Produkty, warianty SKU, ceny, stany magazynowe i galerie.',
            'new' => 'Nowy produkt',
            'create_title' => 'Nowy produkt',
            'create_description' => 'Dodaj wersje PL/EN, warianty, cenę, magazyn i zdjęcia.',
            'edit_title' => 'Edycja produktu',
            'preview' => 'Podgląd',
            'empty' => 'Nie ma jeszcze produktów.',
            'no_brand' => 'bez marki',
            'delete_confirm' => 'Czy na pewno usunąć produkt wraz z wariantami i tłumaczeniami?',

            'filters' => [
                'search' => 'Szukaj nazwy, marki lub SKU…',
                'all_statuses' => 'Wszystkie statusy',
                'all_categories' => 'Wszystkie kategorie',
                'apply' => 'Filtruj',
                'clear' => 'Wyczyść',
            ],

            'table' => [
                'product' => 'Produkt',
                'category' => 'Kategoria',
                'variants' => 'Warianty',
                'price' => 'Cena od',
                'stock' => 'Stan',
                'status' => 'Status',
                'languages' => 'Języki',
                'actions' => 'Akcje',
            ],

            'form' => [
                'languages' => 'Wersje językowe',
                'localized_content' => 'Opis produktu i SEO',
                'name' => 'Nazwa produktu',
                'slug' => 'Slug URL',
                'short_description' => 'Krótki opis',
                'description' => 'Pełny opis produktu',
                'variants' => 'Warianty produktu',
                'variants_help' => 'Każdy wariant ma własny SKU, cenę, VAT i stan magazynowy.',
                'add_variant' => 'Dodaj wariant',
                'variant_name' => 'Nazwa wariantu',
                'price_gross' => 'Cena brutto',
                'currency' => 'Waluta',
                'stock' => 'Stan',
                'track_stock' => 'Kontroluj stan',
                'gallery' => 'Galeria produktu',
                'gallery_help' => 'Wybierz obrazy z centralnej biblioteki i oznacz zdjęcie główne.',
                'manage_media' => 'Biblioteka mediów',
                'use_image' => 'Użyj',
                'primary_image' => 'Główne',
                'no_media' => 'Biblioteka mediów jest pusta.',
                'upload_new' => 'Dodaj nowe obrazy',
                'upload_new_help' => 'Do 5 plików JPG/PNG/WEBP, maks. 5 MB. Po zapisie trafią do biblioteki.',
                'settings' => 'Ustawienia produktu',
                'category' => 'Kategoria',
                'choose_category' => 'Wybierz kategorię',
                'status' => 'Status produktu',
                'brand' => 'Marka / producent',
                'featured' => 'Produkt wyróżniony',
                'create' => 'Utwórz produkt',
            ],

            'messages' => [
                'created' => 'Produkt został utworzony.',
                'updated' => 'Produkt został zapisany.',
                'deleted' => 'Produkt został usunięty.',
            ],
        ],
    ],

    'public' => [
        'kicker' => 'Sklep specjalistyczny 3D',
        'shop_title' => 'Sklep 3D',
        'shop_description' => 'Okulary 3D, folie lentikularne, stereoskopy, aparaty i akcesoria do obrazu przestrzennego.',
        'categories' => 'Kategorie',
        'all_products' => 'Wszystkie produkty',
        'catalog' => 'Katalog',
        'products_count' => 'produktów',
        'featured' => 'Polecany',
        'from' => 'od',
        'in_stock' => 'Dostępny',
        'out_of_stock' => 'Brak w magazynie',
        'empty_title' => 'Brak produktów',
        'empty_description' => 'Katalog jest jeszcze uzupełniany.',
        'home' => 'Strona główna',
        'brand' => 'Marka',
        'variants' => 'Wybierz wariant',
        'other_languages' => 'Inne wersje:',
        'cart_step70' => 'Dodaj do koszyka — KROK 70',
        'cart_step70_note' => 'Katalog jest aktywny. Koszyk i checkout wdrażamy w następnym kroku.',
    ],
];
