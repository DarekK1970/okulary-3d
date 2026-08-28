<?php

return [
    'menu' => 'Media',
    'kicker' => 'Zasoby',
    'title' => 'Biblioteka mediów',
    'description' => 'Centralne miejsce na obrazy używane w artykułach i kolejnych modułach wortalu.',

    'usage' => '{0} nieużywane|{1} :count użycie|[2,4] :count użycia|[5,*] :count użyć',

    'upload' => [
        'title' => 'Dodaj obrazy',
        'description' => 'Możesz przesłać jednocześnie do 10 plików JPG, PNG lub WEBP, maks. 5 MB każdy.',
        'folder' => 'Folder / kolekcja',
        'choose' => 'Wybierz pliki',
        'submit' => 'Prześlij',
    ],

    'filters' => [
        'search' => 'Szukaj po nazwie, tytule, ALT lub podpisie…',
        'all_folders' => 'Wszystkie foldery',
        'apply' => 'Filtruj',
        'clear' => 'Wyczyść',
    ],

    'actions' => [
        'edit' => 'Edytuj',
        'save' => 'Zapisz metadane',
        'delete' => 'Usuń plik',
    ],

    'empty' => [
        'title' => 'Biblioteka jest pusta',
        'description' => 'Prześlij pierwsze obrazy, aby można było wykorzystywać je w artykułach.',
    ],

    'edit' => [
        'title' => 'Edycja medium',
        'back' => 'Biblioteka',
        'filename' => 'Plik',
        'dimensions' => 'Wymiary',
        'size' => 'Rozmiar',
        'type' => 'Typ',
        'folder' => 'Folder',
        'usage' => 'Użycia',
        'metadata' => 'Metadane i katalogowanie',
    ],

    'fields' => [
        'title' => 'Tytuł zasobu',
        'alt' => 'Tekst ALT',
        'alt_help' => 'Krótki opis obrazu dla dostępności i SEO.',
        'caption' => 'Podpis / opis',
        'folder' => 'Folder / kolekcja',
    ],

    'delete' => [
        'title' => 'Usunięcie zasobu',
        'description' => 'Plik zostanie fizycznie usunięty z dysku i nie będzie można go odzyskać.',
        'in_use' => 'Ten obraz jest używany przez :count artykuł(y). Najpierw zmień zdjęcie w tych publikacjach.',
        'confirm' => 'Czy na pewno trwale usunąć ten plik?',
    ],

    'messages' => [
        'uploaded' => '{1} Przesłano :count obraz.|[2,4] Przesłano :count obrazy.|[5,*] Przesłano :count obrazów.',
        'updated' => 'Metadane obrazu zostały zapisane.',
        'deleted' => 'Obraz został usunięty z biblioteki.',
        'in_use' => 'Nie można usunąć obrazu, ponieważ jest wykorzystywany przez artykuł.',
    ],

    'article' => [
        'open_library' => 'Biblioteka',
        'choose_library' => 'Wybierz z biblioteki',
        'or_upload' => 'lub prześlij nowy',
        'picker_title' => 'Wybierz zdjęcie główne',
        'close' => 'Zamknij bibliotekę',
        'search' => 'Filtruj obrazy w bibliotece…',
        'no_media' => 'Brak obrazów do wyboru.',
        'latest_limit' => 'W selektorze pokazujemy 100 najnowszych zasobów.',
        'manage_library' => 'Zarządzaj całą biblioteką',
    ],
];
