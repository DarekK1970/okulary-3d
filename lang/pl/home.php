<?php

return [
    'meta' => [
        'title' => 'Wortal Okulary 3D — stereoskopia, anaglify, lenticular i sklep 3D',
        'description' => 'Polski wortal poświęcony stereoskopii, fotografii i filmom 3D, obrazom lentikularnym, narzędziom online oraz akcesoriom 3D.',
    ],

    'hero' => [
        'badge' => 'Codziennie nowy post o świecie 3D',
        'title_html' => 'Zobacz świat<br><span class="text-red">w trzech</span> <span class="text-cyan">wymiarach</span>',
        'lead' => 'Twórz obrazy 3D, poznawaj stereoskopię i odkrywaj techniki, które nadają głębię. Narzędzia online, wiedza i sklep z akcesoriami — wszystko w jednym miejscu.',
        'cta_anaglyph' => 'Utwórz anaglif',
        'cta_lenticular' => 'Lenticular Lab',
        'cta_shop' => 'Sklep 3D',
        'history_years' => 'lat historii stereoskopii',
        'tools_online' => 'narzędzia online',
        'languages' => 'wersje językowe',
    ],

    'articles' => [
        'kicker' => 'Najnowsze publikacje',
        'title' => 'Najnowsze w świecie 3D',
        'all' => 'Zobacz wszystkie artykuły',
        'items' => [
            [
                'tag' => 'HISTORIA 3D',
                'title' => 'Historia stereoskopii w pigułce',
                'description' => 'Od Wheatstone’a i Brewstera po współczesne okulary oraz wyświetlacze przestrzenne.',
                'date' => '28.08.2026',
                'reading_time' => '6 min czytania',
                'image' => 'article-history.svg',
            ],
            [
                'tag' => 'PORADNIK',
                'title' => 'Jak zrobić zdjęcie 3D smartfonem?',
                'description' => 'Prosta metoda wykonania stereopary bez specjalistycznego aparatu i drogiego sprzętu.',
                'date' => '27.08.2026',
                'reading_time' => '7 min czytania',
                'image' => 'article-smartphone.svg',
            ],
            [
                'tag' => 'TECHNOLOGIE',
                'title' => 'Spatial Photo i nowa era 3D',
                'description' => 'Klasyczna stereoskopia wraca pod nową nazwą i trafia ponownie do masowego odbiorcy.',
                'date' => '26.08.2026',
                'reading_time' => '5 min czytania',
                'image' => 'article-spatial.svg',
            ],
        ],
    ],

    'lab' => [
        'kicker' => 'Twórz samodzielnie',
        'title' => '3D LAB — narzędzia online',
        'description' => 'Praktyczne narzędzia działające bezpośrednio w przeglądarce. Wgraj materiały, ustaw parametry i pobierz gotowy efekt.',
        'run' => 'Uruchom',
        'tools' => [
            [
                'title' => 'Anaglif Maker',
                'description' => 'Połącz lewy i prawy obraz w klasyczny anaglif red/cyan.',
                'icon' => '<span class="mini-glasses"><i></i><i></i></span>',
            ],
            [
                'title' => 'Kreator lenticular 60 LPI',
                'description' => 'Przygotuj przeplatany obraz do druku na folii soczewkowej.',
                'icon' => '<span class="mini-lenticular"></span>',
            ],
            [
                'title' => 'Konwerter stereo',
                'description' => 'Zmieniaj format pomiędzy SBS, parallel, cross-eye i anaglifem.',
                'icon' => '<span class="mini-stereo">↔</span>',
            ],
            [
                'title' => 'Wigglegram Maker',
                'description' => 'Zamień stereoparę w efektowną animację GIF/WebP.',
                'icon' => '<span class="mini-wiggle">≋</span>',
            ],
            [
                'title' => 'Kalkulator bazy stereo',
                'description' => 'Dobierz bezpieczny rozstaw kamer do fotografowanego obiektu.',
                'icon' => '<span class="mini-ruler">↔</span>',
            ],
            [
                'title' => 'Viewer MPO',
                'description' => 'Otwieraj i rozdzielaj pliki MPO z aparatów Fuji, Sony i innych.',
                'icon' => '<span class="mini-mpo">MPO</span>',
            ],
        ],
    ],

    'shop' => [
        'kicker' => 'Sklep 3D',
        'title' => 'Kategorie sklepu',
        'all' => 'Przejdź do sklepu',
        'products' => 'Zobacz produkty',
        'categories' => [
            [
                'title' => 'Okulary 3D',
                'price' => 'już od 2,90 zł',
                'image' => 'shop-glasses.svg',
                'chips' => ['anaglifowe', 'polaryzacyjne', 'Pulfricha', 'elektroniczne'],
            ],
            [
                'title' => 'Folia soczewkowa',
                'price' => 'już od 34,90 zł',
                'image' => 'shop-lenticular.svg',
                'chips' => ['40 LPI', '60 LPI', '75 LPI', '100 LPI'],
            ],
            [
                'title' => 'Stereoskopy',
                'price' => 'już od 99,00 zł',
                'image' => 'shop-stereoscope.svg',
                'chips' => ['kieszonkowe', 'Holmesa', 'retro'],
            ],
            [
                'title' => 'Kamery i aparaty 3D',
                'price' => 'sprzęt nowy i używany',
                'image' => 'shop-camera.svg',
                'chips' => ['Fuji W3', 'kamery stereo', 'akcesoria'],
            ],
        ],
    ],

    'today' => [
        'kicker' => 'Stereoskopia współcześnie',
        'title' => '3D dzisiaj',
        'items' => [
            [
                'label' => 'SPATIAL',
                'title' => 'Spatial Photos',
                'description' => 'Nowoczesny sposób zapisu stereoskopowych zdjęć i filmów na urządzeniach mobilnych.',
                'symbol' => '▣',
                'class' => 'today-blue',
            ],
            [
                'label' => 'IMMERSIVE',
                'title' => 'VR / AR',
                'description' => 'Od stereoskopii do pełnej immersji — technologie, które dają poczucie obecności.',
                'symbol' => '◫',
                'class' => 'today-purple',
            ],
            [
                'label' => 'DISPLAY',
                'title' => 'Wyświetlacze 3D',
                'description' => 'Autostereoskopia, ekrany light-field i nowa generacja obrazowania bez okularów.',
                'symbol' => '◈',
                'class' => 'today-red',
            ],
        ],
    ],

    'gallery' => [
        'kicker' => 'Społeczność',
        'title' => 'Galeria społeczności',
        'tabs_label' => 'Tryb prezentacji obrazów stereo',
        'items' => [
            ['user' => '@stereo_fan', 'likes' => '128', 'mode' => 'Parallel'],
            ['user' => '@depth_explorer', 'likes' => '96', 'mode' => 'Cross-eye'],
            ['user' => '@3d_nature', 'likes' => '145', 'mode' => 'Anaglif'],
            ['user' => '@city_in_3d', 'likes' => '87', 'mode' => 'Wiggle'],
            ['user' => '@retro_3d', 'likes' => '133', 'mode' => 'Parallel'],
            ['user' => '@stereoworld', 'likes' => '102', 'mode' => 'Anaglif'],
        ],
    ],

    'archive' => [
        'kicker' => 'Dziedzictwo stereoskopii',
        'title' => 'Z archiwum stereoskopii',
        'description' => 'Podróż przez ponad 180 lat fotografii przestrzennej — od kart stereoskopowych po współczesne rekonstrukcje cyfrowe.',
        'all' => 'Zobacz całe archiwum',
        'items' => [
            ['type' => 'Karta stereoskopowa', 'title' => 'Paryż — widok z Sekwany', 'year' => 'około 1900'],
            ['type' => 'Karta stereoskopowa', 'title' => 'Tatry — Morskie Oko', 'year' => '1898'],
            ['type' => 'Karta stereoskopowa', 'title' => 'Wnętrze katedry', 'year' => '1910'],
            ['type' => 'Karta stereoskopowa', 'title' => 'Nowy Jork — Broadway', 'year' => '1904'],
            ['type' => 'Karta stereoskopowa', 'title' => 'Wodospad Niagara', 'year' => '1895'],
        ],
    ],
];
