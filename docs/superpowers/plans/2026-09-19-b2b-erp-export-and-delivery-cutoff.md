# Plan Wdrożenia: Eksport ERP (Subiekt, Optima, Symfonia, Wf-Mag), Okno Czasowe Zamówień (Cut-off Time) i Optymalizacja SQLite WAL

> **Dla wykonawców:** WYMAGANA SUB-UMIEJĘTNOŚĆ: Użyj superpowers:executing-plans lub kroków TDD do wdrożenia tego planu zadanie po zadaniu. Kroki używają składni checkboxów (`- [ ]`) do śledzenia postępów.

**Cel:** Rozbudowa portalu B2B Hurtowni Magdy o:
1. Uniwersalny silnik eksportu zamówień do programów ERP (Subiekt GT/Nexo, Comarch Optima, Symfonia Handel, Asseco WAPRO Wf-Mag) w trybie pojedynczym i zbiorczym z pamięcią wybranego formatu.
2. Zarządzanie oknem czasowym zamówień (Cut-off time, domyślnie 21:30) oraz wybór terminu dostawy w portalu klienta z inteligentnym przełączaniem na kolejny dzień roboczy po minięciu godziny granicznej.
3. Włączenie trybu SQLite WAL (`journal_mode = WAL`) oraz `busy_timeout = 5000` dla maksymalnej współbieżności i ochrony przed blokowaniem bazy.
4. Opcjonalne pole `erp_code` (Symbol / Kod ERP) przy produktach dla precyzyjnego mapowania z kartoteką magazynową.

**Architektura:**
- `ErpExporter` (`program/lib/ErpExporter.php`) — dedykowana bezstanowa biblioteka generująca pliki zgodne ze specyfikacjami ERP (Subiekt EPP/EDI++, Optima XML, Symfonia TXT, Wf-Mag XML).
- `B2bRepository` (`program/model/B2bRepository.php`) — migracje schematu (`delivery_date`, `erp_code`, tabela `b2b_settings`), aktywacja trybu WAL, metody pobierania i zapisu ustawień oraz zamówień z datą dostawy.
- `B2bController` (`program/script/B2bController.php`) — endpointy eksportu pojedynczego i zbiorczego, zapis ustawień, kalkulacja dostępnych terminów dostaw.
- `views/b2b/catalog.php` — kafelki wyboru terminu dostawy w modalu podsumowania, dynamiczna blokada i komunikat po godzinie granicznej.
- `views/b2b/admin.php` — konfiguracja godziny granicznej i dni dostaw, przyciski eksportu ERP w modalu zamówienia i na liście zamówień, zapamiętywanie wybranego formatu w `localStorage` i preferencjach.

**Tech Stack:** PHP 8.3, SQLite3 (PDO WAL mode), SimpleXML/DOM, Tailwind CSS, JavaScript ES6.

---

## Globalne Ograniczenia i Założenia
- Kod nie wprowadza zewnętrznych zależności Composer — czysty PHP 8.3 zgodny z architekturą projektu.
- Pamięć wybranego formatu ERP działa natychmiast w UI (localStorage) oraz jest utrwalana w preferencjach systemowych.
- Czas składania zamówienia w portalu klienta zachowuje komfortowy 1-sekundowy feedback UX, a zapytania do bazy wykonują się w ułamku sekundy.
- Wszystkie operacje na bazie danych SQLite są zabezpieczone transakcjami oraz timeoutem `busy_timeout = 5000`.

---

### Zadanie 1: Aktywacja trybu SQLite WAL i obsługa współbieżności w B2bRepository

**Pliki:**
- Modyfikacja: `program/model/B2bRepository.php:40-55`
- Test: `tests/test_b2b_wal_mode.php`

**Interfejsy:**
- `B2bRepository::__construct()` włącza:
  - `PRAGMA journal_mode = WAL;`
  - `PRAGMA busy_timeout = 5000;`
  - `PRAGMA synchronous = NORMAL;`

- [ ] **Krok 1: Napisz test weryfikujący tryb WAL i timeout bazy**
Utwórz `tests/test_b2b_wal_mode.php`, który łączy się z `B2bRepository` i sprawdza wynik `PRAGMA journal_mode;` (powinien zwrócić `wal`) oraz `PRAGMA busy_timeout;` (powinien zwrócić `5000`).

