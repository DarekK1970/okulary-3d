OKULARY 3D — K87.3 FIX
FURGONETKA UNIVERSAL E-COMMERCE INTEGRATION

CEL:
Zastąpić workflow OAuth2 / REST shop->Furgonetka integracją „Własne”,
w której Furgonetka pobiera zamówienia ze sklepu i odsyła numer przesyłki.

PODSTAWA:
https://furgonetka.pl/api/universal-integration-example
https://github.com/heseya/furgonetka-integration

Potwierdzony wzór:
GET  /orders
POST /orders/{id}/tracking_number
Authorization: <TOKEN>
GET /orders: datetime + limit (domyślnie 30)
Callback: tracking.number, HTTP 204.

CO ZMIENIAMY:
- usuwamy z aktywnego workflow Client ID/Secret, OAuth2, access/refresh token,
  /account/services, /packages, /order-commands i etykiety generowane przez sklep;
- Furgonetka sama pobiera zamówienia i obsługuje wybór przewoźnika/nadanie;
- Backend generuje 64-znakowy token integracji i przechowuje go zaszyfrowany;
- stare OAuth sekrety są usuwane po zapisie/generowaniu tokenu;
- Furgonetka Mapa pozostaje niezależnym komponentem checkoutu.

ADMIN -> DOSTAWY -> FURGONETKA.PL:
Nazwa wyświetlana: Okulary3D
Adres sklepu: APP_URL (produkcja https://okulary-3d.pl)
Token: wygenerowany przez Backend
Zaznaczyć w Furgonetka.pl:
[x] Włącz synchronizację zamówień
[x] Wysyłaj informacje o przesyłce

PUBLICZNE API:
GET /orders
POST /orders/{sourceOrderId}/tracking_number

Bez web/session/CSRF. Dedykowany middleware wymaga aktywnej integracji i
Authorization: TOKEN. Bearer TOKEN jest wspierany dodatkowo.

WALUTY:
Universal przykład nie ma pola currency. Dlatego eksportowane są immutable
wartości bazowe PLN: total_base_gross, shipping_base_gross,
base_unit_price_gross. Nie wysyłamy kwoty EUR/GBP/USD bez symbolu waluty.

WAGA:
totalWeight = shipping_weight_grams / 1000.
Odbiór osobisty nie jest eksportowany.

TRACKING:
tracking.number jest wymagany. Opcjonalnie przyjmujemy carrier/service,
url/tracking_url oraz id/shipment_id. Zapis do orders:
shipping_tracking_number, shipping_carrier, shipping_tracking_url,
shipping_external_id, shipping_tracking_updated_at.
Identyczny callback jest idempotentny i nie zmienia statusu zamówienia.
Tracking jest widoczny w Adminie i na koncie klienta.

STARY K87.3:
Nie cofamy migracji 380000. order_shipments i stary widok realizacji mogą
pozostać jako nieaktywne artefakty; routing do nich został usunięty.
FurgonetkaApiService jest oznaczony jako deprecated i blokuje przypadkowe
ponowne użycie OAuth2.

INSTALACJA:
php artisan optimize:clear
php artisan migrate

TESTY:
php artisan test --filter=FurgonetkaIntegrationTest
php artisan test --filter=FurgonetkaMapCheckoutTest
php artisan test --filter=DynamicShippingCheckoutTest
php artisan test --filter=ShippingConfigurationTest
php artisan test --filter=PaymentShippingTest
php artisan test --filter=MultiCurrencyCheckoutTest
php artisan test --filter=ProductionReadinessTest

Jeżeli zielono:
php artisan test
npm run build

TEST LOKALNY ENDPOINTU:
1. Backend -> Produkty -> Dostawy -> Furgonetka.pl.
2. Wygeneruj token integracji.
3. Włącz integrację i zapisz.
4. PowerShell:

$token = "TU_WKLEJ_TOKEN"
Invoke-RestMethod `
  -Uri "http://localhost:8000/orders?limit=30" `
  -Headers @{ Authorization = $token }

Brak tokenu powinien zwrócić 401.

PRODUKCJA:
Furgonetka.pl nie połączy się z localhost. Dopiero po zielonych testach i
wdrożeniu FIX na produkcję wygeneruj osobny PRODUKCYJNY token i wklej go w:
Furgonetka.pl -> Integracje -> Własne.

COMMIT:
git add .
git commit -m "Switch Furgonetka to universal integration"
git push origin develop
