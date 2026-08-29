<?php

return [
    'tools' => [
        'anaglyph' => [
            'title' => 'Anaglyph Maker',
            'description' => 'Połącz lewy i prawy obraz w anaglif czerwono-cyjanowy i wyeksportuj wynik.',
        ],
        'stereo_alignment' => [
            'title' => 'Stereo Alignment / Converter',
            'description' => 'Wyrównaj parę stereo i sprawdź ją jako Parallel, Cross-eye, Overlay lub Anaglyph.',
        ],
        'lenticular' => [
            'title' => 'Lenticular LAB',
            'description' => 'Przeplataj obrazy, generuj Pitch Test i przygotuj plik PDF do druku na folii soczewkowej.',
        ],
        'mpo' => [
            'title' => 'MPO Viewer / Converter',
            'description' => 'Otwórz plik MPO, wydziel parę L/R i konwertuj obraz do wygodnych formatów stereo.',
        ],
        'wigglegram' => [
            'title' => 'Wigglegram Maker',
            'description' => 'Zamień parę lub serię ujęć stereo w animowany efekt głębi.',
        ],
    ],

    'admin' => [
        'kicker' => 'Ścieżka Article → LAB → Shop',
        'title' => 'Rekomendacje kontekstowe',
        'help' => 'Wskaż narzędzia i produkty, które najlepiej rozwijają temat artykułu. Ręczne wybory mają pierwszeństwo przed dopasowaniem automatycznym.',
        'auto' => 'Uzupełniaj rekomendacje automatycznie',
        'auto_help' => 'Jeżeli nie wybierzesz pełnego zestawu, system spróbuje dobrać brakujące narzędzia i produkty na podstawie treści artykułu. Wyłącz, aby wyświetlać wyłącznie ręczne wskazania.',
        'tools' => 'Narzędzia 3D LAB',
        'tools_help' => 'Możesz wybrać maksymalnie 2 narzędzia.',
        'products' => 'Produkty ze sklepu',
        'products_help' => 'Możesz wybrać maksymalnie 4 produkty. Użyj Ctrl/Cmd, aby zaznaczyć kilka pozycji.',
    ],

    'public' => [
        'kicker' => 'Z artykułu do praktyki',
        'title' => 'Sprawdź ten temat w praktyce',
        'description' => 'Uruchom odpowiednie narzędzie 3D LAB albo zobacz produkty powiązane z omawianą techniką.',
        'tools_title' => 'Powiązane narzędzia',
        'products_title' => 'Powiązane produkty',
        'shop_badge' => 'Sklep 3D',
        'open_tool' => 'Uruchom narzędzie',
        'open_product' => 'Zobacz produkt',
        'from' => 'od',
    ],
];
