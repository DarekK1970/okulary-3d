OKULARY3D — KROK 71
Płatności, dostawa, maile transakcyjne i dokument zamówienia

CEL:
Rozszerzenie KROKU 70 o pełniejszy workflow sprzedażowy:
- wybór dostawy,
- koszt dostawy,
- wybór płatności,
- PayNow,
- status płatności,
- maile transakcyjne,
- drukowalne potwierdzenie zamówienia.

DOSTAWA:
Domyślnie dostępne:
1. Kurier
   PLN 18,99
   EUR 4,50

2. Paczkomat / punkt odbioru
   PLN 16,99
   EUR 4,10
   wymaga podania identyfikatora punktu

3. Odbiór osobisty
   PLN/EUR 0,00

Konfiguracja:
config/shop.php

PŁATNOŚCI:
1. Przelew tradycyjny
   - działa bez dodatkowych kluczy
   - admin może ręcznie oznaczyć wpłatę
   - po oznaczeniu wpłaty klient otrzymuje e-mail

2. PayNow
   - implementacja API v3
   - domyślnie wyłączone
   - środowisko Sandbox / Production
   - HMAC SHA256 Signature
   - Idempotency-Key zapisany w bazie
   - bezpieczne ponowienie żądania po timeout
   - redirect do PayNow
   - callback / notification
   - weryfikacja podpisu callbacku
   - obsługa statusów:
     NEW/PENDING -> payment pending
     CONFIRMED -> paid
     ABANDONED/ERROR/EXPIRED/REJECTED -> failed
   - duplikaty i starsze callbacki nie cofają płatności PAID

PAYNOW — ZMIENNE .env:
PAYNOW_ENABLED=false
PAYNOW_SANDBOX=true
PAYNOW_API_KEY=
PAYNOW_SIGNATURE_KEY=
PAYNOW_TIMEOUT=15

Po otrzymaniu kluczy Sandbox ustaw:
PAYNOW_ENABLED=true

Notification URL do ustawienia w panelu PayNow:
http(s)://TWOJA-DOMENA/payments/paynow/notification

W środowisku produkcyjnym:
PAYNOW_SANDBOX=false

UWAGA:
Nie wpisuj kluczy PayNow do repozytorium.
Klucze pozostają wyłącznie w .env.

PRZELEW TRADYCYJNY — ZMIENNE .env:
SHOP_BANK_RECIPIENT=
SHOP_BANK_NAME=
SHOP_BANK_ACCOUNT=
SHOP_BANK_SWIFT=

DANE SPRZEDAWCY:
SHOP_SELLER_NAME="Wortal Okulary 3D"
SHOP_SELLER_ADDRESS=
SHOP_SELLER_TAX_ID=
SHOP_SELLER_EMAIL=

WORKFLOW ZAMÓWIENIA:
Nowe zamówienie:
- stan realizacji: Nowe
- stan płatności:
  przelew -> Nieopłacone
  PayNow -> Płatność w toku

Rozpoczęcie realizacji jest możliwe dopiero po opłaceniu.

Dozwolone przejścia pozostają:
Nowe -> W realizacji
Nowe -> Anulowane
W realizacji -> Wysłane
W realizacji -> Anulowane
Wysłane -> Zrealizowane

Dodatkowe zabezpieczenia:
- nie można rozpocząć realizacji nieopłaconego zamówienia,
- nie można anulować opłaconego zamówienia bez mechanizmu refund,
- nie można anulować zamówienia, gdy PayNow ma status Pending,
- anulowanie nieopłaconego zamówienia nadal zwraca stan magazynowy.

MAILE:
Wysyłane są:
- potwierdzenie przyjęcia zamówienia,
- potwierdzenie płatności,
- informacja o wysłaniu zamówienia.

Błąd SMTP nie przerywa checkoutu.
Błąd zostaje zgłoszony do logów aplikacji.

LOKALNIE:
Przy obecnym MAIL_HOST=127.0.0.1 i MAIL_PORT=1025
wiadomości można sprawdzać w lokalnym catcherze poczty / Mailpit / MailHog,
jeżeli jest uruchomiony.

DOKUMENT SPRZEDAŻOWY:
Automatycznie tworzony jest:
"Potwierdzenie zamówienia"

Tabela:
sales_documents

Numer:
PZ/ROK/000001

Dokument zapisuje snapshot:
- nabywcy,
- adresu,
- wartości produktów,
- dostawy,
- kwoty całkowitej.

Można go:
- otworzyć z potwierdzenia zamówienia,
- otworzyć z konta klienta,
- wydrukować z panelu admina.

WAŻNE:
Potwierdzenie zamówienia NIE jest fakturą VAT ani paragonem fiskalnym.
Docelowa faktura / dokument fiskalny może zostać wdrożony później
zgodnie z wybranym modelem sprzedaży i księgowości.

MIGRACJA:
Rozszerza orders o:
- shipping_method
- shipping_name_snapshot
- shipping_point
- payment_method
- payment_status
- payment_merchant_external_id
- payment_idempotency_key
- payment_external_id
- payment_redirect_url
- payment_error
- paid_at
- payment_failed_at

Dodaje:
sales_documents

WDROŻENIE:
1. Rozpakuj patch do:
C:\laragon\www\okulary-3d

2. Wykonaj:
php artisan optimize:clear
php artisan migrate
npm run build
php artisan test

TEST RĘCZNY — PRZELEW:
1. Otwórz produkt.
2. Dodaj do koszyka.
3. Checkout.
4. Wybierz:
   - Kurier
   - Przelew tradycyjny
5. Złóż zamówienie.
6. Sprawdź koszt dostawy i dokument.
7. Wejdź:
   /admin/orders
8. Oznacz wpłatę jako opłaconą.
9. Zmień:
   Nowe -> W realizacji
10. Następnie:
   W realizacji -> Wysłane

TEST RĘCZNY — PACZKOMAT:
Wybór Paczkomatu / punktu odbioru wymaga wpisania identyfikatora punktu.

TEST PAYNOW:
Dopiero po skonfigurowaniu własnych danych Sandbox w .env:
PAYNOW_ENABLED=true
PAYNOW_SANDBOX=true
PAYNOW_API_KEY=...
PAYNOW_SIGNATURE_KEY=...

Następnie ustaw Notification URL w panelu Sandbox.

COMMIT PO ZALICZENIU:
git add .
git commit -m "Add payments and order workflow"
git push

NASTĘPNY KROK:
KROK 72 — 3D LAB v1:
- Anaglyph Maker
- Stereo Alignment / Converter
- podstawowy workflow plików stereo
- zapis i eksport wyniku.
