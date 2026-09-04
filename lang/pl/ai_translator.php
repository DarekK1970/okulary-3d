<?php

return [
    'kicker' => 'Workflow tłumaczeń AI',
    'title' => 'AI Translator',
    'description' => 'Generuj wersje PL/EN dla treści portalu. AI zapisuje wyłącznie szkic — publikacja wymaga osobnej decyzji redakcyjnej.',
    'content_types' => 'Typy treści',

    'types' => [
        'article' => 'Artykuły',
        'product' => 'Produkty',
        'product_category' => 'Kategorie produktów',
        'marketplace_category' => 'Kategorie Marketplace',
        'archive' => 'Archiwum',
    ],

    'status' => [
        'engine' => 'Silnik',
        'ready' => 'Gotowy do pracy',
        'not_ready' => 'Brak konfiguracji',
        'provider' => 'Provider',
        'model' => 'Model',
        'workflow' => 'Zasada publikacji',
        'draft_only' => 'AI → Draft',
    ],

    'not_configured' => [
        'title' => 'Translator nie jest jeszcze aktywny.',
        'text' => 'Super Admin musi włączyć translator, wybrać provider, model i zapisać klucz API w panelu ustawień.',
    ],

    'table' => [
        'content' => 'Treść',
        'direction' => 'Kierunek',
        'target_status' => 'Wersja docelowa',
        'rule' => 'Zasada',
        'actions' => 'Akcje',
        'ready_locked' => 'Gotowa wersja jest chroniona przed nadpisaniem AI.',
        'saved_as_draft' => 'Wynik AI zostanie zapisany jako Draft.',
        'edit' => 'Edytuj',
        'translate' => 'Tłumacz AI',
        'regenerate' => 'Generuj ponownie',
        'empty' => 'Brak treści w tej sekcji.',
    ],

    'target_statuses' => [
        'missing' => 'Brak tłumaczenia',
        'source' => 'Źródłowa',
        'draft' => 'Draft',
        'review' => 'Do weryfikacji',
        'ready' => 'Ready',
    ],

    'run_statuses' => [
        'started' => 'W toku',
        'success' => 'Sukces',
        'failed' => 'Błąd',
    ],

    'runs' => [
        'kicker' => 'Audyt / tokeny',
        'title' => 'Ostatnie uruchomienia',
        'description' => 'Historia zachowuje provider, model oraz wykorzystanie tokenów. Dane będą później wykorzystywane przez Orchestratora.',
        'date' => 'Data',
        'content' => 'Treść',
        'provider' => 'Provider / model',
        'tokens' => 'Tokeny razem',
        'user' => 'Uruchomił',
        'status' => 'Status',
        'empty' => 'Translator nie był jeszcze uruchamiany.',
    ],

    'messages' => [
        'generated' => 'Tłumaczenie zostało wygenerowane jako Draft przez :provider / :model.',
    ],

    'errors' => [
        'not_configured' => 'AI Translator nie jest skonfigurowany lub jest wyłączony.',
        'provider' => 'Nieobsługiwany provider AI.',
        'http' => 'Provider AI zwrócił błąd HTTP :status.',
        'empty_response' => 'Provider AI nie zwrócił treści tłumaczenia.',
        'invalid_json' => 'Provider AI zwrócił nieprawidłową strukturę danych.',
        'source_missing' => 'Brakuje wersji źródłowej tej treści.',
        'target_missing' => 'Nie znaleziono drugiego obsługiwanego języka.',
        'ready_locked' => 'Wersja docelowa ma status Ready/Source i nie może zostać automatycznie nadpisana. Najpierw zmień jej status ręcznie na Draft lub Review.',
        'type' => 'Nieobsługiwany typ treści.',
        'required_field' => 'Provider AI zwrócił pustą wartość wymaganego pola: :field.',
    ],

    'settings' => [
        'open' => 'Ustawienia AI',
        'kicker' => 'Super Admin / konfiguracja',
        'title' => 'Ustawienia AI Translator',
        'description' => 'Klucze są zapisywane zaszyfrowane w bazie ustawień aplikacji. Nie trzeba ręcznie modyfikować pliku .env.',
        'back' => 'Wróć do translatora',
        'general' => 'Konfiguracja ogólna',
        'enabled' => 'Włącz AI Translator',
        'enabled_help' => 'Wyłączenie blokuje nowe wywołania API, ale nie usuwa konfiguracji ani historii.',
        'provider' => 'Aktywny provider',
        'timeout' => 'Timeout API (sekundy)',
        'model' => 'Model',
        'key_placeholder' => 'Wprowadź klucz API',
        'secret_help' => 'Pozostaw pole puste, aby zachować dotychczasowy klucz.',
        'clear_key' => 'Usuń zapisany klucz',
        'glossary' => 'Glosariusz i reguły terminologiczne',
        'glossary_help' => 'Opcjonalne reguły projektu są dołączane do promptu systemowego przy każdym tłumaczeniu.',
        'glossary_placeholder' => "Przykład:\nstereocard = karta stereoskopowa\nlenticular lens = folia / soczewka lentikularna\nNie tłumacz nazw modeli urządzeń.",
        'save' => 'Zapisz ustawienia',
        'saved' => 'Ustawienia AI Translator zostały zapisane.',
    ],
];
