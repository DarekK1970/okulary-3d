OKULARY 3D — K87.3
FURGONETKA.PL INTEGRATION

Cel:
podłączyć K87.1/K87.2 do oficjalnego REST API Furgonetka.pl
i Furgonetka Mapy.

Zakres:
- OAuth2 authorization_code + refresh_token,
- szyfrowane Client ID / Client Secret / tokeny,
- dane nadawcy i domyślne gabaryty,
- GET /account/services,
- POST /packages/validate (v2),
- POST /packages (v2),
- PUT + GET /order-commands/{uuid},
- GET /packages/{id},
- GET /packages/{id}/tracking,
- GET /packages/{id}/label,
- order_shipments + snapshot API,
- Furgonetka Mapa w checkout,
- zapis point.code/name/type/original_point_id/country_code.

WAŻNE:
Automatyczne tworzenie paczki dla metody parcel_locker jest
celowo zablokowane. Mapa i snapshot punktu działają, ale nie
zgadujemy niepotwierdzonego pola payloadu dla konkretnego
przewoźnika. Po połączeniu realnego konta wykonamy K87.3A
dla konkretnych service_id punktowych dostępnych na koncie.

TEST:
php artisan optimize:clear
php artisan migrate

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

TEST RĘCZNY:
1. Backend -> Produkty -> Dostawy -> Furgonetka.pl.
2. Wpisz Client ID / Client Secret / dane nadawcy.
3. Skopiuj Redirect URI z panelu do aplikacji OAuth2 Furgonetka.
4. Połącz konto i wykonaj test połączenia.
5. Dodaj osobny Furgonetka Map API Key dla domeny.
6. Checkout -> metoda punktowa -> wybierz punkt na mapie.
7. Złóż kontrolowane zamówienie kurierskie.
8. Admin -> Zamówienie -> Furgonetka.pl.
9. Wybierz usługę, utwórz przesyłkę, zamów, pobierz etykietę,
   odśwież tracking.

COMMIT:
git add .
git commit -m "Add Furgonetka shipping integration"
git push origin develop
