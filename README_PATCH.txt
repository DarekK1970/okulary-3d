OKULARY3D — KROK 67 — FIX LEGACY TESTS

PRZYCZYNA:
KROK 67 zmienił strukturę formularza artykułu z płaskiej:

title
slug
excerpt
body_html

na wielojęzyczną:

source_locale
translations[pl][title]
translations[pl][slug]
translations[pl][excerpt]
translations[pl][body_html]
translations[en][...]

Nowe testy MultilingualArticleTest już używały poprawnego formatu.
Dwa starsze testy ArticleCmsTest z KROKU 66 nadal wysyłały stary payload.

POPRAWKA:
Zmieniono tylko:
tests/Feature/ArticleCmsTest.php

Test uploadu hero image nadal sprawdza:
- utworzenie artykułu
- automatyczny slug
- status draft
- zapis hero image
- utworzenie polskiej wersji source

Test sanitizacji nadal sprawdza:
- usunięcie <script>
- usunięcie onclick
- zachowanie dozwolonego <p>
- sanitizację article_translations.body_html
- sanitizację legacy articles.body_html

WDROŻENIE:
1. Rozpakuj do katalogu projektu.
2. Nadpisz:
   tests/Feature/ArticleCmsTest.php

3. Wykonaj:
   php artisan optimize:clear
   php artisan test

Nie uruchamiaj ponownie migracji.

Jeżeli wszystkie testy przejdą:
git add .
git commit -m "Add multilingual content model"
git push
