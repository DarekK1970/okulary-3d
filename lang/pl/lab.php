<?php

return [
    'common' => [
        'home' => 'Strona główna',
        'local_only' => 'Przetwarzanie lokalne w przeglądarce',
        'sources_kicker' => 'Materiały źródłowe',
        'sources_title' => 'Wczytaj parę stereo',
        'sources_help' => 'Najlepszy efekt uzyskasz, gdy oba obrazy mają podobny kadr i rozdzielczość.',
        'left_image' => 'Lewy obraz',
        'right_image' => 'Prawy obraz',
        'choose_or_drop' => 'Kliknij lub przeciągnij plik JPG / PNG / WEBP',
        'no_file' => 'Nie wybrano pliku',
        'swap' => 'Zamień L / R',
        'preview' => 'Podgląd',
        'waiting' => 'Oczekiwanie na dwa obrazy',
        'ready' => 'Gotowe',
        'loading' => 'Wczytywanie obrazu…',
        'fit' => 'Dopasuj',
        'reset' => 'Reset',
        'empty_title' => 'Wczytaj lewy i prawy obraz',
        'empty_text' => 'Podgląd pojawi się automatycznie po wybraniu obu plików.',

        'geometry' => [
            'title' => 'Korekcja geometrii',
            'help' => 'Koryguj pozycję, skalę i rotację prawego obrazu względem lewego.',
            'shift_x' => 'Przesunięcie poziome',
            'shift_y' => 'Przesunięcie pionowe',
            'scale' => 'Skala prawego obrazu',
            'rotation' => 'Rotacja prawego obrazu',
            'tip' => 'Najpierw wyrównaj pion, potem poziom. Przy anaglifach niewielka paralaksa pozioma tworzy efekt głębi.',
        ],

        'export' => [
            'title' => 'Eksport',
            'help' => 'Wygeneruj finalny PNG bez wysyłania plików na serwer.',
            'size' => 'Maksymalny wymiar eksportu',
            'original' => 'Rozdzielczość źródłowa',
            'button' => 'Eksportuj PNG',
            'note' => 'Dla bardzo dużych zdjęć tryb „źródłowy” może wymagać więcej pamięci RAM.',
        ],

        'errors' => [
            'two_images' => 'Najpierw wybierz lewy i prawy obraz.',
            'image' => 'Nie udało się wczytać obrazu. Wybierz poprawny plik JPG, PNG lub WEBP.',
        ],
    ],

    'index' => [
        'meta_title' => '3D LAB — narzędzia stereoskopowe',
        'meta_description' => 'Darmowe narzędzia do anaglifów i przygotowania par stereoskopowych działające bezpośrednio w przeglądarce.',
        'title' => '3D LAB',
        'description' => 'Praktyczne narzędzia do tworzenia i przygotowania obrazów stereoskopowych. Zacznij od klasycznego anaglifu albo wyrównaj parę stereo do dalszej obróbki.',
        'local_processing_title' => 'Twoje zdjęcia zostają na Twoim urządzeniu.',
        'local_processing' => 'W tej wersji 3D LAB obrazy są przetwarzane przez Canvas w przeglądarce i nie są przesyłane do serwera.',
        'open_tool' => 'Uruchom narzędzie',

        'anaglyph' => [
            'title' => 'Anaglyph Maker',
            'description' => 'Połącz lewy i prawy obraz w klasyczny anaglif czerwono-cyjanowy.',
            'feature_1' => 'Color, Half-color, Gray i Optimized',
            'feature_2' => 'Wyrównanie X/Y, skala i rotacja',
            'feature_3' => 'Eksport finalnego PNG',
        ],

        'alignment' => [
            'title' => 'Stereo Alignment / Converter',
            'description' => 'Wyrównaj parę stereo i sprawdź ją w kilku trybach podglądu przed eksportem.',
            'feature_1' => 'Parallel i Cross-eye',
            'feature_2' => 'Anaglyph, Overlay i Blink',
            'feature_3' => 'Eksport pary side-by-side lub anaglifu',
        ],

        'workflow_kicker' => 'Workflow',
        'workflow_title' => 'Od dwóch zdjęć do gotowego obrazu 3D',

        'workflow' => [
            '1' => [
                'title' => 'Wczytaj',
                'text' => 'Dodaj lewy i prawy obraz wykonany z dwóch punktów widzenia.',
            ],
            '2' => [
                'title' => 'Wyrównaj',
                'text' => 'Skoryguj przesunięcie pionowe, poziome, skalę i niewielką rotację.',
            ],
            '3' => [
                'title' => 'Sprawdź',
                'text' => 'Przełącz podgląd anaglifowy, równoległy, krzyżowy lub nakładany.',
            ],
            '4' => [
                'title' => 'Eksportuj',
                'text' => 'Zapisz wynik jako PNG do dalszego wykorzystania lub publikacji.',
            ],
        ],
    ],

    'anaglyph' => [
        'meta_title' => 'Anaglyph Maker online',
        'meta_description' => 'Połącz dwa zdjęcia stereo w anaglif czerwono-cyjanowy i pobierz wynik jako PNG.',
        'title' => 'Anaglyph Maker',
        'description' => 'Wczytaj parę stereo, wyrównaj obrazy i wygeneruj anaglif czerwono-cyjanowy bezpośrednio w przeglądarce.',
        'mode_title' => 'Metoda anaglifu',
        'mode_help' => 'Wybierz sposób mieszania kanałów lewego i prawego obrazu.',
        'mode' => 'Tryb',
        'modes' => [
            'color' => 'Color — pełny kolor',
            'half_color' => 'Half-color — łagodniejsza czerwień',
            'gray' => 'Gray — monochromatyczny',
            'optimized' => 'Optimized — ograniczone ghosting/crosstalk',
        ],
        'preview_hint' => 'Podgląd czerwono-cyjanowy',
    ],

    'alignment' => [
        'meta_title' => 'Stereo Alignment / Converter online',
        'meta_description' => 'Wyrównaj dwa obrazy stereo, sprawdź paralaksę i eksportuj parę side-by-side lub anaglif.',
        'title' => 'Stereo Alignment / Converter',
        'description' => 'Precyzyjnie wyrównaj lewy i prawy kadr, przełącz tryb oglądania i przygotuj poprawną parę do dalszej obróbki.',
        'preview_mode_title' => 'Tryb podglądu',
        'preview_mode_help' => 'Przełączaj technikę oglądania bez zmiany ustawionej geometrii.',
        'preview_mode' => 'Widok',
        'modes' => [
            'parallel' => 'Parallel — para równoległa',
            'cross' => 'Cross-eye — para krzyżowa',
            'anaglyph' => 'Anaglif czerwono-cyjanowy',
            'overlay' => 'Overlay 50%',
            'blink' => 'Blink — naprzemiennie L/R',
        ],
        'export_help' => 'Eksport odpowiada aktualnemu trybowi: anaglif jako pojedynczy obraz, pozostałe jako para side-by-side.',
        'preview_hint' => 'Kontroluj szczególnie linie poziome i obiekty w pobliżu krawędzi.',
    ],
];
