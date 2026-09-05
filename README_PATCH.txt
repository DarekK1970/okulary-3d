OKULARY 3D — K93
COMPACT HOMEPAGE ARTICLE CARDS

CEL:
Zmienić prezentację najnowszych publikacji na stronie głównej.
Zamiast dużych dominujących zdjęć karta ma wyglądać podobnie
do modułu "Warto wiedzieć więcej" z portalu powiedznie.org:

[ pionowa miniatura ] [ tytuł
                        krótki wstęp
                        Czytaj dalej -> ]

Na desktopie dokładnie 3 publikacje w jednym rzędzie.

============================================================
ZMIANA WIZUALNA
============================================================

PRZED:
- karta pionowa,
- ogromny obraz 16:9 zajmujący większość ekranu,
- treść pod zdjęciem,
- przy małej liczbie publikacji sekcja wygląda jak galeria zdjęć.

PO:
- kompaktowa karta pozioma,
- pionowa miniatura po lewej,
- treść po prawej,
- tytuł jest głównym elementem karty,
- pod tytułem krótki lead,
- CTA "Czytaj dalej ->",
- kategoria + data są niewielkimi informacjami pomocniczymi.

============================================================
DESKTOP
============================================================

home-publications-grid:
3 kolumny

Każda karta:
grid-template-columns:
132px + pozostała szerokość

Zdjęcie:
- zajmuje całą lewą część karty,
- wysokość min. 220 px,
- object-fit: cover,
- dzięki wąskiej kolumnie wizualnie działa jako pionowa miniatura,
  nawet jeżeli źródłowy hero image jest poziomy.

============================================================
TABLET
============================================================

Poniżej 1120 px:
2 publikacje w rzędzie.

============================================================
MOBILE
============================================================

Poniżej 760 px:
1 publikacja w rzędzie.

Nadal pozostaje układ:
miniatura po lewej + treść po prawej.

Dopiero na bardzo wąskich ekranach miniatura zwęża się do 112 px.

============================================================
TREŚĆ
============================================================

Karta pokazuje:

1. kategorię,
2. datę publikacji,
3. tytuł,
4. excerpt,
5. CTA "Czytaj dalej".

Jeżeli excerpt jest pusty:
automatycznie pobierane jest pierwsze 165 znaków z body_html
po usunięciu HTML.

Lead jest ograniczony CSS-em do maksymalnie 4 linii,
aby wszystkie trzy karty zachowywały podobną wysokość.

============================================================
LINKOWANIE
============================================================

Klikalne są:
- zdjęcie,
- tytuł,
- CTA,
- kategoria.

Wszystkie prowadzą do rzeczywistych route:
articles.show
lub:
articles.index?category=...

============================================================
BAZA / BACKEND
============================================================

BRAK migracji.
BRAK zmian backendowych.

HomeController z K91 nadal pobiera dokładnie 3 najnowsze
opublikowane artykuły.

============================================================
PLIKI
============================================================

CHANGED:
- resources/views/home.blade.php
- resources/css/app.css
- lang/pl/articles_public.php
- lang/en/articles_public.php

NEW:
- tests/Feature/HomepageArticleCardsTest.php

PATCH:
- README_PATCH.txt

============================================================
INSTALACJA LOKALNA
============================================================

Rozpakuj z nadpisaniem do:
C:\laragon\www\okulary-3d

php artisan optimize:clear

php artisan test --filter=HomepageArticleCardsTest
php artisan test --filter=HomepageTest
php artisan test --filter=PublicArticleRoutingTest
php artisan test --filter=ProductionReadinessTest

Jeżeli zielono:

php artisan test

Następnie:

$env:Path = "C:\laragon\bin\nodejs\node-v22;$env:Path"
npm run build

============================================================
TEST RĘCZNY
============================================================

Otwórz /pl.

Sekcja:
NAJNOWSZE PUBLIKACJE
Najnowsze w świecie 3D

Na monitorze desktop:
- 3 karty w jednej linii,
- każda karta znacznie niższa niż poprzednio,
- pionowa miniatura po lewej,
- tytuł i lead po prawej,
- widoczny "Czytaj dalej ->".

Sprawdź też szerokości:
~1000 px -> 2 kolumny
telefon -> 1 kolumna

============================================================
PO TESTACH
============================================================

git add .
git commit -m "Redesign homepage article cards"
git push origin develop
