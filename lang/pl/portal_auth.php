<?php

return [
    'common' => [
        'account' => 'Konto użytkownika',
        'my_account' => 'Moje konto',
        'security' => 'Bezpieczeństwo konta',
    ],

    'fields' => [
        'name' => 'Imię / nazwa użytkownika',
        'email' => 'Adres e-mail',
        'password' => 'Hasło',
        'password_confirmation' => 'Powtórz hasło',
        'current_password' => 'Aktualne hasło',
        'new_password' => 'Nowe hasło',
    ],

    'login' => [
        'title' => 'Zaloguj się',
        'description' => 'Zaloguj się, aby korzystać z konta, galerii społeczności i funkcji sklepu.',
        'forgot' => 'Nie pamiętasz hasła?',
        'remember' => 'Zapamiętaj mnie na tym urządzeniu',
        'submit' => 'Zaloguj się',
        'no_account' => 'Nie masz jeszcze konta?',
        'register' => 'Utwórz konto',
    ],

    'register' => [
        'title' => 'Utwórz konto',
        'description' => 'Jedno konto do galerii 3D, narzędzi, zamówień i funkcji społecznościowych.',
        'password_help' => 'Hasło musi mieć minimum 8 znaków oraz zawierać litery i cyfry.',
        'terms' => 'Akceptuję <a href="#">regulamin</a> i <a href="#">politykę prywatności</a>.',
        'submit' => 'Zarejestruj się',
        'have_account' => 'Masz już konto?',
        'login' => 'Zaloguj się',
    ],

    'forgot' => [
        'title' => 'Odzyskaj dostęp',
        'description' => 'Podaj adres e-mail użyty podczas rejestracji. Wyślemy link pozwalający ustawić nowe hasło.',
        'submit' => 'Wyślij link do resetu',
        'back' => 'Wróć do logowania',
    ],

    'reset' => [
        'title' => 'Ustaw nowe hasło',
        'description' => 'Wprowadź nowe hasło do swojego konta.',
        'submit' => 'Zapisz nowe hasło',
    ],

    'account' => [
        'title' => 'Moje konto',
        'welcome' => 'Witaj, :name. Tutaj możesz zarządzać podstawowymi danymi swojego konta.',
        'logout' => 'Wyloguj się',
        'admin_panel' => 'Panel administracyjny',
        'profile_title' => 'Dane profilu',
        'password_title' => 'Zmiana hasła',
        'password_description' => 'Dla bezpieczeństwa podaj również aktualne hasło.',
        'role' => 'Rola',
        'save_profile' => 'Zapisz dane',
        'save_password' => 'Zmień hasło',
    ],

    'roles' => [
        'user' => 'Użytkownik',
        'editor' => 'Redaktor',
        'admin' => 'Administrator',
        'super_admin' => 'Super Administrator',
    ],

    'wallet' => [
        'title' => 'Twój portfel TOKEN_LENS',
        'description' => 'Tokeny wykorzystasz do generowania AI oraz usług w marketplace.',
        'help_label' => 'Czym są TOKEN_LENS?',
        'help' => 'TOKEN_LENS to Twoje wartościowe punkty, które możesz wymieniać na działania sztucznej inteligencji pomagające zamienić zwykłe zdjęcie 2D lub parę zdjęć w wydruk lentikularny. Możesz też wykorzystać TOKEN_LENS do wydrukowania obrazu lentikularnego w naszym sklepie.',
        'zero_balance' => 'Nie masz TOKEN_LENS. Przejdź na wyższy plan lub dokup tokeny.',
        'zero_balance_premium' => 'Nie masz TOKEN_LENS. Możesz dokupić dodatkowe tokeny.',
        'change_plan' => 'Przejdź na wyższy plan',
        'buy_tokens' => 'Dokup TOKEN_LENS',
        'empty' => 'Nie masz jeszcze operacji w portfelu.',
        'insufficient' => 'Nie masz wystarczającej liczby TOKEN_LENS.',
        'types' => ['grant' => 'Przyznane tokeny', 'admin_adjustment' => 'Korekta salda', 'ai_video' => 'Generowanie filmu AI', 'marketplace_order' => 'Zamówienie w marketplace'],
        'header_balance' => 'Twoje TOKEN_LENS: :count',
        'valid_until' => 'ważne do :date',
        'no_expiry' => 'bez terminu ważności',
    ],

    'projects' => [
        'title' => 'Moje projekty',
        'description' => 'Wróć do rozpoczętej pracy, pobierz gotowe pliki lub wybierz projekt do wydruku.',
        'number' => 'Lp.',
        'created_at' => 'Data utworzenia',
        'name' => 'Nazwa',
        'preview' => 'Podgląd',
        'actions' => 'Akcje',
        'preview_alt' => 'Podgląd projektu :name',
        'no_preview' => 'Brak podglądu',
        'download' => 'Pobierz finalny plik',
        'open_files' => 'Otwórz pliki projektu',
        'download_zip' => 'Pobierz cały projekt jako ZIP',
        'edit' => 'Edytuj projekt',
        'order' => 'Zamów wydruk UV',
        'delete' => 'Usuń projekt',
        'delete_confirm' => 'Czy na pewno usunąć projekt „:name” wraz z jego plikami?',
        'deleted' => 'Projekt został usunięty.',
        'empty' => 'Nie masz jeszcze żadnych projektów.',
    ],

    'project_files' => [
        'title' => 'Pliki projektu',
        'back' => 'Wróć do Mojego konta',
        'description' => 'Pliki źródłowe, podglądy oraz gotowe materiały projektu.',
        'download_all' => 'Pobierz cały projekt jako ZIP',
        'kind' => 'Rodzaj',
        'file' => 'Plik',
        'type' => 'Typ',
        'size' => 'Rozmiar',
        'preview' => 'Podgląd',
        'actions' => 'Akcje',
        'download' => 'Pobierz plik',
        'missing' => 'Brak pliku na dysku',
        'empty' => 'Projekt nie zawiera jeszcze plików.',
    ],

    'messages' => [
        'registered' => 'Konto zostało utworzone.',
        'logged_out' => 'Zostałeś wylogowany.',
        'profile_updated' => 'Dane profilu zostały zapisane.',
        'password_updated' => 'Hasło zostało zmienione.',
        'account_suspended' => 'To konto zostało zawieszone. Skontaktuj się z administratorem.',
    ],
];
