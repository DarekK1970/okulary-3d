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
        'maintenance' => 'Konserwacja',
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
        'media' => 'Media',
        'admins' => 'Administratorzy',
        'modules_kicker' => 'Moduły',
        'modules' => 'Zarządzanie aplikacją',
    ],
    'modules' => [
        'content' => [
            'title' => 'Treści i artykuły',
            'description' => 'Artykuły, kategorie, szkice, publikacje i materiały redakcyjne.',
        ],
        'media' => [
            'title' => 'Biblioteka mediów',
            'description' => 'Centralne obrazy, metadane, katalogowanie i ponowne wykorzystanie plików.',
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
