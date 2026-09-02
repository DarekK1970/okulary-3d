<?php

return [
    'actions' => [
        'edit' => 'Edytuj',
        'translate' => 'Automatyczna translacja',
        'generate_image' => 'Wygeneruj obraz',
        'generate_image_confirm' => 'Wygenerować nowy obraz AI i przypisać go jako obraz główny publikacji?',
        'preview' => 'Podgląd',
        'delete' => 'Usuń',
    ],

    'tooltips' => [
        'translation_ready' => 'Wersja docelowa jest już gotowa — automatyczna translacja jest zablokowana.',
        'no_target_language' => 'Brak drugiego języka docelowego w konfiguracji portalu.',
        'preview_unavailable' => 'Podgląd jest dostępny po opublikowaniu artykułu.',
    ],

    'messages' => [
        'image_generated' => 'Obraz został wygenerowany, zapisany w Bibliotece mediów i przypisany do publikacji.',
    ],

    'errors' => [
        'image_exists' => 'Publikacja ma już przypisany obraz. Generator AI nie nadpisuje istniejącej grafiki.',
        'openai_not_configured' => 'Generowanie obrazu wymaga włączonej konfiguracji AI oraz klucza OpenAI.',
        'source_missing' => 'Nie znaleziono źródłowej wersji językowej publikacji.',
        'empty_image' => 'OpenAI nie zwrócił danych obrazu.',
        'invalid_image' => 'OpenAI zwrócił nieprawidłowe dane obrazu.',
    ],
];
