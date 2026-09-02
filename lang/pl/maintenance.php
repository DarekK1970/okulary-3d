<?php

return [
    'kicker' => 'Ustawienia systemowe',
    'title' => 'Strona w konserwacji',
    'description' => 'Tymczasowo wyłącz publiczny dostęp do wortalu i pozostaw pełny podgląd wyłącznie dla wskazanych adresów IP.',

    'section' => [
        'kicker' => 'Dostęp techniczny',
        'title' => 'Tryb konserwacji',
        'description' => 'Po włączeniu publiczne podstrony zwracają kod HTTP 503. Panel administracyjny, logowanie administratora, health-check i techniczne endpointy płatności pozostają dostępne.',
    ],

    'status' => [
        'enabled' => 'Konserwacja włączona',
        'disabled' => 'Portal publiczny aktywny',
    ],

    'form' => [
        'enabled' => 'Włącz tryb konserwacji',
        'allowed_ips' => 'Adresy IP z dostępem do podglądu',
        'allowed_ips_help' => 'Wpisz jeden adres IPv4 lub IPv6 w wierszu. Możesz też rozdzielać adresy przecinkami lub średnikami. Dostęp jest przyznawany wyłącznie dla dokładnie wskazanych adresów.',
        'save' => 'Zapisz ustawienia',
    ],

    'current_ip' => [
        'title' => 'Bieżący adres IP',
        'description' => 'Laravel rozpoznaje to połączenie jako:',
    ],

    'safety' => [
        'title' => 'Bezpieczne wyłączenie strony',
        'description' => 'Panel /admin nie jest blokowany trybem konserwacji, więc ustawienie można wyłączyć nawet wtedy, gdy bieżącego adresu IP nie ma na liście podglądu.',
    ],

    'messages' => [
        'saved' => 'Ustawienia trybu konserwacji zostały zapisane.',
    ],

    'errors' => [
        'invalid_ips' => 'Nieprawidłowe adresy IP: :ips',
        'ip_required_when_enabled' => 'Przed włączeniem trybu konserwacji wskaż co najmniej jeden adres IP z dostępem do podglądu.',
    ],

    'public' => [
        'kicker' => 'Wortal Okulary 3D',
        'title' => 'Trwają prace techniczne',
        'description' => 'Wortal jest chwilowo niedostępny publicznie. Wprowadzamy zmiany i sprawdzamy ich działanie przed ponownym udostępnieniem serwisu.',
        'retry' => 'Zapraszamy ponownie za chwilę.',
        'current_ip' => 'Adres IP tego połączenia:',
        'admin' => 'Panel administracyjny',
    ],
];
