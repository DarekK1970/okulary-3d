# Wortal Okulary 3D — production checklist

Ten plik jest checklistą do KROKU 84. Właściwe pierwsze wdrożenie na Plesk wykonamy w KROKU 85.

## 1. Minimalne wymagania

- PHP 8.3+
- MariaDB / MySQL
- HTTPS z prawidłowym certyfikatem
- document root: `httpdocs/public`
- rozszerzenia PHP wymagane przez Laravel i używane moduły aplikacji
- możliwość uruchamiania Laravel Scheduler z Pleska

## 2. Krytyczne ustawienia produkcyjne

Na produkcji sprawdź co najmniej:

```text
APP_ENV=production
APP_DEBUG=false
APP_URL=https://okulary-3d.pl
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database
```

`APP_KEY` musi być ustawiony i musi pozostać stały. Zmiana `APP_KEY` po zapisaniu sekretów w `app_settings` uniemożliwi ich odszyfrowanie.

Klucze PayNow, OpenAI i Gemini pozostają zarządzane przez panel aplikacji i nie są wpisywane ręcznie do repozytorium.

Mailer produkcyjny nie może używać `log` ani `array`.

## 3. Preflight

Lokalnie:

```bash
php artisan optimize:clear
php artisan migrate
npm run build
php artisan test
php artisan app:release-check
```

Na serwerze przed uruchomieniem ruchu:

```bash
php artisan app:release-check --production
```

Komenda ma zakończyć się kodem `0`.

## 4. Cache produkcyjny

Po finalnej konfiguracji `.env` i migracjach:

```bash
php artisan optimize
```

Polecenie przygotowuje cache konfiguracji, eventów, routingu i widoków. KROK 84 usuwa route-action closures blokujące produkcyjny route cache.

Po każdej zmianie konfiguracji środowiska:

```bash
php artisan optimize:clear
php artisan optimize
```

## 5. Scheduler

W Plesku ustaw jedno zadanie uruchamiane co minutę:

```bash
cd /var/www/vhosts/okulary-3d.pl/httpdocs && php artisan schedule:run
```

Dokładną ścieżkę do interpretera PHP zweryfikujemy w KROKU 85 dla konkretnej konfiguracji Pleska.

Scheduler obsługuje obecnie m.in.:

- publikowanie zaplanowanych artykułów,
- wysyłanie newsletterów,
- czyszczenie anonimowych danych Analytics starszych niż 180 dni.

## 6. Queue

Aplikacja ma przygotowaną kolejkę `database`. Przed produkcją `QUEUE_CONNECTION` nie powinno być `sync`.

Dla Pleska bez Supervisor można zastosować powtarzalne zadanie:

```bash
php artisan queue:work database --queue=default --stop-when-empty --tries=3 --timeout=120 --max-jobs=100
```

Dokładny sposób uruchomienia workerów ustalimy w KROKU 85. Po wdrożeniu nowej wersji kodu wykonuj:

```bash
php artisan queue:restart
```

## 7. Health checks

Liveness:

```text
https://okulary-3d.pl/up
```

Readiness:

```text
https://okulary-3d.pl/health/ready
```

`/health/ready` sprawdza bez ujawniania danych dostępowych:

- DB,
- cache,
- możliwość zapisu do storage.

HTTP 200 = ready.
HTTP 503 = dependency failure.

Health checks nie są zapisywane jako odsłony Portal Analytics.

## 8. Storage

Przed produkcją musi istnieć:

```bash
php artisan storage:link
```

oraz prawa zapisu dla:

```text
storage/
bootstrap/cache/
```

## 9. Nagłówki bezpieczeństwa

KROK 84 dodaje globalnie:

- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN`
- `Referrer-Policy: strict-origin-when-cross-origin`
- ograniczony `Permissions-Policy`
- HSTS w środowisku produkcyjnym HTTPS
- `Cache-Control: no-store` dla stron prywatnych

CSP nie jest jeszcze wymuszany, ponieważ obecne widoki i narzędzia LAB wykorzystują elementy, które przed restrykcyjnym CSP wymagają osobnego audytu nonce/hash.

## 10. Backup przed migracją

Przed każdym produkcyjnym `php artisan migrate --force` wykonaj backup bazy oraz katalogu `storage/app/public`.