- [ ] **Krok 2: Uruchom test i upewnij się, że zawodzi (stan RED)**
Uruchom `tests/test_b2b_wal_mode.php`.

- [ ] **Krok 3: Wprowadź pragmy WAL w B2bRepository::__construct()**
W `program/model/B2bRepository.php` dodaj wywołania po otwarciu połączenia SQLite.

- [ ] **Krok 4: Uruchom test ponownie i potwierdź sukces (stan GREEN)**
Uruchom `tests/test_b2b_wal_mode.php`.

---

### Zadanie 2: Rozszerzenie schematu bazy danych (delivery_date, erp_code, b2b_settings)

**Pliki:**
- Modyfikacja: `program/model/B2bRepository.php`
- Test: `tests/test_b2b_schema_erp_and_delivery.php`

**Interfejsy:**
- Tabela `b2b_orders`: dodanie kolumny `delivery_date DATE DEFAULT NULL`
- Tabela `b2b_products`: dodanie kolumny `erp_code VARCHAR(50) DEFAULT NULL`
- Tabela `b2b_settings`: `CREATE TABLE IF NOT EXISTS b2b_settings (setting_key VARCHAR(50) PRIMARY KEY, setting_value TEXT)`
- Metody `B2bRepository`:
  - `getSetting(string $key, $default = null): ?string`
  - `setSetting(string $key, string $value): bool`
  - `getAllSettings(): array`
  - `updateProduct(int $id, array $data)` (uwzględniająca `erp_code`)
  - `createOrder(array $orderData, array $items)` (uwzględniająca `delivery_date`)

- [ ] **Krok 1: Napisz test weryfikujący schemat i metody ustawień**
Sprawdź czy tabela `b2b_settings` działa, czy można zapisać i odczytać `cutoff_time`, `delivery_days`, `default_erp_format`, oraz czy `b2b_orders` obsługuje `delivery_date`.

- [ ] **Krok 2: Uruchom test (stan RED)**

- [ ] **Krok 3: Zaimplementuj aktualizację schematu i metody w B2bRepository**
Dodaj bezpieczne `ALTER TABLE` w `initDatabase()`, utwórz tabelę `b2b_settings` i dodaj metody `getSetting`, `setSetting`.

- [ ] **Krok 4: Uruchom test i potwierdź sukces (stan GREEN)**

---

### Zadanie 3: Biblioteka generatora eksportu ERP (`program/lib/ErpExporter.php`)

**Pliki:**
- Utwórz: `program/lib/ErpExporter.php`
- Test: `tests/test_erp_exporter.php`

**Interfejsy:**
- `ErpExporter::export(string $format, array $ordersData): array` -> `['content' => string, 'filename' => string, 'mime' => string]`
- Formaty:
  - `subiekt` (`.epp` / EDI++) — sekcje: `[INFO]`, `[NAGLOWEK]`, `[KONTRAHENCI]`, `[TOWARY]`, `[DOKUMENT]`, `[ZAWARTOSC]`.
  - `optima` (`.xml`) — schemat Comarch XML `<DOKUMENTY><ZAMOWIENIE>...<POZYCJE>...`.
  - `symfonia` (`.txt`) — format dokumentu Zamówienie Obce (ZO) w formacie tekstowym Symfonii.
  - `wfmag` (`.xml`) — format zamówienia klienta Asseco WAPRO Wf-Mag.
- Obsługa pojedynczego zamówienia oraz paczki zbiorczej wielu zamówień.
- Użycie `erp_code` (jeśli podany) lub nazwy towaru, oraz NIP kontrahenta.

- [ ] **Krok 1: Napisz test sprawdzający poprawność struktury każdego z 4 formatów**
W `tests/test_erp_exporter.php` utwórz testowe zamówienie z 2 pozycjami i sprawdź czy `ErpExporter::export(...)` generuje poprawny nagłówek, kontrahenta, pozycje i sumy dla Subiekta, Optimy, Symfonii i Wf-Maga.

- [ ] **Krok 2: Uruchom test (stan RED)**

