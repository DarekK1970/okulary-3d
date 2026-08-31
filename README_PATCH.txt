OKULARY 3D — K86.3
PRODUCT AI ACTIONS / COMPACT ACTION ICONS

Cel:
1. Dodać w Admin → Produkty akcję "Autouzupełnianie SEO".
2. Dodać bezpośrednią akcję "Autotranslator".
3. Zamienić tekstowe Edytuj/Usuń na kompaktowe ikony.
4. Pełna nazwa każdej akcji jest dostępna jako tooltip `title`
   oraz `aria-label`.

AUTOUZUPEŁNIANIE SEO
- korzysta z tej samej konfiguracji AI co AI Translator,
- obsługuje OpenAI i Gemini,
- analizuje:
  nazwę produktu,
  markę,
  kategorię,
  krótki opis,
  pełny opis,
- generuje:
  seo_title (maks. 70 znaków),
  seo_description (maks. 180 znaków),
- nie wymyśla certyfikatów, parametrów ani kompatybilności,
- NIE NADPISUJE ręcznie uzupełnionych pól:
  uzupełnia wyłącznie brakujące pole/pola,
- jeśli oba pola są kompletne, ikona jest nieaktywna,
- użycie AI jest zapisywane w ai_translation_runs,
  dzięki czemu tokeny pozostają widoczne w istniejącym audycie.

AUTOTRANSLATOR
- wykorzystuje istniejący AiTranslationService,
- dla produktu tłumaczy:
  nazwę,
  krótki opis,
  pełny opis HTML,
  seo_title,
  seo_description,
- wynik zapisywany jest jako Draft,
- przycisk jest aktywny dopiero po uzupełnieniu SEO źródła,
  żeby docelowa wersja otrzymała również przetłumaczone meta pola,
- przycisk jest blokowany, gdy wersja docelowa ma status
  Ready/Source i jest chroniona przez istniejący workflow AI.

AKCJE W KOLUMNIE:
[✨ SEO] [🌐 Translator] [✎ Edycja] [kosz Usuń]
W interfejsie są to wyłącznie ikony; nazwy pojawiają się
po najechaniu kursorem.

NOWE / ZMIENIONE PLIKI:
- app/Services/ProductSeoService.php
- app/Http/Controllers/Admin/AiTranslationController.php
- resources/views/admin/products/index.blade.php
- lang/pl/product_ai.php
- lang/en/product_ai.php
- tests/Feature/ProductAiActionsTest.php
- README_PATCH.txt

NIE MA MIGRACJI BAZY.
NIE MA NOWYCH ROUTES.
Korzystamy z istniejącej:
POST /admin/translations/{type}/{id}

LOKALNIE:
1. Pracuj na develop.
2. Rozpakuj ZIP do katalogu projektu.
3. Wykonaj:

php artisan optimize:clear
php artisan test --filter=ProductAiActionsTest
php artisan test --filter=AiTranslationTest
php artisan test

4. Otwórz:
   /admin/products

5. Dla produktu z opisem PL:
   - kliknij ikonę Autouzupełnianie SEO,
   - sprawdź pola SEO w Edytuj,
   - następnie kliknij Autotranslator,
   - sprawdź wersję EN jako Draft.

UWAGA:
Testy ProductAiActionsTest używają Http::fake(), więc nie zużywają
prawdziwych tokenów API.

COMMIT PO TESTACH:
git add .
git commit -m "Add product AI actions"
git push origin develop
