OKULARY 3D — K90
BOT TRAFFIC ANALYTICS / HUMAN TRAFFIC SEPARATION

CEL:
Dodać do Portal Analytics osobną analizę ruchu botów internetowych
oraz zagwarantować, że rozpoznane crawlery NIE zwiększają:
- odsłon użytkowników,
- unikalnych wizyt,
- aktywnych użytkowników,
- źródeł ruchu,
- urządzeń,
- lejka,
- eventów użytkowników.

Rozwiązanie jest wzorowane funkcjonalnie na panelu wdrożonym
wcześniej w projekcie powiedznie.org.

============================================================
NAJWAŻNIEJSZA ZMIANA ARCHITEKTONICZNA
============================================================

PRZED K90:

Request
  -> prosty regex "czy bot?"
  -> jeżeli rozpoznany: ignoruj
  -> jeżeli nierozpoznany: może trafić do sesji użytkownika

Problem:
prosty regex rozpoznawał podstawowe boty, ale nie dawał:
- osobnej statystyki,
- klasyfikacji,
- listy najaktywniejszych botów,
- listy crawlowanych URL,
- szerokiego katalogu agentów AI / SEO / social / monitoring.

PO K90:

Request
  -> BotDetectorService
       |
       +-- BOT
       |    -> portal_analytics_bot_requests
       |    -> STOP dla human analytics
       |
       +-- HUMAN
            -> portal_analytics_sessions
            -> portal_analytics_page_views
            -> portal_analytics_events

Bot i User nie współdzielą licznika.

============================================================
DETEKCJA
============================================================

Kategorie:

1. search_engine
   Googlebot
   Bingbot
   DuckDuckBot
   Applebot
   YandexBot
   Baiduspider
   Yahoo Slurp
   PetalBot
   Qwantify
   Sogou
   SeznamBot

2. ai
   OAI-SearchBot
   ChatGPT-User
   GPTBot
   PerplexityBot
   ClaudeBot
   CohereBot
   Meta-ExternalAgent
   Applebot-Extended
   Amazonbot
   Bytespider
   CCBot
   YouBot
   Google-Extended

3. seo
   SerpstatBot
   AhrefsBot
   SemrushBot
   MJ12bot
   DotBot
   BLEXBot
   DataForSeoBot
   SeobilityBot
   Screaming Frog
   SiteAuditBot
   Rogerbot
   MegaIndex

4. social_preview
   FacebookExternalHit
   LinkedInBot
   Twitterbot
   PinterestBot
   TelegramBot
   WhatsApp
   Discordbot
   Slackbot

5. monitoring
   Google Lighthouse / PageSpeed
   UptimeRobot
   Pingdom
   StatusCake
   GTmetrix
   Site24x7
   Checkly
   HeadlessChrome

6. other
   AwarioBot
   AdsBot-Google
   Mediapartners-Google
   oraz fallback:
   bot/crawler/spider/scraper,
   python-requests,
   aiohttp,
   Go HTTP Client,
   curl/wget,
   Java HTTP,
   okhttp,
   PostmanRuntime,
   node-fetch,
   axios itd.

============================================================
NOWA TABELA
============================================================

portal_analytics_bot_requests

Pola:
- bot_name
- category
- route_name
- path
- method
- status_code
- locale
- user_agent_hash
- occurred_at

NIE zapisujemy:
- IP,
- query string,
- request body,
- surowego User-Agent.

User-Agent jest tylko hashowany SHA-256.

============================================================
PRYWATNE ADRESY / TOKENY
============================================================

Jeżeli bot wejdzie na trasę prywatną, np.:
password.*
order.*
payment.*
account.*

do tabeli NIE zapisujemy konkretnego URL z tokenem.

Zapisujemy szablon route, np.:

/{locale}/reset-password/{token}

zamiast realnego:
.../reset-password/SECRET_TOKEN

============================================================
PANEL ADMIN -> ANALITYKA
============================================================

