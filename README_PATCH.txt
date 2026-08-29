OKULARY3D — KROK 81
Multilingual SEO

CEL:
Ujednolicenie technicznego SEO całego publicznego wortalu PL/EN.

WDROŻONE:
1. Globalny canonical.
2. hreflang PL / EN.
3. hreflang x-default.
4. meta robots.
5. Open Graph.
6. Twitter Cards.
7. JSON-LD.
8. sitemap.xml.
9. robots.txt.
10. poprawione przełączanie języka dla treści posiadających różne slugi.

PUBLICZNE ADRESY:
http://okulary-3d.test/sitemap.xml
http://okulary-3d.test/robots.txt

CANONICAL:
Każda publiczna strona indeksowalna otrzymuje jeden canonical.

Dla stron z bezpieczną paginacją:
?page=2

canonical zachowuje numer strony.

Dla filtrów i wyszukiwania, np.:
?category=...
?technique=...
?q=...

strona otrzymuje:
noindex,follow

a canonical wskazuje czystą stronę bazową bez parametrów.

HREFLANG:
Dla zwykłych stron:
PL i EN generowane są automatycznie na podstawie tej samej trasy.

Dla:
- artykułów,
- produktów,
- archiwum,

alternatywne URL-e są budowane z rzeczywistych lokalizowanych slugów.

Przykład:
PL:
/pl/articles/fotografia-stereoskopowa

EN:
/en/articles/stereoscopic-photography

Dodatkowo:
hreflang="x-default"

wskazuje domyślną wersję PL, o ile jest publicznie dostępna.

PRZEŁĄCZNIK JĘZYKA:
Nagłówek korzysta teraz z tych samych adresów co hreflang.

Naprawia to wcześniejszy problem stron szczegółowych, gdzie:
Article / Product / Archive
mogły mieć inny slug PL i EN.

Jeśli dana wersja językowa nie istnieje,
przełącznik bezpiecznie wraca do strony głównej tego języka.

OPEN GRAPH / TWITTER:
Globalny layout dodaje:
- og:type
- og:title
- og:description
- og:url
- og:site_name
- og:locale
- og:locale:alternate
- og:image, jeśli istnieje
- twitter:card
- twitter:title
- twitter:description
- twitter:image, jeśli istnieje

Obrazy SEO są konwertowane do pełnych URL-i absolutnych.

JSON-LD:
Globalnie:
- Organization
- WebSite

Artykuł:
- Article

Produkt:
- Product
- Offer dla aktywnych wariantów
- cena
- waluta
- dostępność
- SKU
- marka

Archiwum:
- CreativeWork

Galeria:
- ImageObject

SITEMAP.XML:
Zawiera:
- stronę główną PL/EN,
- sklep PL/EN,
- wszystkie działające narzędzia 3D LAB PL/EN,
- archiwum PL/EN,
- galerię PL/EN,
- opublikowane artykuły,
- aktywne produkty z aktywnymi wariantami,
- opublikowane obiekty archiwum,
- opublikowane prace galerii.

Dla rekordów wielojęzycznych sitemap używa rzeczywistych slugów językowych.

W sitemapie znajdują się także:
xhtml:link rel="alternate" hreflang="pl"
xhtml:link rel="alternate" hreflang="en"
xhtml:link rel="alternate" hreflang="x-default"

Sitemap NIE zawiera:
- wersji Draft,
- wersji Review,
- nieopublikowanych artykułów,
- nieaktywnych produktów,
- nieopublikowanego archiwum,
- niezatwierdzonych prac galerii.

ROBOTS.TXT:
Pozwala indeksować publiczny portal, ale blokuje crawling obszarów prywatnych:

/admin
/*/account
/*/cart
/*/checkout
/*/login
/*/register
/*/forgot-password
/*/reset-password
/*/order/
/*/payment/

Na końcu robots.txt podawany jest pełny adres sitemap.xml.

NOINDEX:
Publiczny layout automatycznie stosuje:
noindex,nofollow

dla:
- logowania,
- rejestracji,
- resetowania hasła,
- konta,
- koszyka,
- checkout,
- zamówień,
- płatności,
- formularza dodawania pracy do galerii.

Dla stron filtrowanych:
noindex,follow

Dodatkowe zabezpieczenie:
prywatne URL-e, np. link resetu hasła albo public_token zamówienia,
nie są kopiowane do canonical ani hreflang.

PLIKI:
NOWE:
- config/seo.php
- app/Services/SeoService.php
- app/Services/SitemapService.php
- app/Http/Controllers/SeoController.php
- resources/views/seo/sitemap.blade.php
- tests/Feature/MultilingualSeoTest.php

ZMIENIONE:
- routes/web.php
- resources/views/layouts/app.blade.php
- resources/views/partials/header.blade.php
- app/Http/Controllers/ArticleController.php
- app/Http/Controllers/ShopController.php
- app/Http/Controllers/ArchiveController.php
- app/Http/Controllers/StereoGalleryController.php
- resources/views/articles/show.blade.php
- resources/views/shop/show.blade.php

MIGRACJE:
Brak.

NPM BUILD:
Brak nowych assetów.
npm run build nie jest wymagany.

PO ROZPAKOWANIU:
php artisan optimize:clear
php artisan test

TEST RĘCZNY:
1. Otwórz źródło:
   http://okulary-3d.test/pl

2. Sprawdź:
   canonical
   hreflang pl
   hreflang en
   hreflang x-default
   og:locale=pl_PL

3. Otwórz artykuł posiadający PL i EN z różnymi slugami.
   Sprawdź:
   - przełącznik PL/EN,
   - canonical,
   - hreflang,
   - JSON-LD Article.

4. Otwórz produkt PL/EN.
   Sprawdź JSON-LD:
   Product
   Offer
   priceCurrency
   availability.

5. Otwórz:
   /pl/archive?technique=stereocard

   Powinno być:
   meta robots = noindex,follow

   canonical:
   /pl/archive

6. Otwórz:
   http://okulary-3d.test/sitemap.xml

   Sprawdź PL/EN i x-default.

7. Otwórz:
   http://okulary-3d.test/robots.txt

COMMIT:
git add .
git commit -m "Add multilingual SEO"
git push

NASTĘPNY KROK:
KROK 82 — Portal Analytics.
