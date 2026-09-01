<?php

return [
    'admin' => [
        'menu' => 'Strony statyczne',
        'kicker' => 'Treści stałe',
        'title' => 'Strony statyczne',
        'description' => 'Edytuj strony informacyjne i regulaminy portalu oraz sklepu. Każda strona obsługuje wszystkie języki skonfigurowane w wortalu.',
        'edit_title' => 'Edycja strony statycznej',
        'edit_description' => 'Treść jest edytowana osobno dla każdego języka. Pole treści działa jako edytor WYSIWYG.',
    ],

    'groups' => [
        'content' => 'Strony statyczne',
        'shop' => 'Sklep',
    ],

    'table' => [
        'page' => 'Strona',
        'languages' => 'Języki',
        'updated' => 'Aktualizacja',
        'actions' => 'Akcje',
    ],

    'actions' => [
        'edit' => 'Edytuj',
        'auto_translate' => 'Automatyczne tłumaczenie',
        'preview' => 'Podgląd',
        'save' => 'Zapisz zmiany',
        'cancel' => 'Anuluj',
        'back' => 'Strony statyczne',
    ],

    'editor' => [
        'languages' => 'Wersje językowe',
        'content' => 'Treść strony',
        'source' => 'Język źródłowy',
        'title' => 'Tytuł',
        'body' => 'Treść — WYSIWYG',
        'seo' => 'Metadane strony',
        'seo_title' => 'SEO title',
        'seo_description' => 'SEO description',
        'page_info' => 'Informacje o stronie',
        'key' => 'Klucz systemowy',
        'group' => 'Sekcja',
        'public_url' => 'Adres publiczny',
    ],

    'messages' => [
        'saved' => 'Strona statyczna została zapisana.',
        'translated' => 'Dodano brakujące wersje językowe: :locales.',
        'no_missing_translations' => 'Ta strona ma już kompletne wersje we wszystkich skonfigurowanych językach.',
    ],

    'errors' => [
        'source_missing' => 'Brakuje źródłowej wersji językowej tej strony.',
        'source_title_required' => 'Tytuł źródłowej wersji strony jest wymagany.',
        'translation_title_required' => 'Jeżeli tworzysz wersję językową, podaj również jej tytuł.',
    ],

    'public' => [
        'breadcrumbs' => 'Nawigacja okruszkowa',
        'home' => 'Strona główna',
        'languages' => 'Wersje językowe:',
        'content_pending' => 'Treść tej strony jest w przygotowaniu.',
        'back' => 'Wróć do strony głównej',
    ],

    'footer' => [
        'portal_terms' => 'Regulamin portalu',
        'shop_terms' => 'Regulamin sklepu',
        'secure_payments' => 'Bezpieczne płatności',
    ],
];
