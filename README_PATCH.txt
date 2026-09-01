OKULARY 3D — K89
GLOBAL ADVANCED WYSIWYG

CEL:
Zastąpić bardzo prosty edytor contenteditable jednym wspólnym,
rozbudowanym edytorem WYSIWYG we wszystkich modułach, które już
używają atrybutu data-wysiwyg.

============================================================
ARCHITEKTURA
============================================================

K89 NIE kopiuje osobnych edytorów do:
- Artykułów
- Produktów
- Newslettera
- Stron statycznych

Wszystkie te moduły już używają wspólnego:
resources/js/admin-cms.js
oraz:
resources/css/admin-cms.css

K89 rozbudowuje właśnie ten globalny komponent.

Dzięki temu każde obecne i przyszłe pole:
<div data-wysiwyg>...</div>
automatycznie otrzyma ten sam edytor.

Nie zmieniamy:
- modeli danych,
- nazw pól,
- istniejących body_html / description_html,
- mechanizmu zapisu formularzy.

============================================================
DLACZEGO K89 NIE DODAJE CIĘŻKIEJ BIBLIOTEKI NPM
============================================================

Analizowany @synapxlab/wysiwyg jest bardzo dobrym punktem odniesienia
funkcjonalnego, ale jego aktualny pakiet npm deklaruje zależności m.in.
od React / ReactDOM / Excalidraw / KaTeX.

W naszym wortalu potrzebujemy zaawansowanego rich-text CMS,
a nie pełnego page-buildera / Excalidraw.

Dlatego K89 realizuje potrzebny zakres jako lekki komponent
projektowy bez nowych zależności runtime.

Efekt użytkowy:
zaawansowany edytor podobny do klasycznych WYSIWYG,
ale bez dokładania dużego stosu zależności do całego wortalu.

============================================================
FUNKCJE EDYTORA
============================================================

- Undo / Redo
- Akapit
- H2
- H3
- H4
- Blockquote
- PRE / kod
- Bold
- Italic
- Underline
- Strike
- Superscript
- Subscript
- Lista UL
- Lista OL
- Wcięcie +
- Wcięcie -
- Wyrównanie lewo
- Wyśrodkowanie
- Wyrównanie prawo
- Justowanie
- Kolor tekstu
- Kolor tła tekstu
- Link
- Tabela
- Obraz z URL
- Linia pozioma
- Usuń formatowanie
- Głębokie czyszczenie HTML
- HTML SOURCE
- Fullscreen
- licznik słów
- licznik znaków
- bezpieczne wklejanie HTML z Word/WWW
- responsywny toolbar

============================================================
BIBLIOTEKA MEDIÓW
============================================================

Jeżeli na aktualnym ekranie istnieje:
data-media-picker-modal

edytor automatycznie pokazuje dodatkowy przycisk:
"Wstaw obraz z Biblioteki mediów".

Dotyczy to przede wszystkim edytora artykułów,
gdzie biblioteka mediów jest już dostarczona do formularza.

Kliknięcie obrazu w tym trybie:
- NIE ustawia go jako hero,
- wstawia go do miejsca kursora w treści,
- zamyka picker.

Na ekranach bez istniejącego pickera nadal dostępne jest:
"Wstaw obraz z URL".

============================================================
NEWSLETTER
============================================================

Jeżeli istniejący newsletter jest zablokowany i ma:
contenteditable="false"

K89 rozpoznaje tryb readonly:
- treść pozostaje widoczna,
- toolbar jest disabled,
- edycja nie jest możliwa.

============================================================
HTML SOURCE
============================================================

Przycisk:
</>

przełącza:
WYSIWYG <-> HTML source.

Po powrocie z source HTML jest czyszczony przed ponownym
umieszczeniem w edytorze.

Przy submit zawsze synchronizujemy finalny HTML do istniejącego:
data-editor-output.

============================================================
PASTE CLEAN
============================================================

Wklejenie treści z Worda / strony WWW:
- usuwa script/style/iframe/form itd.,
- usuwa event handlery,
- usuwa class/id,
- ogranicza style do bezpiecznej listy,
- pozostawia semantyczne formatowanie.

============================================================
SANITIZER BACKEND
============================================================

ArticleHtmlSanitizer został rozszerzony, bo stary sanitizer usuwałby
część HTML generowanego przez nowy toolbar.

Nowa lista obsługuje m.in.:
h2/h3/h4
u/s
sup/sub
table/thead/tbody/tfoot/tr/th/td
hr
pre/code
img
span

Dozwolone style:
- text-align
- color
- background-color
- font-size
- font-family
- text-decoration

Nadal blokowane:
- script
- iframe
- object
- embed
- event attributes on*
- javascript:
- data: dla obrazów/linków
- niebezpieczne CSS url()/expression()/behavior
- dowolne niewspierane atrybuty

Link target="_blank" automatycznie otrzymuje:
rel="noopener noreferrer".

============================================================
WAŻNE
============================================================

Nie ma migracji bazy.

Nie ma zmian package.json.

Nie ma nowych paczek npm.

Nie trzeba wykonywać npm install.

============================================================
PLIKI
============================================================

CHANGED:
- resources/js/admin-cms.js
- resources/css/admin-cms.css
- app/Services/ArticleHtmlSanitizer.php

NEW:
- tests/Feature/AdvancedWysiwygTest.php
- README_PATCH.txt

============================================================
INSTALACJA LOKALNA
============================================================

Rozpakuj ZIP do:
C:\laragon\www\okulary-3d

z nadpisaniem plików.

Następnie:

php artisan optimize:clear

php artisan test --filter=AdvancedWysiwygTest
php artisan test --filter=ArticleCmsTest
php artisan test --filter=ShopCatalogTest
php artisan test --filter=NewsletterTest
php artisan test --filter=StaticPageCmsTest

Jeżeli zielono:

php artisan test

Następnie Windows / Laragon:

$env:Path = "C:\laragon\bin\nodejs\node-v22;$env:Path"
node -v
npm -v
npm run build

============================================================
TEST RĘCZNY
============================================================

Sprawdź kolejno:

1. Backend -> Artykuły -> Edytuj
2. Backend -> Produkty -> Edytuj produkt
3. Backend -> Newsletter -> kampania
4. Backend -> Strony statyczne -> Edytuj

W każdym miejscu stary toolbar powinien być zastąpiony
tym samym zaawansowanym edytorem.

Test funkcji:
- H2/H3/H4
- B / I / U / S
- lista
- justowanie
- kolor
- link
- tabela 3x3
- obraz
- source HTML
- fullscreen
- wklejenie treści z Worda
- zapis
- ponowne otwarcie dokumentu

Artykuły:
sprawdź dodatkowo przycisk obrazu z Biblioteki mediów.

Newsletter:
sprawdź istniejącą wysłaną/zablokowaną kampanię - readonly.

============================================================
PO TESTACH
============================================================

git add .
git commit -m "Add global advanced WYSIWYG editor"
git push origin develop
