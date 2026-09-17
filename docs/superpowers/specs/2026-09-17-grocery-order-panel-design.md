# Specyfikacja Projektowa: Moduł Zamówień Warzyw i Owoców z Hurtowni (TwiiCoreF)

- **Data:** 2026-09-17
- **Status:** Zaakceptowany przez użytkownika
- **Autor:** Vanguard_UI_Architect & TwiiCoreF Team

---

## 1. Cel i zakres systemu

System służy do składania zamówień warzyw i owoców dla sklepu spożywczego na podstawie cenników w formacie Excel (`.xlsx`) otrzymywanych z hurtowni.

### Kluczowe wymagania biznesowe i techniczne:
1. **Wgrywanie cennika hurtowni**: Obsługa plików `.xlsx`, które często zawierają u góry logotypy, nagłówki ozdobne, grafiki i puste wiersze.
2. **Pominięcie grafik i autodetekcja tabeli**: Parser wyodrębnia wyłącznie surowe dane tabelaryczne, pomijając wszelkie obiekty graficzne.
3. **Elastyczne mapowanie kolumn**: Użytkownik widzi podgląd pierwszych wierszy i potwierdza, które kolumny odpowiadają za:
   - *Nazwę towaru*
   - *Cenę jednostkową*
   - *Jednostkę miary* (opcjonalnie: `kg`, `szt.`; domyślnie `kg`)
   - *Wiersz początkowy danych*
4. **Interaktywna edycja ilości**: Przekształcenie cennika w formularz zamówienia z filtrowaniem na żywo, wprowadzaniem ilości (wagi ułamkowe np. `1.50` lub sztuki całkowite) oraz dynamicznym sumowaniem wartości.
5. **Eksport do czystego Excela (.xlsx)**: Po zatwierdzeniu generowany jest surowy, pozbawiony grafik i formatowań plik `.xlsx` zawierający wyłącznie zamówione pozycje (`ilość > 0`), gotowy do bezpośredniego wysłania e-mailem do hurtowni.
6. **Baza historii zamówień**: Zamówienie (nagłówek i poszczególne pozycje z cenami i ilościami) jest trwale rejestrowane w bazie danych SQLite (`db/orders.sqlite`) z możliwością wglądu i ponownego pobrania wygenerowanego arkusza.

---

## 2. Architektura i komponenty

Projekt jest zintegrowany ze szkieletem **TwiiCoreF** zgodnie z wytycznymi architektonicznymi `docs/MVC.md`:

```
├── db/
│   └── orders.sqlite                    <- Baza danych SQLite z historią zamówień
├── storage/
│   └── orders/                          <- Archiwum wygenerowanych plików .xlsx do pobrania
├── program/
│   ├── config/
│   │   └── data.php                     <- Rejestracja trasy 'order' => 'action'
│   ├── lib/
│   │   ├── XlsxParser.php               <- Parser cennika .xlsx z pomijaniem grafik
│   │   └── XlsxWriter.php               <- Generator czystego pliku .xlsx dla hurtowni
│   ├── model/
│   │   └── OrderModel.php               <- Model danych SQLite (orders, order_items)
│   └── script/
│       └── OrderController.php          <- Kontroler kreatora, uploadu, eksportu i historii
└── views/
    └── order/
        ├── index.php                    <- Kreator zamówienia (Upload, Mapowanie, Edycja)
        ├── history.php                  <- Lista historii zamówień
        └── view.php                     <- Podgląd szczegółów archiwalnego zamówienia
```

---

## 3. Schemat bazy danych (SQLite: `db/orders.sqlite`)

Baza danych tworzona jest automatycznie przez `OrderModel::initDatabase()` przy pierwszym wywołaniu, jeśli plik nie istnieje.

