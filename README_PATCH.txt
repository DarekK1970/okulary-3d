OKULARY3D — KROK 72
3D LAB v1 — Anaglyph Maker + Stereo Alignment / Converter

CEL:
Uruchomienie pierwszych realnych narzędzi stereoskopowych wortalu.

NOWE ADRESY:
http://okulary-3d.test/pl/lab
http://okulary-3d.test/pl/lab/anaglyph-maker
http://okulary-3d.test/pl/lab/stereo-alignment

Odpowiedniki EN:
http://okulary-3d.test/en/lab
http://okulary-3d.test/en/lab/anaglyph-maker
http://okulary-3d.test/en/lab/stereo-alignment

ARCHITEKTURA:
- przetwarzanie wykonywane jest wyłącznie w przeglądarce,
- Canvas API,
- obrazy NIE są wysyłane na serwer,
- brak nowych tabel bazy danych,
- brak migracji,
- brak backendowego przechowywania zdjęć,
- eksport przez wygenerowanie lokalnego pliku PNG.

ANAGLYPH MAKER:
1. Wczytanie lewego zdjęcia.
2. Wczytanie prawego zdjęcia.
3. Drag & drop lub wybór pliku.
4. Zamiana L/R.
5. Korekcja geometrii prawego obrazu:
   - X od -150 do +150 px,
   - Y od -100 do +100 px,
   - skala 92–108%,
   - rotacja -3 do +3 stopni.
6. Tryby anaglifu:
   - Color,
   - Half-color,
   - Gray,
   - Optimized.
7. Podgląd na żywo.
8. Eksport PNG:
   - 1200 px,
   - 2400 px,
   - 4096 px,
   - rozdzielczość źródłowa.

STEREO ALIGNMENT / CONVERTER:
Obsługuje podgląd:
- Parallel,
- Cross-eye,
- Anaglyph,
- Overlay 50%,
- Blink L/R.

Geometria jest wspólna dla wszystkich trybów,
więc można np.:
1. wyrównać zdjęcia w Overlay,
2. sprawdzić pion w Blink,
3. zobaczyć efekt w Anaglyph,
4. przełączyć na Parallel,
bez utraty ustawień.

EKSPORT:
- Anaglyph -> pojedynczy obraz PNG,
- Parallel -> para L | R,
- Cross-eye -> para R | L,
- Overlay/Blink -> finalna para side-by-side.

PRYWATNOŚĆ:
Zdjęcia użytkownika pozostają lokalnie w przeglądarce.
Aplikacja nie wykonuje POST/UPLOAD zdjęć do Laravel.

NAV:
Pozycja "3D LAB" w głównym menu prowadzi teraz do:
/{locale}/lab

TESTY:
Dodano:
tests/Feature/LabToolsTest.php

WDROŻENIE:
1. Rozpakuj patch do:
   C:\laragon\www\okulary-3d

2. Wykonaj:
   php artisan optimize:clear
   npm run build
   php artisan test

UWAGA:
Nie ma migracji w KROKU 72, dlatego php artisan migrate
nie jest wymagane.

TEST RĘCZNY:
1. Otwórz:
   http://okulary-3d.test/pl/lab

2. Anaglyph Maker:
   - wczytaj dwa zdjęcia,
   - przesuń suwak Y,
   - przesuń X,
   - sprawdź Color / Gray / Half-color / Optimized,
   - kliknij Zamień L/R,
   - wyeksportuj PNG.

3. Stereo Alignment:
   - wczytaj tę samą parę,
   - sprawdź Parallel,
   - Cross-eye,
   - Anaglyph,
   - Overlay,
   - Blink,
   - dokonaj korekcji geometrii,
   - wyeksportuj wynik.

4. Sprawdź wersję EN.

COMMIT PO ZALICZENIU:
git add .
git commit -m "Add Stereo 3D Lab tools"
git push

NASTĘPNY KROK:
KROK 73 — Lenticular LAB v1:
- Lenticular Interlacer,
- Pitch Test Generator,
- Lenticular Calculator,
- przygotowanie workflow pod A4 Lenticular Wizard 60 LPI.
