OKULARY3D — KROK 75
Community Stereo Gallery

CEL:
Uruchomienie moderowanej galerii stereoskopowej tworzonej
przez użytkowników wortalu.

PUBLICZNE ADRESY:
http://okulary-3d.test/pl/gallery
http://okulary-3d.test/en/gallery

DODAWANIE PRACY:
http://okulary-3d.test/pl/gallery/submit

Wymaga zalogowania.

KONTO UŻYTKOWNIKA:
http://okulary-3d.test/pl/account/gallery

ADMIN / MODERACJA:
http://okulary-3d.test/admin/gallery

Dostęp:
editor / admin / super_admin

FUNKCJONALNOŚCI PUBLICZNE:
- lista opublikowanych prac,
- miniatury par L/R,
- szczegóły pracy,
- autor,
- opis,
- licencja,
- data publikacji,
- viewer stereo działający lokalnie w przeglądarce.

TRYBY VIEWERA:
- Parallel,
- Cross-eye,
- Anaglyph czerwono-cyjanowy,
- Wiggle,
- Zamień L / R.

ZGŁASZANIE PRACY:
Zalogowany użytkownik podaje:
- lewy obraz,
- prawy obraz,
- tytuł,
- nazwę autora wyświetlaną publicznie,
- opis,
- licencję,
- potwierdzenie prawa do publikacji.

FORMATY:
- JPG
- PNG
- WEBP

LIMIT:
10 MB na jeden obraz.

LICENCJE:
- Wszelkie prawa zastrzeżone
- CC BY
- CC BY-SA
- CC0

WORKFLOW:
1. User przesyła pracę.
2. Status:
   Oczekuje na moderację.
3. Praca NIE jest publiczna.
4. Editor/Admin/Super Admin otwiera:
   /admin/gallery
5. Moderator wybiera:
   - Oczekuje
   - Opublikowana
   - Odrzucona
6. Może dodać uwagę moderacyjną.
7. Po publikacji praca pojawia się publicznie.

KONTO UŻYTKOWNIKA:
User widzi:
- wszystkie własne zgłoszenia,
- status,
- uwagi moderatora.

Zgłoszenie Pending lub Rejected:
- można usunąć.

Publikacja Published:
- nie może być samodzielnie usunięta przez Usera.
  Chroni to moderowany zasób przed przypadkowym zniknięciem.

PLIKI:
Obrazy zapisywane są na dysku Laravel:
storage/app/public/gallery/{USER_ID}/{UUID}/

Wymagany istniejący symlink:
public/storage -> storage/app/public

Symlink był już przygotowany na wcześniejszym etapie projektu.

STRONA GŁÓWNA:
Sekcja galerii nie zawiera już atrap przycisków:
Parallel / Cross-eye / Anaglyph / Wiggle.

Zamiast tego:
- Otwórz galerię
- Dodaj pracę
lub dla gościa:
- Zaloguj i dodaj

Główna nawigacja "Galeria" prowadzi teraz do:
/{locale}/gallery

BAZA:
Nowa tabela:
stereo_gallery_items

Przechowuje m.in.:
- user_id
- slug
- title
- description
- author_name
- license
- status
- ścieżki L/R
- wymiary obrazów
- rights_confirmed_at
- published_at
- moderator
- moderation_note

WDROŻENIE:
1. Rozpakuj patch do:
   C:\laragon\www\okulary-3d

2. Wykonaj:
   php artisan optimize:clear
   php artisan migrate
   npm run build
   php artisan test

TEST RĘCZNY:
1. Otwórz:
   http://okulary-3d.test/pl/gallery

2. Zaloguj się zwykłym kontem.

3. Kliknij:
   Dodaj własną pracę.

4. Dodaj parę L/R i wyślij.

5. Sprawdź:
   /pl/account/gallery

6. Praca powinna mieć status:
   Oczekuje na moderację.

7. Zaloguj się jako Editor/Admin/Super Admin.

8. Otwórz:
   /admin/gallery

9. Otwórz zgłoszenie i ustaw:
   Opublikowana

10. Wróć do:
    /pl/gallery

11. Otwórz pracę i sprawdź:
    - Parallel
    - Cross-eye
    - Anaglyph
    - Wiggle
    - Zamień L/R

12. Sprawdź stronę główną:
    - przycisk Otwórz galerię
    - główne menu Galeria

COMMIT PO ZALICZENIU:
git add .
git commit -m "Add community stereo gallery"
git push

NASTĘPNY KROK:
KROK 76 — Stereoscopic Archive / History.
