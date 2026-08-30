OKULARY 3D — K86.2 FIX CATEGORY ROUTES

Problem:
Testy K86.2 zwracały 404 dla:
- /pl/sklep/materialy
- /pl/sklep/materialy/folie

Przyczyna:
Polska trasa kategorii była rejestrowana jako literalna:
  /pl/sklep/{path}
a locale było dodawane przez:
  ->defaults('locale', 'pl')

Middleware SetLocale pobiera język z:
  $request->route('locale')

Dlatego dla tej trasy locale nie było traktowane tak samo jak
rzeczywisty parametr {locale}, co prowadziło do 404 w middleware.

Naprawa:
1. Trasa kategorii ma teraz rzeczywisty parametr:
   /{locale}/sklep/{path}
   ograniczony regexem do locale=pl.
2. SetLocale otrzymuje więc poprawnie route('locale').
3. Wildcard ścieżki kategorii używa `.*`, aby ostatni parametr
   mógł obsługiwać wielopoziomowe ścieżki.
4. /{locale}/shop/{slug} również używa `.*`, dzięki czemu
   angielskie ścieżki kategorii, np.
   /en/shop/materials/films/polarizing-film
   są obsługiwane przez istniejący ShopController::show().
5. Adresy produktów pozostają bez zmian.

ZMODYFIKOWANE PLIKI:
- routes/web.php
- README_PATCH.txt

PO ROZPAKOWANIU:
php artisan optimize:clear
php artisan route:list --path=sklep
php artisan test --filter=ProductCategorySeoTest
php artisan test --filter=ProductCategoryTreeTest

Jeżeli oba zestawy są zielone:
php artisan test
npm run build

Na tym etapie nie commituj przed pełnym testem.
