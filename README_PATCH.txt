OKULARY3D — KROK 66 — FIX OPTIONAL FIELDS

PRZYCZYNA:
Pola nullable/optional po walidacji Laravel nie zawsze występują w tablicy
$validated. Kontrolery odwoływały się bezpośrednio m.in. do:
$validated['excerpt']

Test sanitizacji HTML celowo nie przekazywał excerpt, dlatego artykuł nie
dochodził do zapisu i test kończył się ModelNotFoundException.

POPRAWIONO:
- ArticleController:
  * slug
  * excerpt
  * published_at
- ArticleCategoryController:
  * slug
  * description
  * sort_order

WDROŻENIE:
1. Rozpakuj do katalogu projektu i nadpisz 2 kontrolery.
2. Wykonaj:
   php artisan optimize:clear
   php artisan test

Nie wykonuj ponownie migracji — nie jest potrzebna.

Jeżeli wszystkie testy przejdą:
git add .
git commit -m "Add article CMS"
git push
