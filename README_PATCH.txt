OKULARY3D — KROK 76
Stereoscopic Archive / History

CEL:
Uruchomienie cyfrowego archiwum historycznej stereoskopii
z naciskiem na źródła, prawa do materiałów i kontekst historyczny.

PUBLICZNE ADRESY:
http://okulary-3d.test/pl/archive
http://okulary-3d.test/en/archive

ADMIN:
http://okulary-3d.test/admin/archive

DOSTĘP ADMIN:
editor / admin / super_admin

PUBLICZNE ARCHIWUM:
- lista historycznych obiektów,
- wyszukiwanie tekstowe,
- filtrowanie po technice,
- filtrowanie po kraju / regionie,
- zakres lat OD / DO,
- sortowanie chronologiczne,
- karta obiektu,
- źródło cyfrowe,
- status prawny,
- metadane historyczne,
- opis,
- rozszerzony kontekst historyczny,
- wersje PL / EN.

OBSŁUGIWANE TECHNIKI:
- karta stereoskopowa,
- fotografia stereoskopowa,
- anaglif,
- View-Master / krążek stereo,
- obraz lentikularny,
- inne.

STATUSY PRAW:
- Public Domain,
- CC0,
- CC BY,
- CC BY-SA,
- publikacja za zgodą właściciela praw.

WAŻNE:
KROK 76 nie pobiera automatycznie materiałów z Internetu.
Admin dodaje tylko materiały, dla których istnieje właściwa
podstawa do publikacji.

METADANE:
- rok OD,
- rok DO,
- ca. / datowanie przybliżone,
- autor / fotograf,
- wydawca,
- kraj / region,
- nazwa kolekcji,
- źródło / instytucja,
- URL źródła,
- status prawny,
- informacja o prawach.

OBRAZY:
Każdy rekord ma:
1. obowiązkowy oryginalny skan / obraz,
2. opcjonalną rozdzieloną parę L / R.

Jeśli istnieje para L/R, publiczny viewer udostępnia:
- Original,
- Parallel,
- Cross-eye,
- Anaglyph red/cyan,
- Wiggle,
- Swap L/R.

Jeżeli L/R nie istnieje:
viewer pokazuje tylko oryginalny skan.

WERSJE JĘZYKOWE:
Model:
archive_items
+
archive_item_translations

Każdy obiekt ma:
source_locale = pl lub en.

Statusy tłumaczenia:
- source
- draft
- review
- ready

Publiczne są:
- source
- ready

Przykład:
source_locale = pl

PL:
Source -> publiczne

EN:
Draft -> NIEPUBLICZNE
Ready -> publiczne

SLUG:
Oddzielny dla PL i EN.

ADMIN:
Można:
- tworzyć rekord,
- edytować rekord,
- publikować / wycofać publikację,
- uzupełniać PL i EN,
- zmieniać status tłumaczenia,
- wymieniać skan,
- dodać / wymienić / usunąć parę L/R,
- usunąć cały rekord.

PLIKI:
storage/app/public/archive/{UUID}/

Np.:
original.jpg
left.jpg
right.jpg

STRONA GŁÓWNA:
Naprawiono istniejące elementy Historia / Archiwum:
- link "Zobacz całe archiwum" prowadzi teraz do /{locale}/archive,
- statyczne karty archiwalne prowadzą do archiwum,
- pozycja "Historia" w głównym menu prowadzi do /{locale}/archive.

BAZA:
Nowe tabele:
- archive_items
- archive_item_translations

WDROŻENIE:
1. Rozpakuj patch do:
   C:\laragon\www\okulary-3d

2. Wykonaj:
   php artisan optimize:clear
   php artisan migrate
   npm run build
   php artisan test

TEST RĘCZNY:
1. Zaloguj się jako Editor/Admin.

2. Otwórz:
   http://okulary-3d.test/admin/archive

3. Dodaj przykładową historyczną kartę:
   - source_locale: PL
   - technika: Karta stereoskopowa
   - rok: np. 1900
   - źródło: wpisz rzeczywiste źródło
   - prawa: wybierz zgodny status
   - dodaj skan
   - uzupełnij tytuł PL
   - zaznacz Publikuj

4. Zapisz i otwórz:
   http://okulary-3d.test/pl/archive

5. Sprawdź:
   - filtrowanie,
   - kartę obiektu,
   - źródło,
   - prawa,
   - metadane.

6. Dodaj do obiektu dwa obrazy L/R.

7. Sprawdź viewer:
   - Original
   - Parallel
   - Cross-eye
   - Anaglyph
   - Wiggle
   - Zamień L/R

8. Dodaj wersję EN:
   - najpierw Draft,
   - sprawdź że EN nie jest publiczne,
   - zmień na Ready,
   - sprawdź /en/archive.

9. Sprawdź stronę główną:
   - Historia w menu,
   - Zobacz całe archiwum,
   - karty sekcji archiwum.

COMMIT PO ZALICZENIU:
git add .
git commit -m "Add stereoscopic archive"
git push

NASTĘPNY KROK:
KROK 77 — AI Translator.
