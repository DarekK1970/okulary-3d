OKULARY 3D — K91
PUBLIC ARTICLES ROUTING + DYNAMIC HOMEPAGE

CEL:
Naprawić publiczny routing artykułów i usunąć atrapę artykułów
ze strony głównej.

DIAGNOZA PRODUKCJI:
- bezpośredni artykuł /pl/articles/{slug} działa,
- /pl/articles nie ma route i zwraca 404,
- menu "Artykuły" prowadzi do /pl#articles,
- "Zobacz wszystkie artykuły" prowadzi do #,
- karty na stronie głównej są wpisane na sztywno z lang/home.php,
- strzałki kart prowadzą do #,
- dlatego opublikowany przez Orchestrator artykuł istnieje,
  ale nie pojawia się automatycznie w sekcji publikacji.

============================================================
PO K91
============================================================

Nowy publiczny route:

/{locale}/articles

Nazwa:
articles.index

Przykłady:
/pl/articles
/en/articles

Route szczegółu pozostaje:
/{locale}/articles/{slug}

============================================================
STRONA GŁÓWNA
============================================================

Sekcja "Najnowsze w świecie 3D" nie używa już atrap:

Historia stereoskopii w pigułce
Jak zrobić zdjęcie 3D smartfonem?
Spatial Photo i nowa era 3D

Zamiast tego HomeController pobiera 3 najnowsze PRAWDZIWE artykuły:

- status = published,
- published_at <= teraz,
- istnieje publiczna wersja dla aktualnego języka,
- kolejność: najnowsza data publikacji.

Karty pokazują:
- hero image z Media Library / legacy storage,
- kategorię,
- datę,
- tytuł,
- excerpt,
- wyliczony czas czytania,
- realny link do artykułu.

Jeżeli Orchestrator opublikuje nowy artykuł:
pojawia się automatycznie na stronie głównej bez edycji Blade/lang.

============================================================
PUBLICZNA LISTA ARTYKUŁÓW
============================================================

Nowa strona:
Backend content -> public:
https://okulary-3d.pl/pl/articles

Funkcje:
- wszystkie opublikowane artykuły,
- tylko aktualny locale,
- paginacja 12,
- sortowanie od najnowszych,
- filtr kategorii,
- wyszukiwarka tytuł/excerpt/body,
- realne hero images,
- czas czytania,
- linki do szczegółów.

Parametry:
?category=historia-3d
?q=vistascreen

Filtry/search otrzymują noindex,follow przez istniejący SeoService.
Czysty /pl/articles jest index,follow.

============================================================
NAWIGACJA
============================================================

HEADER:
Artykuły
PRZED:
  /pl#articles
PO:
  /pl/articles

Na liście i szczególe route articles.* link jest aktywny.

STOPKA:
Artykuły -> /pl/articles

Przy okazji usunięta została niespójność:
Historia 3D w stopce -> /pl/archive
zamiast /pl#history.

============================================================
SZCZEGÓŁ ARTYKUŁU
============================================================

Breadcrumb:
Strona główna
> Artykuły
> Kategoria

Kategoria w breadcrumbs jest klikalna i otwiera:
articles.index?category=...

"Wróć do artykułów":
PRZED -> /pl#articles
PO -> /pl/articles

Hero:
obsługuje:
1. heroMedia
2. legacy hero_image_path

============================================================
SEO
============================================================

articles.index dodano do:
config/seo.php -> indexable_routes
config/seo.php -> sitemap_static_routes

Dzięki temu:
/pl/articles
/en/articles

otrzymują canonical/hreflang i trafiają do sitemap.xml.

============================================================
BAZA
============================================================

BRAK NOWEJ MIGRACJI.

K91 korzysta z istniejących:
articles
article_translations
article_categories
media_assets

============================================================
PLIKI
============================================================

NEW:
- resources/views/articles/index.blade.php
- tests/Feature/PublicArticleRoutingTest.php

CHANGED:
- routes/web.php
- app/Http/Controllers/HomeController.php
- app/Http/Controllers/ArticleController.php
- resources/views/home.blade.php
- resources/views/articles/show.blade.php
- resources/views/partials/header.blade.php
- resources/views/partials/footer.blade.php
- resources/css/article.css
- lang/pl/articles_public.php
- lang/en/articles_public.php
- config/seo.php
- tests/Feature/HomepageTest.php

============================================================
INSTALACJA LOKALNA
============================================================

Rozpakuj do:
C:\laragon\www\okulary-3d

z nadpisaniem.

Nie ma migracji.

php artisan optimize:clear

TESTY:

php artisan test --filter=PublicArticleRoutingTest
php artisan test --filter=MultilingualArticleTest
php artisan test --filter=HomepageTest
php artisan test --filter=MultilingualSeoTest
php artisan test --filter=ProductionReadinessTest

Jeżeli zielono:

php artisan test

Następnie:

$env:Path = "C:\laragon\bin\nodejs\node-v22;$env:Path"
npm run build

============================================================
TEST RĘCZNY
============================================================

1. Otwórz:
/pl

W "Najnowsze w świecie 3D" powinien pojawić się najnowszy
rzeczywiście opublikowany artykuł, a nie demo z 28.08.2026.

2. Kliknij:
Artykuły

Powinno otworzyć:
/pl/articles

3. Kliknij kartę / tytuł / strzałkę.
Powinno otworzyć:
/pl/articles/{realny-slug}

4. Kliknij kategorię artykułu.
Powinno otworzyć:
/pl/articles?category={slug-kategorii}

5. Na szczególe kliknij:
Wróć do artykułów

Powinno wrócić do:
/pl/articles

6. Przełącz PL/EN na liście artykułów.
Powinno przejść:
/pl/articles <-> /en/articles

============================================================
PO TESTACH
============================================================

git add .
git commit -m "Fix public article routing and dynamic homepage"
git push origin develop
