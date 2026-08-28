OKULARY3D — KROK 70
Koszyk, checkout i zamówienia

CEL:
Domknięcie podstawowego procesu zakupowego przed integracją
płatności, dostawy i dokumentów sprzedażowych.

ZAKRES FRONTEND:
- aktywny koszyk sesyjny
- licznik produktów w nagłówku
- dodawanie wariantu produktu do koszyka
- zmiana ilości
- usuwanie pozycji
- opróżnienie koszyka
- kontrola dostępności i stanów magazynowych
- blokada mieszania walut PLN/EUR w jednym koszyku
- checkout dla gościa i zalogowanego użytkownika
- dane kontaktowe
- dane rozliczeniowe
- osobny adres dostawy
- uwagi do zamówienia
- ekran potwierdzenia z numerem zamówienia

KONTO KLIENTA:
- /pl/account/orders
- /en/account/orders
- historia zamówień powiązanych z kontem
- szczegóły zamówienia
- pozycje, ceny, status i adres dostawy
- skrót do historii zamówień w nagłówku po zalogowaniu

ZAMÓWIENIA:
Nowe tabele:
- orders
- order_items

Zamówienie zapisuje snapshot:
- nazwy produktu
- nazwy wariantu
- SKU
- ceny
- VAT
- ilości
- waluty

Dzięki temu późniejsza zmiana produktu w katalogu
nie zmienia historycznych danych zamówienia.

MAGAZYN:
- przy złożeniu zamówienia stan śledzonego wariantu jest zmniejszany
- operacja wykonywana jest w transakcji
- przy anulowaniu zamówienia stan jest zwracany
- zwrot następuje maksymalnie jeden raz

STATUSY:
- pending / Nowe
- processing / W realizacji
- shipped / Wysłane
- completed / Zrealizowane
- cancelled / Anulowane

Dozwolone przejścia:
Nowe -> W realizacji
Nowe -> Anulowane
W realizacji -> Wysłane
W realizacji -> Anulowane
Wysłane -> Zrealizowane

Zrealizowane i Anulowane są statusami końcowymi.

ADMIN:
- /admin/orders
- filtrowanie po statusie
- wyszukiwanie po numerze, kliencie i e-mailu
- podgląd danych klienta
- adres rozliczeniowy
- adres dostawy
- pozycje zamówienia
- wartości zamówienia
- workflow statusów

RBAC:
- admin: dostęp
- super_admin: dostęp
- editor: brak dostępu do rejestru zamówień

WAŻNE — KROK 71:
W KROKU 70 NIE konfigurujemy jeszcze:
- operatora płatności
- metod wysyłki
- kosztów wysyłki
- dokumentów sprzedażowych / faktur
- maili transakcyjnych

Dlatego shipping_gross = 0.00, a checkout informuje,
że finalna metoda i koszt dostawy zostaną podłączone w KROKU 71.

WDROŻENIE:
1. Rozpakuj paczkę do:
   C:\laragon\www\okulary-3d

2. Nadpisz wskazane pliki.

3. Wykonaj:
   php artisan optimize:clear
   php artisan migrate
   npm run build
   php artisan test

TEST RĘCZNY:
1. Wejdź:
   http://okulary-3d.test/pl/shop

2. Otwórz aktywny produkt.

3. Wybierz wariant i kliknij:
   Dodaj do koszyka

4. Sprawdź:
   http://okulary-3d.test/pl/cart

5. Zmień ilość.

6. Przejdź do checkoutu.

7. Wypełnij dane i złóż zamówienie.

8. Sprawdź zaplecze:
   http://okulary-3d.test/admin/orders

9. Zalogowany klient:
   http://okulary-3d.test/pl/account/orders

10. W panelu admina zmień status:
    Nowe -> W realizacji

11. Dla testowego drugiego zamówienia sprawdź anulowanie.
    Po anulowaniu stan magazynowy powinien wrócić.

COMMIT PO ZALICZENIU:
git add .
git commit -m "Add shopping cart and checkout"
git push

NASTĘPNY KROK:
KROK 71 — płatności, metody dostawy, workflow płatności,
maile transakcyjne oraz dokumenty sprzedażowe.
