<?php

return [
    'translation_statuses' => [
        'source' => 'Źródłowa',
        'draft' => 'Robocza',
        'review' => 'Do weryfikacji',
        'ready' => 'Gotowa',
    ],

    'articles' => [
        'kicker' => 'CMS wielojęzyczny',
        'title' => 'Artykuły',
        'description' => 'Jedna publikacja może posiadać niezależne wersje językowe PL i EN.',
        'new' => 'Nowy artykuł',
        'create_title' => 'Nowy artykuł',
        'create_description' => 'Przygotuj wersję źródłową, opcjonalne tłumaczenie, SEO i termin publikacji.',
        'edit_title' => 'Edycja artykułu',
        'empty' => 'Nie ma jeszcze żadnych artykułów.',

        'statuses' => [
            'draft' => 'Szkic',
            'scheduled' => 'Zaplanowany',
            'published' => 'Opublikowany',
        ],

        'filters' => [
            'search' => 'Szukaj we wszystkich wersjach językowych…',
            'all_statuses' => 'Wszystkie statusy',
            'all_categories' => 'Wszystkie kategorie',
            'apply' => 'Filtruj',
            'clear' => 'Wyczyść',
        ],

        'table' => [
            'title' => 'Artykuł',
            'category' => 'Kategoria',
            'status' => 'Publikacja',
            'languages' => 'Języki',
            'publication' => 'Termin',
            'actions' => 'Akcje',
        ],

        'actions' => [
            'edit' => 'Edytuj',
            'preview' => 'Podgląd',
            'delete' => 'Usuń',
            'delete_confirm' => 'Czy na pewno usunąć artykuł wraz ze wszystkimi tłumaczeniami?',
        ],

        'form' => [
            'languages' => 'Wersje językowe',
            'localized_content' => 'Treść i SEO per język',
            'source_language_badge' => 'Język źródłowy',
            'title' => 'Tytuł',
            'slug' => 'Slug URL',
            'slug_help' => 'Slug jest niezależny dla każdego języka. Puste pole zostanie wygenerowane automatycznie.',
            'excerpt' => 'Lead / krótki opis',
            'body' => 'Treść artykułu',
            'seo_heading' => 'Metadane wyszukiwarki',
            'seo_title' => 'SEO title',
            'seo_title_help' => 'Maks. 70 znaków. Puste pole użyje tytułu artykułu.',
            'seo_description' => 'Meta description',
            'translation_status' => 'Status tłumaczenia',
            'publication' => 'Publikacja',
            'category' => 'Kategoria',
            'choose_category' => 'Wybierz kategorię',
            'source_locale' => 'Język źródłowy',
            'source_locale_help' => 'Wersja źródłowa jest zawsze oznaczana jako „Źródłowa” i nie podlega workflow tłumaczenia.',
            'status' => 'Status publikacji',
            'published_at' => 'Data i godzina publikacji',
            'published_at_help' => 'Dla „Zaplanowany” termin musi być w przyszłości. Dla „Opublikowany” puste pole oznacza teraz.',
            'hero' => 'Zdjęcie główne',
            'hero_upload' => 'Wybierz plik',
            'hero_help' => 'Wspólne dla wszystkich języków. JPG, PNG lub WEBP, maks. 5 MB.',
            'hero_preview' => 'Podgląd zdjęcia',
            'remove_hero' => 'Usuń obecne zdjęcie główne',
            'save' => 'Zapisz zmiany',
            'create' => 'Utwórz artykuł',
            'cancel' => 'Anuluj',
        ],

        'validation' => [
            'translation_complete' => 'Jeżeli tworzysz tłumaczenie, podaj zarówno tytuł, jak i treść.',
        ],

        'messages' => [
            'created' => 'Wielojęzyczny artykuł został utworzony.',
            'updated' => 'Artykuł i jego wersje językowe zostały zapisane.',
            'deleted' => 'Artykuł wraz z tłumaczeniami został usunięty.',
        ],
    ],
];