### Tabela `orders`
| Kolumna | Typ | Opis |
|---|---|---|
| `id` | INTEGER PRIMARY KEY AUTOINCREMENT | Identyfikator zamówienia |
| `order_number` | TEXT NOT NULL UNIQUE | Numer zamówienia (np. `ZAM/2026/09/17/01`) |
| `supplier_name` | TEXT | Nazwa hurtowni lub notatka użytkownika |
| `original_filename`| TEXT NOT NULL | Oryginalna nazwa wgranego pliku cennika |
| `export_filename` | TEXT NOT NULL | Nazwa wygenerowanego czystego pliku `.xlsx` |
| `total_items` | INTEGER NOT NULL DEFAULT 0 | Liczba zamówionych pozycji (o ilości > 0) |
| `total_amount` | REAL NOT NULL DEFAULT 0.00 | Łączna wartość zamówienia w PLN |
| `created_at` | DATETIME NOT NULL | Data i czas utworzenia zamówienia |

### Tabela `order_items`
| Kolumna | Typ | Opis |
|---|---|---|
| `id` | INTEGER PRIMARY KEY AUTOINCREMENT | Identyfikator pozycji |
| `order_id` | INTEGER NOT NULL | Klucz obcy do `orders.id` (ON DELETE CASCADE) |
| `product_name` | TEXT NOT NULL | Nazwa warzywa lub owocu |
| `unit_price` | REAL NOT NULL | Cena jednostkowa w PLN |
| `quantity` | REAL NOT NULL | Zamówiona ilość (obsługa ułamków dziesiętnych) |
| `unit` | TEXT NOT NULL DEFAULT 'kg' | Jednostka miary (`kg`, `szt.`, itp.) |
| `item_total` | REAL NOT NULL | Wartość pozycji: `round(unit_price * quantity, 2)` |

---

## 4. Biblioteki narzędziowe (program/lib/)

### 4.1. `XlsxParser.php`
- **Wykorzystuje**: wbudowane w PHP `ZipArchive` oraz `SimpleXML`.
- **Działanie**:
  1. Otwiera plik `.xlsx` jako archiwum ZIP.
  2. Wczytuje tablicę unikalnych ciągów znaków z `xl/sharedStrings.xml`.
  3. Odczytuje pierwszy arkusz `xl/worksheets/sheet1.xml` linijka po linijce.
  4. Całkowicie pomija warstwy graficzne i definicje z `xl/drawings/`.
  5. Pomija puste wiersze u góry i zwraca macierz wierszy i kolumn.
  6. Metoda `getPreviewRows(int $limit = 10)`: zwraca próbkę danych do interfejsu mapowania.
  7. Metoda `extractProducts(int $headerRowIndex, string $colProduct, string $colPrice, ?string $colUnit)`: przetwarza i oczyszcza asortyment (walidacja cen, zamiana przecinków na kropki, trim nazw).

### 4.2. `XlsxWriter.php`
- **Wykorzystuje**: natywny `ZipArchive`.
- **Działanie**:
  1. Składa standardowy plik `.xlsx` w standardzie OpenXML zgodny z MS Excel i Google Sheets.
  2. Tworzy wiersz nagłówków:
     `A: Lp. | B: Towar | C: Ilość | D: Jednostka | E: Cena jednostkowa (zł) | F: Wartość (zł)`
  3. Wstawia wyłącznie pozycje z zamówioną ilością (`quantity > 0`).
  4. Na końcu wstawia pogrubiony wiersz podsumowania: `ŁĄCZNA WARTOŚĆ: [kwota] zł`.
  5. Zapisuje plik binarny do `storage/orders/{export_filename}` oraz umożliwia bezpośrednie wysłanie do strumienia pobierania przeglądarki (`Content-Disposition: attachment`).

---

## 5. Kontroler i przepływ żądań (`OrderController.php`)

Kontroler rejestrowany w `Config::$routes`: `'order' => 'action'`.

- `actionIndex()`:
  - Wymaga autoryzacji (`$this->requireAuth()`).
  - Renderuje widok `views/order/index.php` w pełnym layoutcie lub zintegrowanym panelu.
