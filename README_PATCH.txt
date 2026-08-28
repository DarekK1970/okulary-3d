OKULARY3D — KROK 71A FIX
Aktualizacja testu dostępu do ustawień

PRZYCZYNA:
KROK 71A zastąpił placeholder "Ustawienia systemowe"
realnym ekranem "Ustawienia sklepu i płatności".

Aplikacja poprawnie zwraca HTTP 200 dla super_admin.
Błąd dotyczył wyłącznie starej asercji w AdminAccessTest.

ZMIANA:
tests/Feature/AdminAccessTest.php
- oczekiwany tekst:
  Ustawienia systemowe
- zmieniono na:
  Ustawienia sklepu i płatności

PO ROZPAKOWANIU:
php artisan test

Jeżeli wszystkie testy przejdą, KROK 71A można uznać za zakończony.
