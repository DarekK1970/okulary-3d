OKULARY 3D — K92
ARTICLE LIST AI ACTIONS + ICONS

CEL:
Ujednolicić kolumnę AKCJE na liście artykułów z listą produktów
oraz dodać szybkie operacje AI bez otwierania edytora publikacji.

============================================================
NOWA KOLEJNOŚĆ AKCJI
============================================================

W Backend -> Artykuły:

1. [ikona ołówka]
   EDYTUJ

2. [ikona globu]
   AUTOMATYCZNA TRANSLACJA

3. [ikona obrazu + sparkle]
   WYGENERUJ OBRAZ

   Ta ikona pojawia się TYLKO wtedy, gdy publikacja nie ma:
   - hero_media_id
   ORAZ
   - hero_image_path

4. [ikona oka]
   PODGLĄD

5. [ikona kosza]
   USUŃ

Przyciski nie zawierają tekstowych etykiet.
Nazwy funkcji są dostępne jako:
- title
- aria-label

czyli są widoczne jako tooltip po najechaniu i dostępne dla
czytników ekranu.

============================================================
AUTOMATYCZNA TRANSLACJA
============================================================

Nie powielamy translatora.

K92 wykorzystuje istniejący:
AiTranslationService::TYPE_ARTICLE

oraz istniejący endpoint:
admin.translations.translate

Czyli translacja artykułu korzysta z tej samej konfiguracji
OpenAI/Gemini i tego samego rejestru ai_translation_runs.

Dla obecnej konfiguracji PL/EN:
- PL -> EN
lub
- EN -> PL

Jeżeli docelowa translacja ma status publicznie gotowy:
przycisk jest disabled, identycznie jak w produktach.

Draft można wygenerować ponownie.

============================================================
GENEROWANIE OBRAZU
============================================================

Nowy endpoint:

POST /admin/articles/{article}/generate-image

Nowy serwis:
ArticleAiImageService

Generator wykorzystuje istniejący klucz:
ai_translation / openai.api_key

Domyślny model:
gpt-image-2

Można go później zmienić ustawieniem:
ai_translation / openai.image_model

bez zmiany kodu.

Parametry obrazu:
- size: 1536x1024
- quality: medium
- 1 obraz
- wynik base64 z Image API

Endpoint OpenAI:
POST https://api.openai.com/v1/images/generations

============================================================
PROMPT OBRAZU
============================================================

Prompt powstaje automatycznie z:
- tytułu źródłowego,
- excerpt,
- treści artykułu po usunięciu HTML.

Instrukcja wymusza:
- poziomy hero image,
- profesjonalny charakter redakcyjny,
- zgodność z tematyką stereoskopii / 3D / optyki,
- brak tekstu,
- brak logo,
- brak watermarków,
- brak przypadkowych dekoracyjnych okularów 3D,
- historyczną wiarygodność przy tematach historycznych.

============================================================
MEDIA LIBRARY
============================================================

Wygenerowany obraz:
1. jest walidowany jako prawdziwy plik graficzny,
2. trafia do Storage public,
3. otrzymuje rekord MediaAsset,
4. folder:
   article-heroes-ai
5. zostaje przypisany:
   article.hero_media_id
   article.hero_image_path

Czyli jest od razu:
- obrazem głównym artykułu,
- widoczny w Bibliotece mediów,
- używany na stronie głównej,
- używany na liście artykułów,
- używany na szczególe publikacji.

============================================================
OCHRONA PRZED NADPISANIEM
============================================================

Warunek jest kontrolowany DWUKROTNIE:

1. GUI:
   przycisk "Wygeneruj obraz" nie istnieje, jeżeli artykuł ma obraz.

2. Backend:
   serwis odrzuca próbę generowania, gdy istnieje:
   hero_media_id
   lub hero_image_path.

Po zakończeniu długiego requestu OpenAI serwis sprawdza artykuł
ponownie przed przypisaniem grafiki.

Jeśli drugi administrator w międzyczasie ręcznie doda obraz:
wygenerowany plik jest usuwany i istniejący obraz NIE jest
nadpisywany.

============================================================
KOSZTY / REJESTR AI
============================================================

Generowanie obrazu zapisuje rekord w:
ai_translation_runs

content_type:
article_image

provider:
openai

model:
gpt-image-2

Zapisywane są również tokeny usage, jeżeli OpenAI je zwróci.

Nie zapisujemy ogromnego base64 obrazu do bazy.

============================================================
OPENAI IMAGE API
============================================================

Implementacja jest zgodna z aktualną dokumentacją OpenAI Image API:
- pojedynczy prompt -> Image API / generations,
- gpt-image-2,
- odpowiedź data[0].b64_json,
- landscape 1536x1024,
- medium quality.

============================================================
PLIKI
============================================================

NEW:
- app/Services/ArticleAiImageService.php
- app/Http/Controllers/Admin/ArticleAiController.php
- lang/pl/article_ai.php
- lang/en/article_ai.php
- tests/Feature/ArticleAiActionsTest.php

CHANGED:
- routes/web.php
- app/Services/MediaAssetService.php
- resources/views/admin/articles/index.blade.php
- resources/css/admin-cms.css

============================================================
BAZA
============================================================

BRAK NOWEJ MIGRACJI.

Korzystamy z istniejących:
- media_assets
- articles.hero_media_id
- ai_translation_runs

============================================================
INSTALACJA LOKALNA
============================================================

Rozpakuj z nadpisaniem do:
C:\laragon\www\okulary-3d

Następnie:

php artisan optimize:clear

TESTY:

php artisan test --filter=ArticleAiActionsTest
php artisan test --filter=AiTranslationTest
php artisan test --filter=ArticleCmsTest
php artisan test --filter=MediaLibraryTest
php artisan test --filter=PublicArticleRoutingTest

Jeżeli zielono:

php artisan test

Następnie:

$env:Path = "C:\laragon\bin\nodejs\node-v22;$env:Path"
npm run build

============================================================
TEST RĘCZNY
============================================================

Backend -> Artykuły

Sprawdź kolejność ikon:

[ołówek]
[translator]
[obraz AI - tylko bez obrazu]
[oko]
[kosz]

Najedź kursorem:
każda ikona musi mieć właściwy tooltip.

TRANSLATOR:
kliknięcie globu przy artykule PL powinno stworzyć/odświeżyć
draft EN.

OBRAZ:
wybierz artykuł bez hero image.
Kliknij ikonę obrazu.
Po generowaniu:
- obraz pojawia się w wierszu artykułu,
- ikona generowania obrazu znika,
- obraz pojawia się w Bibliotece mediów,
- jest przypisany jako hero.

Artykuł z istniejącym obrazem:
NIE może mieć ikony generowania obrazu.

PODGLĄD:
dla publikacji opublikowanej otwiera source locale w nowej karcie.
Dla draft/scheduled ikona oka jest disabled.

============================================================
PO TESTACH
============================================================

git add .
git commit -m "Add AI actions to article list"
git push origin develop
