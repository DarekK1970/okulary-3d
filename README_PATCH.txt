OKULARY 3D — K88
STATIC PAGES CMS + WYSIWYG + AUTOMATIC TRANSLATION

CEL:
Dodać zarządzalne strony statyczne portalu i sklepu,
automatyczne tłumaczenie brakujących wersji językowych
oraz podłączyć stopkę do realnych adresów stron.

============================================================
1. NOWA FUNKCJONALNOŚĆ W ADMINIE
============================================================

Nowa pozycja menu:

Strony statyczne

Lista jest podzielona na dwie sekcje:

STRONY STATYCZNE:
- FAQ
- Wysyłka i płatności
- Zwroty i reklamacje
- Polityka prywatności
- Regulamin portalu

SKLEP:
- Regulamin sklepu
- Bezpieczne płatności

Kolumna AKCJE:
- Edytuj
- Automatyczne tłumaczenie
- Podgląd

============================================================
2. EDYTOR WYSIWYG
============================================================

Każda wersja językowa posiada:
- Tytuł
- Treść WYSIWYG
- SEO title
- SEO description

WYSIWYG korzysta z istniejącego admin-cms.js,
czyli dokładnie tego samego mechanizmu co edycja artykułów.

Obsługuje:
P, H2, H3, Bold, Italic, listy, blockquote, link.

HTML przechodzi przez istniejący ArticleHtmlSanitizer.

============================================================
3. AUTOMATYCZNE TŁUMACZENIE
============================================================

Przycisk:
Automatyczne tłumaczenie

działa dla konkretnej strony.

Mechanizm:
- bierze source_locale strony,
- sprawdza wszystkie języki z config/locales.php,
- pomija kompletną wersję,
- tworzy wersję brakującą,
- jeżeli wersja jest częściowo uzupełniona, NIE nadpisuje
  już wpisanych ręcznie pól; uzupełnia tylko braki,
- zapisuje run w ai_translation_runs jako:
  content_type = static_page.

Używa tej samej konfiguracji OpenAI/Gemini,
która już działa w AI Translator.

Dodatkowo AiTranslationProviderService został poprawiony:
nazwy języków nie są już zakodowane tylko dla PL/EN.
Jeśli później dodasz np. DE/FR/ES do config/locales.php,
provider pobierze nazwę języka z konfiguracji.

============================================================
4. PUBLICZNE ADRESY
============================================================

Adres jest stabilny i niezależny od tytułu:

/{locale}/info/{key}

Przykłady:

/pl/info/faq
/pl/info/shipping-payments
/pl/info/returns-complaints
/pl/info/privacy-policy
/pl/info/portal-terms
/pl/info/shop-terms
/pl/info/secure-payments

Dla EN:
/en/info/faq
itd.

Jeżeli lokalna wersja jeszcze nie istnieje,
strona tymczasowo pokazuje wersję źródłową.

Po automatycznym tłumaczeniu od razu pokazuje wersję lokalną.

============================================================
5. SEO
============================================================

Każda strona posiada:
- SEO title
- SEO description
- canonical
- hreflang wszystkich obsługiwanych języków
- x-default
- schema.org WebPage

============================================================
6. STOPKA
============================================================

Sekcja WSPARCIE:

FAQ
-> /{locale}/info/faq

Wysyłka i płatności
-> /{locale}/info/shipping-payments

Zwroty i reklamacje
-> /{locale}/info/returns-complaints

Polityka prywatności
-> /{locale}/info/privacy-policy

Regulamin portalu
-> /{locale}/info/portal-terms


Sekcja SKLEP otrzymuje dodatkowo:

Regulamin sklepu
-> /{locale}/info/shop-terms

Bezpieczne płatności
-> /{locale}/info/secure-payments

============================================================
7. BAZA
============================================================

Nowe tabele:

static_pages
static_page_translations

Migracja automatycznie tworzy siedem wymaganych stron
i ich polskie rekordy źródłowe.

Nie wstawia fikcyjnych treści prawnych.

Do momentu uzupełnienia body użytkownik zobaczy neutralny komunikat:
"Treść tej strony jest w przygotowaniu."

============================================================
8. BEZPIECZEŃSTWO
============================================================

Zarządzanie stronami:
ADMIN + SUPER ADMIN.

Zwykły User:
403.

HTML:
sanityzowany istniejącym ArticleHtmlSanitizer.

Automatyczne tłumaczenie:
nie nadpisuje kompletnej ręcznie przygotowanej wersji.

============================================================
PLIKI
============================================================

NEW:
- database/migrations/2026_09_01_400000_create_static_pages_tables.php
- app/Models/StaticPage.php
- app/Models/StaticPageTranslation.php
- app/Services/StaticPageTranslationService.php
- app/Http/Controllers/Admin/StaticPageController.php
- app/Http/Controllers/StaticPageController.php
- app/Providers/StaticPageServiceProvider.php
- resources/views/admin/static-pages/index.blade.php
- resources/views/admin/static-pages/edit.blade.php
- resources/views/static-pages/show.blade.php
- lang/pl/static_pages.php
- lang/en/static_pages.php
- tests/Feature/StaticPageCmsTest.php

CHANGED:
- bootstrap/providers.php
- app/Services/AiTranslationProviderService.php
- resources/views/admin/layout.blade.php
- resources/views/partials/footer.blade.php

============================================================
INSTALACJA LOKALNA
============================================================

Rozpakuj patch do:
C:\laragon\www\okulary-3d

Następnie:

php artisan optimize:clear
php artisan migrate

============================================================
TESTY
============================================================

php artisan test --filter=StaticPageCmsTest
php artisan test --filter=AiTranslationTest
php artisan test --filter=MultilingualSeoTest

Jeżeli zielono:

php artisan test
npm run build

============================================================
TEST RĘCZNY
============================================================

1. Backend -> Strony statyczne.

2. Sprawdź dwie grupy:
   Strony statyczne
   Sklep

3. Otwórz FAQ -> Edytuj.

4. Sprawdź:
   PL / EN tabs
   WYSIWYG
   SEO title
   SEO description

5. W PL wpisz przykładową treść i zapisz.

6. Wróć do listy i kliknij:
   Automatyczne tłumaczenie

7. EN powinien zostać utworzony automatycznie.

8. Otwórz:
/pl/info/faq
/en/info/faq

9. Sprawdź stopkę:
   każdy link Wsparcie prowadzi do realnej strony,
   a w sekcji Sklep są:
   Regulamin sklepu
   Bezpieczne płatności.

10. Zmień język wortalu:
    linki w stopce muszą zachować bieżący locale.

============================================================
PO TESTACH
============================================================

git add .
git commit -m "Add static pages CMS and AI translations"
git push origin develop
