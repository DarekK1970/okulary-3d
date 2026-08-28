OKULARY3D — KROK 77
AI Translator

CEL:
Uruchomienie kontrolowanego workflow tłumaczeń AI PL <-> EN
bez automatycznej publikacji treści.

ADMIN:
http://okulary-3d.test/admin/translations

USTAWIENIA AI — SUPER ADMIN:
http://okulary-3d.test/admin/settings/ai-translation

NAJWAŻNIEJSZA ZASADA:
AI NIGDY nie publikuje tłumaczenia samodzielnie.

Workflow:
SOURCE -> AI -> DRAFT -> ręczna weryfikacja -> READY

Jeżeli wersja docelowa ma już status READY lub SOURCE:
- AI nie może jej nadpisać,
- przycisk generowania jest blokowany,
- aby świadomie wygenerować wersję ponownie, redaktor musi
  najpierw zmienić status istniejącej wersji na DRAFT lub REVIEW.

OBSŁUGIWANE TYPY TREŚCI:
Editor:
- Artykuły
- Archiwum stereoskopii

Admin / Super Admin:
- Artykuły
- Archiwum stereoskopii
- Produkty
- Kategorie produktów

PROVIDERZY:
1. OpenAI
   - Responses API
   - Structured Outputs / JSON Schema

2. Google Gemini
   - GenerateContent API
   - structured JSON output

MODEL:
Nazwa modelu jest polem edytowalnym w panelu.
Domyślne wartości KROKU 77:
- OpenAI: gpt-5.6
- Gemini: gemini-3.7-flash

Nie ma potrzeby modyfikowania .env.

KLUCZE API:
Klucze OpenAI i Gemini są zapisywane w istniejącej tabeli:
app_settings

group:
ai_translation

Klucze są oznaczane jako sekrety i korzystają z istniejącego
encrypted cast modelu AppSetting.

W panelu:
- pole klucza pozostawione puste = zachowaj dotychczasowy klucz,
- można jawnie usunąć zapisany klucz,
- zapisany klucz jest pokazywany wyłącznie w formie maskowanej.

USTAWIENIA:
- włącz / wyłącz translator,
- aktywny provider,
- timeout,
- model OpenAI,
- model Gemini,
- klucz OpenAI,
- klucz Gemini,
- glosariusz projektu.

GLOSARIUSZ:
Admin może wpisać własne reguły terminologiczne, np.:

stereocard = karta stereoskopowa
lenticular lens = soczewka lentikularna
Nie tłumacz nazw modeli urządzeń.

Reguły są dołączane do promptu systemowego.

PROMPT BEZPIECZEŃSTWA TREŚCI:
Translator ma instrukcję, aby:
- zachować znaczenie,
- nie dodawać nowych faktów,
- zachować HTML,
- zachować URL,
- zachować daty,
- zachować jednostki i wartości liczbowe,
- zachować nazwy modeli i kody produktów,
- nie tworzyć dodatkowych twierdzeń marketingowych,
- zachować specjalistyczną terminologię 3D.

HTML:
Dla artykułów i produktów wynik HTML jest ponownie przepuszczany
przez istniejący ArticleHtmlSanitizer przed zapisem.

SLUG:
AI nie generuje sluga bezpośrednio.
Slug jest tworzony lokalnie z przetłumaczonego tytułu/nazwy
oraz sprawdzany pod kątem unikalności.

AUDYT / TOKENY:
Nowa tabela:
ai_translation_runs

Zapisuje:
- typ treści,
- ID treści,
- język źródłowy,
- język docelowy,
- provider,
- model,
- status,
- input tokens,
- output tokens,
- total tokens,
- długość request/response,
- użytkownika uruchamiającego,
- informację o błędzie.

To jest fundament pod późniejszy KROK 79 — Orchestrator,
który będzie analizował wykorzystanie modeli i tokenów.

BŁĘDY API:
Do bazy nie zapisujemy pełnej odpowiedzi błędu providera.
Zapisywany jest kontrolowany komunikat HTTP / parsera,
aby nie utrwalać przypadkowo danych wrażliwych.

BAZA:
Nowa migracja:
2026_08_28_250000_create_ai_translation_runs_table.php

Tabela app_settings już istnieje — nie jest tworzona ponownie.

WDROŻENIE:
1. Rozpakuj patch do:
   C:\laragon\www\okulary-3d

2. Wykonaj:
   php artisan optimize:clear
   php artisan migrate
   npm run build
   php artisan test

TEST RĘCZNY:

1. Zaloguj się jako Super Admin.

2. Otwórz:
   http://okulary-3d.test/admin/settings/ai-translation

3. Wybierz provider.

4. Wpisz:
   - model,
   - API key,
   - opcjonalny glosariusz,
   - zaznacz Włącz AI Translator.

5. Zapisz.

6. Otwórz:
   http://okulary-3d.test/admin/translations

7. Wybierz Artykuły.

8. Przy artykule posiadającym tylko wersję źródłową kliknij:
   Tłumacz AI

9. Sprawdź:
   - pojawia się wersja docelowa,
   - status = Draft,
   - slug został utworzony,
   - tekst i HTML znajdują się w formularzu artykułu.

10. Ręcznie sprawdź tłumaczenie.

11. Dopiero po weryfikacji ustaw status:
    Ready

12. Wróć do AI Translator.
    Dla wersji Ready przycisk AI powinien być zablokowany.

13. Sprawdź analogicznie:
    - Archiwum,
    - Produkty,
    - Kategorie produktów.

14. Przełącz provider na drugi i sprawdź pojedyncze tłumaczenie.

15. Na dole /admin/translations sprawdź historię:
    - provider,
    - model,
    - tokeny,
    - status.

COMMIT PO ZALICZENIU:
git add .
git commit -m "Add AI translation workflow"
git push

NASTĘPNY KROK:
KROK 78 — Discovery Agent.
