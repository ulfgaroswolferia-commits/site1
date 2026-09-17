# Moduł Zamówień z Hurtowni Warzyw i Owoców — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Budowa kompletnego, prostego panelu zamówień ze sklepu spożywczego do hurtowni warzyw i owoców: import cennika `.xlsx` (z pominięciem grafik i elastycznym mapowaniem kolumn), interaktywna edycja ilości (sztuki/waga), generowanie czystego pliku `.xlsx` bez formatowania do wysyłki e-mailem oraz archiwizacja w bazie SQLite.

**Architecture:** Moduł MVC w TwiiCoreF oparty o trasę `order`, kontroler `OrderController`, model SQLite `OrderModel` (`db/orders.sqlite`) oraz bezstanowe biblioteki w `program/lib/`: `XlsxParser` (do odczytu arkuszy przez `ZipArchive` i `SimpleXML`) oraz `XlsxWriter` (do tworzenia czystych arkuszy `.xlsx`). Widoki w `views/order/` w stylistyce panelu głównego.

**Tech Stack:** PHP 8.3, TwiiCoreF MVC, SQLite PDO, PHP ZipArchive, PHP SimpleXML, Vanilla JavaScript (kalkulator na żywo, filtr wyszukiwania).

**Spec:** `docs/superpowers/specs/2026-09-17-grocery-order-panel-design.md`

## Global Constraints

- Brak zewnętrznych zależności: czysty PHP bez Composer/npm.
- Wszystkie akcje wymagają zalogowanego użytkownika (`$this->requireAuth()`).
- Wszystkie akcje POST zabezpieczone tokenem CSRF (`Tools::requireCsrf()`).
- Wszystkie wyjścia w szablonach zabezpieczone przez `Tools::h()`.
- Linki i adresy generowane przez `App::baseUrl()`.
- Baza SQLite zapisywana w katalogu `db/orders.sqlite`.
- Wygenerowane pliki `.xlsx` archiwizowane w `storage/orders/`.

---

### Task 1: Parser plików Excel (`XlsxParser`)

**Files:**
- Create: `program/lib/XlsxParser.php`
- Modify: `program/config/includes.php`
- Test: `tests/test_xlsx_parser.php`

**Interfaces:**
- Produces:
  - `XlsxParser::open(string $filePath): XlsxParser`
  - `XlsxParser::getRawRows(int $limit = 30): array`
  - `XlsxParser::detectCandidateColumns(array $previewRows): array`
  - `XlsxParser::extractProducts(int $headerRowIndex, int $productColIndex, int $priceColIndex, ?int $unitColIndex = null): array`

- [x] **Step 1: Napisz test jednostkowy dla `XlsxParser` w `tests/test_xlsx_parser.php`**

Utwórz skrypt testowy w `tests/test_xlsx_parser.php`, który generuje prosty próbny plik `.xlsx` za pomocą `ZipArchive`, a następnie testuje odczyt wierszy, pomijanie pustych komórek i ekstrakcję produktów z cenami.

- [x] **Step 2: Uruchom test, aby upewnić się, że kończy się błędem (brak klasy `XlsxParser`)**

Run: `& "C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe" tests/test_xlsx_parser.php`
Expected: Błąd "Class 'XlsxParser' not found".

- [x] **Step 3: Zaimplementuj `XlsxParser` w `program/lib/XlsxParser.php`**

Zaimplementuj odczyt `xl/sharedStrings.xml` oraz `xl/worksheets/sheet1.xml` przy użyciu `ZipArchive` i `SimpleXML`. Oczyszczaj komórki, normalizuj ceny (zamiana przecinków na kropki, usuwanie „zł”) i zwracaj ustrukturyzowane wiersze. Zarejestruj `XlsxParser.php` w `program/config/includes.php`.

- [x] **Step 4: Uruchom test ponownie i potwierdź sukces**

Run: `& "C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe" tests/test_xlsx_parser.php`
Expected: "ALL XLSX_PARSER TESTS PASSED".

---

### Task 2: Generator czystego arkusza Excel (`XlsxWriter`)

**Files:**
- Create: `program/lib/XlsxWriter.php`
- Modify: `program/config/includes.php`
- Test: `tests/test_xlsx_writer.php`