- `actionUpload()` (POST):
  - Przyjmuje przesłany plik z formularza `$_FILES['price_list']`.
  - Weryfikuje rozszerzenie `.xlsx` i rozmiar pliku.
  - Zapisuje plik w katalogu tymczasowym `tmp/`.
  - Zwraca w formacie JSON strukturę pierwszych wierszy oraz sugerowane mapowanie kolumn.
- `actionProcess()` (POST):
  - Przyjmuje indeks nagłówka i mapowanie kolumn.
  - Zwraca listę wyekstrahowanych produktów z cennika do interaktywnego widoku zamówienia.
- `actionSave()` (POST):
  - Odbiera tablicę zamówionych pozycji `items` (produkt, cena, ilość, jednostka).
  - Waliduje i filtruje pozycje o ilości > 0.
  - Zapisuje zamówienie do `OrderModel` w bazie SQLite.
  - Wywołuje `XlsxWriter` i generuje plik `.xlsx`.
  - Zwraca identyfikator zamówienia oraz link do natychmiastowego pobrania.
- `actionHistory()`:
  - Pobiera z bazy listę wszystkich zamówień z paginacją.
  - Renderuje `views/order/history.php`.
- `actionView()`:
  - Pobiera szczegóły danego zamówienia po `id`.
  - Renderuje `views/order/view.php`.
- `actionDownload()`:
  - Bezpiecznie wysyła wygenerowany plik `.xlsx` z `storage/orders/` do pobrania przez przeglądarkę.

---

## 6. Bezpieczeństwo i obsługa błędów

1. **Autoryzacja**: Każda akcja weryfikuje sesję zalogowanego użytkownika (`$this->requireAuth()`). Niezalogowani są przekierowywani na `/home/login`.
2. **Ochrona CSRF**: Wszystkie operacje POST wymagają poprawnego tokena CSRF (`Tools::requireCsrf()`).
3. **Walidacja plików**:
   - Sprawdzanie rozszerzenia (`.xlsx`) i MIME typu.
   - Limit rozmiaru pliku (np. do 15 MB).
   - Ochrona przed uszkodzonymi archiwami ZIP (graceful error z czytelnym komunikatem w UI).
4. **Odporność na formaty cen i liczb**:
   - Obsługa cen z separatorem dziesiętnym w postaci przecinka lub kropki (np. `4,20`, `4.20`, `4,20 zł/kg`).
   - Odrzucanie wierszy z zerowymi lub niepoprawnymi nazwami towarów.
5. **Separacja danych**:
   - Katalogi `storage/orders/` i `tmp/` są zabezpieczone plikiem `.htaccess` z dyrektywą `Deny from all`, aby nikt nie mógł pobrać plików bezpośrednio przez URL z pominięciem kontrolera.

---

## 7. Plan testów i weryfikacji

1. **Weryfikacja składni PHP**: `php -l` na wszystkich nowych plikach kontrolera, modelu, bibliotek i widoków.
2. **Test parsowania `.xlsx`**: Przetestowanie `XlsxParser` na wygenerowanym arkuszu testowym z nagłówkami na różnych wysokościach (np. wiersz 1 vs wiersz 5) i formatami cen.
3. **Test generowania `.xlsx`**: Weryfikacja integralności wygenerowanego przez `XlsxWriter` pliku (poprawne otwarcie i struktura XML).
4. **Test integracji bazy SQLite**: Zapisanie próbnego zamówienia, odczyt nagłówka i pozycji z `OrderModel`.
5. **Test end-to-end w przeglądarce**:
   - Wgranie pliku cennika.
   - Wprowadzenie ilości (np. 5 kg marchwi, 10 sztuk sałaty).
   - Pobranie czystego arkusza zamówienia.
   - Weryfikacja obecności zamówienia w zakładce „Historia zamówień”.