W tym samym zakresie czasu:
Dzisiaj / 7 dni / 30 dni

pojawia się osobny blok:

BOTY INTERNETOWE

Metryki:
- Żądania botów
- Rozpoznane boty
- Odwiedzone adresy
- Ostatnia aktywność

Kategorie:
- Wyszukiwarki
- AI
- SEO
- Social / podglądy
- Monitoring
- Pozostałe

Tabele:
- Najaktywniejsze boty
  BOT
  TYP
  ŻĄDANIA
  OSTATNIO

- Najczęściej crawlowane adresy
  ADRES
  ŻĄDANIA
  LICZBA BOTÓW

============================================================
UNIKALNE WIZYTY
============================================================

Etykieta "Sesje" w głównych metrykach zostaje doprecyzowana jako:

UNIKALNE WIZYTY

podpis:
sesje użytkowników — bez botów

Ważne:
po wdrożeniu K90 każdy rozpoznany bot jest odcinany PRZED
utworzeniem PortalAnalyticsSession.

To dotyczy również:
active_sessions
oraz eventów JS/backend.

============================================================
DANE HISTORYCZNE
============================================================

Nie wykonujemy ryzykownego "czyszczenia" starych sesji.

Powód:
przed K90 tabela sesji nie przechowywała surowego User-Agent,
więc nie da się wiarygodnie stwierdzić, czy już zapisana historyczna
sesja była botem, bez zgadywania.

Od momentu wdrożenia K90 ruch jest rozdzielany prawidłowo.

============================================================
PLIKI
============================================================

NEW:
- database/migrations/2026_09_02_410000_create_portal_analytics_bot_requests_table.php
- app/Models/PortalAnalyticsBotRequest.php
- app/Services/BotDetectorService.php
- app/Services/PortalBotReportService.php
- tests/Feature/BotTrafficAnalyticsTest.php

CHANGED:
- app/Services/PortalAnalyticsService.php
- app/Services/PortalAnalyticsReportService.php
- app/Http/Middleware/TrackPortalAnalytics.php
- resources/views/admin/analytics/index.blade.php
- resources/css/admin-analytics.css
- lang/pl/analytics.php
- lang/en/analytics.php

============================================================
INSTALACJA LOKALNA
============================================================

Rozpakuj patch do:
C:\laragon\www\okulary-3d

z nadpisaniem plików.

Następnie:

php artisan optimize:clear
php artisan migrate

TESTY:

php artisan test --filter=BotTrafficAnalyticsTest
php artisan test --filter=PortalAnalyticsTest
php artisan test --filter=ProductionReadinessTest

Jeżeli zielono:

php artisan test

Następnie:

$env:Path = "C:\laragon\bin\nodejs\node-v22;$env:Path"
npm run build

============================================================
TEST RĘCZNY
============================================================

1. Backend -> Analityka.
2. Wybierz "7 dni".
3. Sprawdź nowy blok "Boty internetowe".

Lokalnie ruch botów można zasymulować PowerShell:

curl.exe `
  -A "Googlebot/2.1" `
  http://okulary-3d.test/pl

curl.exe `
  -A "PerplexityBot/1.0" `
  http://okulary-3d.test/pl

curl.exe `
  -A "SerpstatBot/2.1" `
  http://okulary-3d.test/pl/shop

curl.exe `
  -A "facebookexternalhit/1.1" `
  http://okulary-3d.test/pl

Po odświeżeniu Admin -> Analityka:
- 4 żądania botów powinny być widoczne osobno,
- Googlebot = Wyszukiwarki,
- PerplexityBot = AI,
- SerpstatBot = SEO,
- FacebookExternalHit = Social / podglądy.

Jednocześnie te 4 wejścia NIE mogą zwiększyć:
"Unikalne wizyty".

============================================================
PO TESTACH
============================================================

git add .
git commit -m "Separate bot traffic from portal analytics"
git push origin develop
