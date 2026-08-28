OKULARY3D — KROK 61
Globalny layout publicznej aplikacji

ZAKRES:
- wspólny layout Blade
- desktopowy i mobilny header
- główne menu wortalu
- przełącznik PL/EN
- ikony wyszukiwarki, konta i koszyka
- responsywne menu mobilne
- wspólna stopka
- pasek newslettera
- autorskie logo SVG Okulary 3D
- globalne CSS
- JavaScript obsługujący menu mobilne
- tłumaczenia PL/EN elementów layoutu
- testy Feature layoutu

UWAGA:
Strona główna jest na tym etapie celowo tylko przestrzenią demonstracyjną.
Pełny homepage zgodny z zaakceptowaną wizualizacją powstaje w KROKU 62.

WDROŻENIE:
1. Rozpakuj paczkę do katalogu:
   C:\laragon\www\okulary-3d
   i zezwól na nadpisanie plików.

2. Wykonaj:
   php artisan optimize:clear
   npm run build
   php artisan test

3. Sprawdź:
   http://okulary-3d.test/pl
   http://okulary-3d.test/en

OCZEKIWANY REZULTAT:
- wspólny header i footer na obu wersjach językowych
- przełącznik PL/EN
- menu desktopowe
- menu hamburger na małej szerokości
- poprawne logo SVG
- teksty layoutu zmieniają język

COMMIT PO ZALICZENIU:
git add .
git commit -m "Add public application layout"
git push
