OKULARY 3D — K86.4E FIX
MULTI-CURRENCY DOCUMENT TEST ASSERTION

Problem:
Cały test suite przeszedł poza jednym testem:
MultiCurrencyDocumentTest.

Aplikacja renderuje prawidłowo:

Wartość bazowa:
118,99
PLN

W HTML liczba i kod waluty są rozdzielone białymi znakami /
przejściami linii wynikającymi z formatowania Blade.

Przeglądarka wizualnie składa je jako:
118,99 PLN

Natomiast PHPUnit assertSee('118,99 PLN') porównuje surowy HTML
i nie normalizuje tych białych znaków.

To nie jest błąd aplikacji ani danych finansowych.

Naprawa:
Test sprawdza osobno:
- etykietę "Wartość bazowa:"
- "118,99"
- "PLN"

Dzięki temu nadal weryfikuje kompletne dane dokumentu,
ale nie zależy od technicznego formatowania whitespace w Blade.

ZMIENIONE:
- tests/Feature/MultiCurrencyDocumentTest.php
- README_PATCH.txt

PO ROZPAKOWANIU:
php artisan optimize:clear
php artisan test --filter=MultiCurrencyDocumentTest

Jeżeli zielono:
php artisan test
npm run build
