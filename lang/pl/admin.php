<?php

return [
    'title' => 'Panel administracyjny — Wortal Okulary 3D',
    'panel' => 'Backend',
    'navigation' => 'Nawigacja panelu administracyjnego',
    'account' => 'Moje konto',
    'logout' => 'Wyloguj',
    'back_to_portal' => 'Wróć do wortalu',
    'open' => 'Otwórz moduł',
    'no_permission' => 'Brak uprawnień',
    'validation_error' => 'Nie udało się zapisać formularza.',

    'menu' => [
        'dashboard' => 'Dashboard',
        'content' => 'Treści',
        'articles' => 'Artykuły',
        'categories' => 'Kategorie artykułów',
        'shop' => 'Sklep',
        'users' => 'Użytkownicy',
        'translations' => 'Tłumaczenia AI',
        'orchestrator' => 'Orchestrator',
        'analytics' => 'Analityka',
        'settings' => 'Ustawienia',
    ],

    'dashboard' => [
        'title' => 'Dashboard',
        'eyebrow' => 'Wortal Okulary 3D',
        'welcome' => 'Witaj, :name',
        'description' => 'To jest centralny panel zarządzania portalem, sklepem, treściami, tłumaczeniami i automatyką publikacji.',
        'your_role' => 'Twoja rola',
        'stats' => 'Statystyki systemowe',
        'users' => 'Użytkownicy',
        'articles' => 'Artykuły',
        'published' => 'Opublikowane',
        'admins' => 'Administratorzy',
        'modules_kicker' => 'Moduły',
        'modules' => 'Zarządzanie aplikacją',
    ],

    'modules' => [
        'content' => [
            'title' => 'Treści i artykuły',
            'description' => 'Artykuły, kategorie, szkice, publikacje i materiały redakcyjne.',
        ],
        'shop' => [
            'title' => 'Sklep',
            'description' => 'Produkty, kategorie, ceny, magazyn i późniejsza obsługa zamówień.',
        ],
        'users' => [
            'title' => 'Użytkownicy',
            'description' => 'Konta, role, uprawnienia i zarządzanie dostępem do systemu.',
        ],
        'translations' => [
            'title' => 'Tłumaczenia AI',
            'description' => 'Wersje językowe PL/EN i przygotowanie kolejnych języków.',
        ],
        'orchestrator' => [
            'title' => 'Orchestrator',
            'description' => 'Planowanie publikacji, discovery źródeł, generowanie i harmonogram.',
        ],
        'analytics' => [
            'title' => 'Analityka',
            'description' => 'Ruch, publikacje, konwersje i efektywność narzędzi oraz sklepu.',
        ],
        'settings' => [
            'title' => 'Ustawienia systemowe',
            'description' => 'Konfiguracja krytyczna dostępna wyłącznie dla Super Administratora.',
        ],
    ],

    'articles' => [
        'kicker' => 'CMS',
        'title' => 'Artykuły',
        'description' => 'Twórz, edytuj i planuj publikacje wortalu.',
        'new' => 'Nowy artykuł',
        'create_title' => 'Nowy artykuł',
        'create_description' => 'Przygotuj treść, zdjęcie główne i termin publikacji.',
        'edit_title' => 'Edycja artykułu',
        'empty' => 'Nie ma jeszcze żadnych artykułów.',

        'statuses' => [
            'draft' => 'Szkic',
            'scheduled' => 'Zaplanowany',
            'published' => 'Opublikowany',
        ],

        'filters' => [
            'search' => 'Szukaj po tytule, slug lub opisie…',
            'all_statuses' => 'Wszystkie statusy',
            'all_categories' => 'Wszystkie kategorie',
            'apply' => 'Filtruj',
            'clear' => 'Wyczyść',
        ],

        'table' => [
            'title' => 'Artykuł',
            'category' => 'Kategoria',
            'status' => 'Status',
            'publication' => 'Publikacja',
            'author' => 'Autor',
            'actions' => 'Akcje',
        ],

        'actions' => [
            'edit' => 'Edytuj',
            'delete' => 'Usuń',
            'delete_confirm' => 'Czy na pewno usunąć ten artykuł?',
        ],

        'form' => [
            'title' => 'Tytuł',
            'slug' => 'Slug URL',
            'slug_help' => 'Możesz zostawić puste — slug zostanie utworzony automatycznie.',
            'excerpt' => 'Lead / krótki opis',
            'body' => 'Treść artykułu',
            'editor_toolbar' => 'Narzędzia formatowania tekstu',
            'publication' => 'Publikacja',
            'category' => 'Kategoria',
            'choose_category' => 'Wybierz kategorię',
            'status' => 'Status',
            'published_at' => 'Data i godzina publikacji',
            'published_at_help' => 'Dla statusu „Zaplanowany” data musi być w przyszłości. Dla „Opublikowany” puste pole oznacza teraz.',
            'hero' => 'Zdjęcie główne',
            'hero_upload' => 'Wybierz plik',
            'hero_help' => 'JPG, PNG lub WEBP, maksymalnie 5 MB.',
            'hero_preview' => 'Podgląd zdjęcia głównego',
            'remove_hero' => 'Usuń obecne zdjęcie główne',
            'save' => 'Zapisz zmiany',
            'create' => 'Utwórz artykuł',
            'cancel' => 'Anuluj',
        ],

        'messages' => [
            'created' => 'Artykuł został utworzony.',
            'updated' => 'Artykuł został zapisany.',
            'deleted' => 'Artykuł został usunięty.',
        ],
    ],

    'categories' => [
        'kicker' => 'CMS',
        'title' => 'Kategorie artykułów',
        'description' => 'Zarządzaj strukturą tematyczną publikacji.',
        'new' => 'Nowa kategoria',
        'list' => 'Istniejące kategorie',
        'empty' => 'Nie ma jeszcze żadnych kategorii.',
        'articles_short' => 'art.',
        'delete' => 'Usuń kategorię',
        'delete_confirm' => 'Czy na pewno usunąć tę kategorię?',

        'form' => [
            'name' => 'Nazwa',
            'slug' => 'Slug',
            'description' => 'Opis',
            'order' => 'Kolejność',
            'active' => 'Kategoria aktywna',
            'add' => 'Dodaj kategorię',
            'save' => 'Zapisz kategorię',
        ],

        'messages' => [
            'created' => 'Kategoria została dodana.',
            'updated' => 'Kategoria została zapisana.',
            'deleted' => 'Kategoria została usunięta.',
            'in_use' => 'Nie można usunąć kategorii, ponieważ zawiera artykuły.',
        ],
    ],

    'sections' => [
        'shop' => [
            'kicker' => 'E-commerce',
            'title' => 'Sklep',
            'description' => 'Moduł sklepu zostanie uruchomiony w kolejnych etapach projektu.',
        ],
        'users' => [
            'kicker' => 'RBAC',
            'title' => 'Użytkownicy i role',
            'description' => 'Dostęp do tej sekcji mają Administrator i Super Administrator.',
        ],
        'translations' => [
            'kicker' => 'AI',
            'title' => 'Tłumaczenia AI',
            'description' => 'Moduł wielojęzycznych treści i automatycznych tłumaczeń powstanie w dalszym etapie.',
        ],
        'orchestrator' => [
            'kicker' => 'Automatyzacja',
            'title' => 'Orchestrator',
            'description' => 'Moduł automatycznego discovery, planowania i publikowania treści powstanie w dalszym etapie.',
        ],
        'analytics' => [
            'kicker' => 'Dane',
            'title' => 'Analityka',
            'description' => 'Panel statystyczny zostanie podłączony po uruchomieniu głównych modułów aplikacji.',
        ],
        'settings' => [
            'kicker' => 'Super Administrator',
            'title' => 'Ustawienia systemowe',
            'description' => 'Sekcja przeznaczona wyłącznie dla najwyższego poziomu uprawnień.',
        ],
    ],

    'placeholder' => [
        'title' => 'Moduł przygotowany do dalszego wdrożenia',
        'description' => 'Routing, dostęp RBAC i miejsce w panelu są już gotowe. Funkcjonalność modułu zostanie wdrożona w przewidzianym kroku projektu.',
        'back' => 'Wróć do dashboardu',
    ],
];
