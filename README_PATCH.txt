OKULARY3D — KROK 62 — poprawka testu lokalizacji

PRZYCZYNA:
KROK 62 zastąpił demonstracyjny homepage pełną stroną główną.
Stary test LocalizationTest nadal oczekiwał tekstów z wersji demonstracyjnej.

ZMIANA:
- testy PL/EN zostały dostosowane do aktualnego homepage
- testy nadal weryfikują:
  * / -> /pl
  * lang="pl" oraz lang="en"
  * polskie i angielskie treści
  * 404 dla /de

WDROŻENIE:
1. Rozpakuj paczkę do katalogu projektu.
2. Zezwól na nadpisanie:
   tests/Feature/LocalizationTest.php
3. Wykonaj:
   php artisan test

Jeśli wszystkie testy przejdą:
git add .
git commit -m "Build portal homepage"
git push
