OKULARY3D — KROK 78 / FIX DISCOVERY FILTER

PRZYCZYNA:
W DiscoveryService zastosowano:

Collection::filter('is_array')

W Laravel 13 callback przekazywany do Collection::filter()
otrzymuje dwa argumenty:
- wartość
- klucz

Natywna funkcja PHP is_array() przyjmuje tylko jeden argument.

Powodowało to:
ArgumentCountError:
is_array() expects exactly 1 argument, 2 given

Błąd występował w normalizeCandidate() podczas:
- filtrowania listy sources,
- filtrowania listy facts.

SKUTEK:
Każde wykonanie Discovery kończyło się wyjątkiem.
Controller wykonywał catch() i back(), dlatego testy oczekujące:
 /admin/discovery
otrzymywały redirect do:
 /

NAPRAWA:
Zastąpiono oba wywołania:

->filter('is_array')

bezpiecznym callbackiem:

->filter(static fn (mixed $value): bool => is_array($value))

ZMIENIONY PLIK:
app/Services/DiscoveryService.php

BAZA:
Brak migracji.

BUILD:
Nie trzeba wykonywać npm run build.

PO ROZPAKOWANIU:
php artisan optimize:clear
php artisan test

Możesz też najpierw uruchomić tylko test modułu:

php artisan test --filter=DiscoveryAgentTest

OCZEKIWANY WYNIK:
DiscoveryAgentTest:
7 passed

Następnie:
pełny zestaw testów powinien przejść bez 3 dotychczasowych błędów.

COMMIT PO ZALICZENIU:
git add .
git commit -m "Fix Discovery Agent collection filtering"
git push