**Interfaces:**
- Produces:
  - `XlsxWriter::createOrderWorkbook(array $items, array $meta = []): string` (zwraca zawartość binarną pliku `.xlsx`)
  - `XlsxWriter::saveToFile(string $filePath, array $items, array $meta = []): bool`

- [x] **Step 1: Napisz test weryfikujący tworzenie poprawnego archiwum `.xlsx` w `tests/test_xlsx_writer.php`**

Test przekazuje listę pozycji `[['name' => 'Ziemniaki młode', 'price' => 2.50, 'quantity' => 10, 'unit' => 'kg']]`, wywołuje `XlsxWriter::saveToFile()`, a następnie weryfikuje za pomocą `ZipArchive`, że plik zawiera poprawne pliki XML (`[Content_Types].xml`, `xl/workbook.xml`, `xl/worksheets/sheet1.xml`).

- [x] **Step 2: Uruchom test, upewnij się, że nie przechodzi z powodu braku `XlsxWriter`**

Run: `& "C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe" tests/test_xlsx_writer.php`
Expected: Błąd "Class 'XlsxWriter' not found".

- [x] **Step 3: Zaimplementuj `XlsxWriter` w `program/lib/XlsxWriter.php`**

Zaimplementuj generowanie minimalnego, w 100% zgodnego ze standardem OpenXML archiwum `.xlsx` z wierszem nagłówka (Lp., Nazwa towaru, Ilość, Jednostka, Cena jednostkowa, Wartość) oraz podsumowaniem na dole. Zarejestruj w `program/config/includes.php`.

- [x] **Step 4: Uruchom test i zweryfikuj poprawność generowanego pliku**

Run: `& "C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe" tests/test_xlsx_writer.php`
Expected: "ALL XLSX_WRITER TESTS PASSED".

---

### Task 3: Model danych SQLite (`OrderModel`)

**Files:**
- Create: `program/model/OrderModel.php`
- Test: `tests/test_order_model.php`

**Interfaces:**
- Consumes: brak
- Produces:
  - `OrderModel::initDatabase(): PDO`
  - `OrderModel::createOrder(array $orderData, array $items): int` (zwraca id zamówienia)
  - `OrderModel::getOrderById(int $orderId): ?array`
  - `OrderModel::getOrderItems(int $orderId): array`
  - `OrderModel::getAllOrders(int $limit = 50, int $offset = 0): array`
  - `OrderModel::getOrdersCount(): int`

- [x] **Step 1: Napisz test jednostkowy dla `OrderModel` w `tests/test_order_model.php`**

Test sprawdza automatyczne utworzenie pliku `db/orders.sqlite`, wstawienie nagłówka zamówienia z unikalnym numerem `order_number`, powiązanych pozycji `order_items`, odczyt pojedynczego zamówienia oraz listowanie historii.

- [x] **Step 2: Uruchom test, weryfikując brak klasy `OrderModel`**

Run: `& "C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe" tests/test_order_model.php`
Expected: Błąd braku klasy `OrderModel`.

- [x] **Step 3: Zaimplementuj `OrderModel` w `program/model/OrderModel.php`**

Utwórz model dziedziczący po `\Model`. Zapewnij połączenie przez `PDO('sqlite:' . BASE_PATH . '/db/orders.sqlite')`, transakcje `beginTransaction()` / `commit()`, tworzenie tabel `orders` i `order_items` oraz zapytania przez prepared statements.

- [x] **Step 4: Uruchom test bazy danych i potwierdź sukces**

Run: `& "C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe" tests/test_order_model.php`
Expected: "ALL ORDER_MODEL TESTS PASSED".

---

### Task 4: Kontroler zamówień (`OrderController`) i routing

**Files:**
- Modify: `program/config/data.php` (rejestracja trasy `'order' => 'action'`)
- Create: `program/script/OrderController.php`
- Test: `tests/test_order_controller.php`

**Interfaces:**
- Consumes: `XlsxParser`, `XlsxWriter`, `OrderModel`
- Produces:
  - `OrderController::actionIndex()`
  - `OrderController::actionUpload()`
  - `OrderController::actionProcess()`
  - `OrderController::actionSave()`
  - `OrderController::actionHistory()`
  - `OrderController::actionView()`
  - `OrderController::actionDownload()`

