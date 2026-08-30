OKULARY 3D — K86.1 FIX SHOP PRICE TEST

Problem:
Pełny zestaw testów:
154 passed, 1 failed.

Nie był to błąd logiki sklepu. HTML renderował cenę i walutę
w osobnych liniach:

19,99
PLN

Test ShopCatalogTest oczekuje ciągłego tekstu:
19,99 PLN

Naprawa:
Cena i waluta są renderowane w jednym ciągu HTML:
<strong>19,99 PLN</strong>

ZMIENIONY PLIK:
resources/views/shop/index.blade.php

Po rozpakowaniu:
php artisan optimize:clear
php artisan test --filter=ShopCatalogTest
php artisan test

Nie zmieniaj testu — oczekiwanie "19,99 PLN" jest poprawne.
