OKULARY3D — KROK 68
Biblioteka mediów

ZAKRES:
- nowa tabela media_assets
- centralny moduł /admin/media
- upload do 10 obrazów jednocześnie
- JPG / PNG / WEBP
- limit 5 MB na plik
- foldery / kolekcje
- metadane:
  * tytuł
  * ALT
  * podpis
  * typ MIME
  * rozmiar
  * szerokość / wysokość
  * uploader
- filtrowanie i wyszukiwanie
- podgląd w gridzie
- edycja metadanych
- fizyczne usuwanie nieużywanych plików
- blokada usunięcia pliku używanego przez artykuł

INTEGRACJA Z CMS:
- articles.hero_media_id
- wybór zdjęcia z biblioteki w edytorze artykułu
- wizualny modal z ostatnimi 100 zasobami
- wyszukiwarka wewnątrz selektora
- nowy upload wykonany z formularza artykułu automatycznie trafia do biblioteki
- media są współdzielone i NIE są usuwane razem z artykułem

ZGODNOŚĆ WSTECZ:
- hero_image_path pozostaje jako snapshot ścieżki
- publiczny artykuł używa hero_media, z fallbackiem do hero_image_path

MIGRACJA ISTNIEJĄCYCH ZDJĘĆ:
- istniejące hero_image_path z KROKU 66/67 są automatycznie rejestrowane
  w media_assets
- pliki NIE są kopiowane i NIE są przenoszone
- artykuł dostaje hero_media_id wskazujące nowy rekord biblioteki
- takie pliki trafiają do folderu: legacy-articles

WDROŻENIE:
1. Rozpakuj paczkę do:
   C:\laragon\www\okulary-3d
   ze zgodą na nadpisanie.

2. Wykonaj:
   php artisan optimize:clear
   php artisan migrate
   npm run build
   php artisan test

3. Sprawdź:
   http://okulary-3d.test/admin/media

4. Prześlij 2-3 testowe obrazy.
5. Edytuj ich:
   - tytuł
   - ALT
   - folder

6. Wejdź:
   http://okulary-3d.test/admin/articles

7. Edytuj artykuł i użyj:
   [Wybierz z biblioteki]

8. Zapisz artykuł i sprawdź publiczny podgląd.

COMMIT PO ZALICZENIU:
git add .
git commit -m "Add media library"
git push

NASTĘPNY KROK:
KROK 69 — katalog sklepu:
kategorie, produkty, warianty, ceny, stany magazynowe,
zdjęcia z biblioteki mediów i wielojęzyczne opisy PL/EN.
