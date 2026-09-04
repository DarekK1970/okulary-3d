<?php

return [
    'menu' => 'fal.ai', 'kicker' => 'AI LENTICULAR STUDIO', 'title' => 'Integracja fal.ai',
    'description' => 'Konfiguracja generowania wideo Seedance, skalowania jakości i zabezpieczeń kosztowych.',
    'ready' => 'Gotowe do użycia', 'not_ready' => 'Wymaga konfiguracji', 'save' => 'Zapisz ustawienia',
    'connection' => ['title' => 'Połączenie API', 'description' => 'Dane dostępowe są szyfrowane w bazie i widoczne tylko dla superadministratora.', 'enabled' => 'Włącz integrację fal.ai', 'enabled_help' => 'Generowanie będzie dostępne dopiero po zapisaniu prawidłowego klucza.', 'timeout' => 'Limit czasu żądania (sekundy)', 'api_key' => 'Klucz API', 'key_placeholder' => 'Wklej klucz API z panelu fal.ai', 'secret_help' => 'Pozostaw puste, aby zachować obecny klucz.', 'clear_key' => 'Usuń zapisany klucz API'],
    'seedance' => ['title' => 'Seedance — obraz na wideo', 'description' => 'Domyślne parametry generowania sekwencji dla efektu 3D.', 'model' => 'Endpoint modelu', 'resolution' => 'Rozdzielczość', 'duration' => 'Czas filmu (sekundy)', 'audio' => 'Generuj dźwięk', 'audio_help' => 'Dla projektów lentikularnych dźwięk powinien pozostać wyłączony.'],
    'upscale' => ['title' => 'Zwiększanie rozdzielczości', 'description' => 'Przygotowanie materiału do wydruków A3 i większych.', 'enabled' => 'Włącz upscaling dla wymagających formatów', 'enabled_help' => 'Silnik projektu zdecyduje, kiedy rozdzielczość źródłowa jest niewystarczająca.', 'model' => 'Endpoint upscalera', 'resolution' => 'Docelowa rozdzielczość'],
    'cost' => ['title' => 'Zabezpieczenia kosztowe', 'description' => 'Twarde limity, które będą sprawdzane przed zleceniem płatnego zadania.', 'maximum_job' => 'Maksymalny koszt jednego zadania (USD)', 'daily_budget' => 'Dzienny budżet aplikacji (USD)', 'note' => 'Limity aplikacji uzupełniają limity i saldo ustawione bezpośrednio na koncie fal.ai.'],
    'test' => ['title' => 'Test połączenia', 'description' => 'Sprawdza autoryzację przez odczyt cennika modelu. Nie uruchamia płatnego generowania.', 'button' => 'Sprawdź połączenie'],
    'messages' => ['saved' => 'Ustawienia fal.ai zostały zapisane.', 'test_success' => 'Połączenie z fal.ai działa prawidłowo.', 'missing_key' => 'Najpierw zapisz klucz API fal.ai.', 'connection_error' => 'Nie udało się połączyć z fal.ai.', 'test_failed' => 'fal.ai odrzuciło żądanie (HTTP :status). Sprawdź klucz i endpoint modelu.'],
];
