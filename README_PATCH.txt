OKULARY3D — KROK 80
Contextual Recommendations

CEL:
Połączenie ścieżki:
ARTYKUŁ -> 3D LAB -> SKLEP

FUNKCJONALNOŚCI:
- rekomendacje narzędzi 3D LAB pod artykułem,
- rekomendacje produktów sklepu pod artykułem,
- ręczne wskazania w edytorze artykułu,
- opcjonalne automatyczne uzupełnianie rekomendacji,
- ręczne rekomendacje zawsze mają pierwszeństwo,
- maksymalnie 2 narzędzia i 4 produkty.

NARZĘDZIA:
- Anaglyph Maker
- Stereo Alignment / Converter
- Lenticular LAB
- MPO Viewer / Converter
- Wigglegram Maker

TRYB AUTOMATYCZNY:
System analizuje treść aktualnej wersji językowej artykułu
i dopasowuje działające narzędzia na podstawie słownictwa
związanego z daną techniką.

Produkty są dobierane wyłącznie spośród:
- Active,
- posiadających aktywny wariant,
- posiadających publiczną wersję językową.

Jeżeli dopasowanie produktu jest zbyt słabe,
produkt nie jest pokazywany.

TRYB RĘCZNY:
Admin -> Artykuły -> Edycja

Nowy panel:
Rekomendacje kontekstowe

Redaktor może:
- włączyć/wyłączyć AUTO,
- wskazać do 2 narzędzi,
- wskazać do 4 produktów.

AUTO OFF:
wyświetlane są tylko wskazania ręczne.

AUTO ON:
wskazania ręczne są pierwsze,
a wolne miejsca mogą zostać uzupełnione automatycznie.

BAZA:
- nowa kolumna articles.recommendation_auto
- nowa tabela article_context_recommendations

PUBLICZNY ARTYKUŁ:
Po treści może pojawić się sekcja:
Sprawdź ten temat w praktyce

z blokami:
- Powiązane narzędzia
- Powiązane produkty

WDROŻENIE:
php artisan optimize:clear
php artisan migrate
npm run build
php artisan test

TEST RĘCZNY:
1. Otwórz istniejący artykuł w Admin -> Artykuły.
2. Znajdź panel Rekomendacje kontekstowe.
3. Wybierz np. Lenticular LAB.
4. Wybierz 1 produkt.
5. Zapisz artykuł.
6. Otwórz wersję publiczną.
7. Sprawdź link LAB i produkt.
8. Włącz AUTO i przetestuj artykuł zawierający słowa:
   lenticular / lentikular / LPI / folia soczewkowa.
9. System powinien sam zaproponować Lenticular LAB.

COMMIT:
git add .
git commit -m "Add contextual recommendations"
git push

NASTĘPNY KROK:
KROK 81 — Multilingual SEO.
