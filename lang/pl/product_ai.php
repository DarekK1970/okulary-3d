<?php

return [
    'actions' => [
        'seo_fill' => 'Autouzupełnianie SEO',
        'translate' => 'Autotranslator',
        'edit' => 'Edytuj produkt',
        'delete' => 'Usuń produkt',
    ],

    'tooltips' => [
        'seo_complete' => 'Autouzupełnianie SEO — pola SEO są już kompletne',
        'translate_needs_seo' => 'Autotranslator — najpierw uzupełnij pola SEO produktu',
        'translate_locked' => 'Autotranslator — wersja docelowa ma status Ready/Source i jest chroniona przed nadpisaniem',
    ],

    'messages' => [
        'seo_generated' => 'Pola SEO produktu zostały automatycznie uzupełnione przez :provider / :model.',
    ],

    'errors' => [
        'not_configured' => 'AI Translator nie jest skonfigurowany lub jest wyłączony.',
        'provider' => 'Nieobsługiwany provider AI.',
        'http' => 'Provider AI zwrócił błąd HTTP :status.',
        'empty_response' => 'Provider AI nie zwrócił kompletnych pól SEO.',
        'invalid_json' => 'Provider AI zwrócił nieprawidłową strukturę danych.',
        'source_missing' => 'Brakuje źródłowej wersji językowej produktu.',
        'seo_complete' => 'Pola SEO tego produktu są już kompletne. Autouzupełnianie nie nadpisuje ręcznie przygotowanych treści.',
    ],
];