- [x] **Step 1: Zarejestruj trasę `'order' => 'action'` w `Config::$routes` w `program/config/data.php`**

- [x] **Step 2: Napisz test weryfikujący działanie metod kontrolera w `tests/test_order_controller.php`**

Test symuluje żądania, weryfikuje wymóg autoryzacji (redirect niezalogowanego), przetwarzanie danych i zapis do modelu.

- [x] **Step 3: Zaimplementuj `OrderController` w `program/script/OrderController.php`**

Dziedziczenie po `AppController`, obsługa wgrania pliku cennika (zapis w `tmp/`), wywołanie `XlsxParser`, ekstrakcja produktów, zapis zamówienia i generowanie pliku przez `XlsxWriter` do `storage/orders/`, akcja bezpiecznego pobierania `actionDownload()`.

- [x] **Step 4: Uruchom test kontrolera i potwierdź poprawność**

Run: `& "C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe" tests/test_order_controller.php`
Expected: "ALL ORDER_CONTROLLER TESTS PASSED".

---

### Task 5: Widoki kreatora, edycji i historii zamówień (`views/order/`)

**Files:**
- Create: `views/order/index.php`
- Create: `views/order/history.php`
- Create: `views/order/view.php`
- Modify: `views/home/dashboard.php` (dodanie kafelka skrótu i statystyk zamówień)

**Interfaces:**
- Consumes: widok `$view` z kontrolera `OrderController`

- [x] **Step 1: Zaimplementuj `views/order/index.php`**

Nowoczesny, responsywny interfejs kreatora w stylistyce panelu:
1. Strefa uploadu pliku `.xlsx` (Drag & Drop).
2. Panel wyboru nagłówka i mapowania kolumn z podglądem na żywo.
3. Interaktywna tabela produktów z filtrem wyszukiwania, polami ilości (sztuki / waga dziesiętna), automatycznym kalkulatorem sumy zamówienia i przyciskiem zatwierdzenia.
4. Ekran sukcesu z automatycznym wywołaniem pobierania pliku `.xlsx`.

- [x] **Step 2: Zaimplementuj `views/order/history.php` oraz `views/order/view.php`**

Tabela historii zamówień (numer, data, plik, łączna kwota, liczba pozycji) z możliwością wglądu w pozycje oraz ponownego pobrania pliku `.xlsx`.

- [x] **Step 3: Zaktualizuj `views/home/dashboard.php`**

Dodaj kafelek „Zamówienia z hurtowni” w sekcji szybkich akcji oraz metrykę z liczbą złożonych zamówień.

- [x] **Step 4: Sprawdź poprawność składni wszystkich widoków przy pomocy lintera PHP**

Run: `& "C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe" -l views/order/index.php; & "C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe" -l views/order/history.php; & "C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe" -l views/order/view.php`
Expected: "No syntax errors detected in...".

---

### Task 6: Testy integracyjne end-to-end i weryfikacja w środowisku Laragon

**Files:**
- Test: `tests/test_e2e_ordering.php`

- [x] **Step 1: Napisz skrypt testu integracyjnego E2E w `tests/test_e2e_ordering.php`**

Skrypt tworzy przykładowy cennik hurtowni warzyw i owoców z logo i grafiką (np. 15 pozycji: pomidory malinowe, ziemniaki, jabłka champion, banany itp.), przeprowadza import przez kontroler, symuluje wypełnienie ilości przez sklep spożywczy, zapisuje zamówienie w bazie i sprawdza zawartość wygenerowanego czystego arkusza `.xlsx`.

- [x] **Step 2: Wykonaj pełny test integracyjny**

Run: `& "C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe" tests/test_e2e_ordering.php`
Expected: "E2E ORDERING FLOW SUCCESSFUL".

- [x] **Step 3: Weryfikacja końcowa i czystość repozytorium**

Upewnij się, że katalogi `db/` oraz `storage/` są ignorowane w `.gitignore`, a wszystkie pliki spełniają standardy specyfikacji.
