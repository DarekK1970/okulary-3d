OKULARY3D — KROK 60 — poprawka testu domyślnego

ZMIANA:
- domyślny test Laravel nie oczekuje już HTTP 200 dla "/"
- test potwierdza poprawne przekierowanie "/" -> "/pl"

WDROŻENIE:
1. Rozpakuj paczkę do katalogu głównego projektu.
2. Zezwól na nadpisanie pliku:
   tests/Feature/ExampleTest.php
3. Wykonaj:
   php artisan test

Jeśli wszystkie testy przejdą:
git add .
git commit -m "Add multilingual application foundation"
git push