- [ ] **Krok 3: Zaimplementuj klasę ErpExporter w program/lib/ErpExporter.php**
Zbuduj generator dla 4 formatów ze szczególnym uwzględnieniem kodowania znaków (Subiekt: Windows-1250, XML: UTF-8).

- [ ] **Krok 4: Uruchom test i potwierdź sukces (stan GREEN)**

---

### Zadanie 4: Logika Cut-off Time i wybór terminu dostawy w portalu klienta

**Pliki:**
- Modyfikacja: `program/script/B2bController.php` (kalkulacja terminów dostaw i odbiór `delivery_date` w `actionSaveorder`)
- Modyfikacja: `views/b2b/catalog.php` (kafelki wyboru daty dostawy, blokada po godzinie granicznej, wysyłka w formularzu)
- Test: `tests/test_b2b_delivery_cutoff.php`

**Interfejsy:**
- `B2bController::getAvailableDeliveryDates(): array` -> lista dostępnych dni roboczych z flagą `is_cutoff_passed` i rekomendowaną domyślną datą.
- `POST /b2b/saveorder` z parametrem `delivery_date` (format YYYY-MM-DD).

- [ ] **Krok 1: Napisz test kalkulatora godzin granicznych i zapisu daty dostawy**
Przetestuj zachowanie przed godziną graniczną (dostępne jutro) i po godzinie granicznej (dostępne pojutrze z ostrzeżeniem).

- [ ] **Krok 2: Uruchom test (stan RED)**

- [ ] **Krok 3: Wdróż logikę w B2bController i widoku catalog.php**
Dodaj kafelki wyboru daty w modalu podsumowania zamówienia, zsynchronizuj z godziną graniczną z bazy.

- [ ] **Krok 4: Uruchom test i potwierdź sukces (stan GREEN)**

---

### Zadanie 5: Panel Hurtownika — Ustawienia, Eksport ERP i Pamięć Formatu

**Pliki:**
- Modyfikacja: `program/script/B2bController.php` (akcje eksportu i zapisu ustawień)
- Modyfikacja: `views/b2b/admin.php` (UI ustawień, przyciski eksportu w modalu i liście zamówień, wybór formatu z pamięcią)
- Test: `tests/test_b2b_erp_export_endpoints.php`

**Interfejsy:**
- `GET /b2b/exporterp?id={order_id}&format={subiekt|optima|symfonia|wfmag}`
- `GET /b2b/exportbatch?status={status}&date={delivery_date}&format={subiekt|optima|symfonia|wfmag}`
- `POST /b2b/savesettings` (zapis `cutoff_time`, `default_erp_format`)

- [ ] **Krok 1: Napisz test weryfikujący endpointy eksportu pojedynczego i zbiorczego oraz zapisu ustawień**
- [ ] **Krok 2: Uruchom test (stan RED)**
- [ ] **Krok 3: Zaimplementuj endpointy w B2bController oraz UI w admin.php**
Dodaj przełącznik formatów z automatycznym zapisem wyboru w `localStorage` i preferencjach oraz modal/dropdown eksportu.
- [ ] **Krok 4: Uruchom test i potwierdź sukces (stan GREEN)**

---

### Zadanie 6: Pełna weryfikacja integracyjna i testy E2E

**Pliki:**
- Utwórz: `tests/test_b2b_full_erp_and_cutoff_e2e.php`

- [ ] **Krok 1: Uruchom kompletny scenariusz E2E**
  1. Sprawdzenie trybu WAL i współbieżności.
  2. Zmiana godziny granicznej w ustawieniach hurtownika.
  3. Sklep B2B wchodzi do katalogu, widzi dostępne daty dostawy, składa zamówienie na wybraną datę.
  4. Hurtownik widzi zamówienie z datą dostawy, pobiera eksport w wybranym formacie ERP.
  5. Hurtownik pobiera paczkę zbiorczą dnia.
  6. Weryfikacja integralności plików eksportu.
- [ ] **Krok 2: Uruchom istniejące testy regresyjne (`tests/test_b2b_e2e_full.php`, `tests/test_b2b_fast_order_save.php`)**
- [ ] **Krok 3: Raport końcowy dla użytkownika.**
