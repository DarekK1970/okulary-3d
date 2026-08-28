OKULARY3D — KROK 69
Katalog sklepu

ZAKRES:
- wielojęzyczne kategorie produktów PL/EN
- wielojęzyczne produkty PL/EN
- niezależne slugi per język
- status tłumaczenia: source / draft / review / ready
- status produktu: draft / active / archived
- marka / producent
- produkt wyróżniony
- warianty SKU
- cena brutto
- VAT
- waluta PLN / EUR
- stan magazynowy
- opcjonalne śledzenie stanu
- aktywność wariantu
- galerie produktów z biblioteki mediów
- możliwość przesłania nowych obrazów podczas edycji produktu
- ochrona mediów używanych przez produkty przed usunięciem

RBAC:
- editor: brak dostępu do katalogu handlowego
- admin: dostęp
- super_admin: dostęp

BACKEND:
http://okulary-3d.test/admin/product-categories
http://okulary-3d.test/admin/products

FRONTEND:
http://okulary-3d.test/pl/shop
http://okulary-3d.test/en/shop

Produkt publiczny musi:
1. mieć status active,
2. posiadać aktywny wariant,
3. posiadać wersję językową source albo ready.

UWAGA:
Koszyk jest jeszcze celowo nieaktywny.
KROK 70 uruchomi koszyk, checkout, zamówienia i historię zamówień klienta.

WDROŻENIE:
1. Rozpakuj paczkę do:
   C:\laragon\www\okulary-3d

2. Wykonaj:
   php artisan optimize:clear
   php artisan migrate
   npm run build
   php artisan test

3. Najpierw utwórz kategorię produktu:
   /admin/product-categories

4. Następnie utwórz produkt:
   /admin/products

   Uzupełnij:
   - PL
   - opcjonalnie EN + status Gotowa
   - co najmniej 1 SKU
   - cenę
   - VAT
   - stan magazynowy
   - zdjęcia z biblioteki

5. Ustaw produkt jako Aktywny.

6. Sprawdź publiczny katalog:
   /pl/shop
   /en/shop

COMMIT PO ZALICZENIU:
git add .
git commit -m "Add shop catalog"
git push

NASTĘPNY KROK:
KROK 70 — koszyk, checkout, zamówienia, statusy zamówień
i historia zamówień na koncie klienta.
