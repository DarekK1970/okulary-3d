OKULARY3D — KROK 84 / FIX ROUTE CACHE CHECK

PRZYCZYNA:
app:release-check zgłaszał:

3 route closure(s) prevent route cache

mimo że rzeczywiste:

php artisan optimize

kończyło etap:

routes ... DONE

Problemem był błędny test w ReleaseReadinessService.

Poprzednia wersja uznawała:

$route->getAction('uses') instanceof Closure

za automatyczny błąd kompatybilności z route cache.

To założenie jest nieprawidłowe dla aktualnego Laravel 13,
który potrafi przygotować obsługiwane route closures
do serializacji.

NAPRAWA:
ReleaseReadinessService nie liczy już Closure.

Dla każdej trasy:
1. tworzy KLON obiektu Route,
2. wywołuje na klonie:
   prepareForSerialization()

To odpowiada rzeczywistej operacji przygotowania trasy
do route cache, a jednocześnie nie modyfikuje aktywnej
kolekcji routingu bieżącego procesu.

Jeżeli którakolwiek trasa faktycznie nie nadaje się
do serializacji, wyjątek zostanie przechwycony
i route_cache nadal otrzyma FAIL.

Jeżeli wszystkie trasy przejdą:
route_cache = OK

ZMIENIONY PLIK:
app/Services/ReleaseReadinessService.php

MIGRACJE:
brak

NPM:
nie trzeba wykonywać npm run build

PO ROZPAKOWANIU:
php artisan optimize:clear

Najpierw:
php artisan test --filter=ProductionReadinessTest

Następnie:
php artisan test

Potem:
php artisan app:release-check

OCZEKIWANE:
route_cache = OK

Na końcu ponownie można sprawdzić realny cache:

php artisan optimize
php artisan about

Po teście lokalnym:
php artisan optimize:clear
