# Checkpoint sesji deweloperskiej — 2026-10-01

Stan gałęzi: `main` zsynchronizowany z `origin/main`.

---

## 1. Co zostało zrealizowane w tej sesji

### A. Reguły pracy i autonomia agenta
* Utworzono i zacommitowano plik `GEMINI.md` definiujący styl pracy „Bias for Action” (brak pytań uściślających, pytania do użytkownika wyłącznie w wariantach architektonicznych A/B lub przy formalnych skillach Superpowers).
* Zabezpieczono `.gitignore` przed przypadkowym śledzeniem plików konfiguracyjnych (`data.php`, `data.sample.php`).

### B. Uszczelnienie bazy danych i współbieżności (Punkt 1)
* **Eliminacja blokad DDL przy każdym requeście:** Wprowadzono znacznik wersji bazy `db/.b2b_v2`, dzięki czemu zapytania `CREATE TABLE`, `ALTER TABLE` i domyślne ustawienia są pomijane w codziennym ruchu HTTP.
* **Likwidacja Race Condition w numeracji zamówień:** Wdrożono transakcje `BEGIN IMMEDIATE TRANSACTION` oraz pętlę retry-loop (5 prób z losowym jitterem 20–80 ms) w `B2bRepository::createOrder()` i `OrderModel::createOrder()`.
* **Ujednolicenie trybu SQLite WAL:** Włączono `PRAGMA journal_mode = WAL`, `PRAGMA busy_timeout = 5000`, `PRAGMA synchronous = NORMAL` oraz `foreign_keys = ON` w `OrderModel` (wcześniej baza zamówień działała w trybie blokującym DELETE).

### C. Mechanizm Idempotencji i ochrona przed dublowaniem zamówień (Punkt 2)
* **Klucz idempotencji (`idempotency_key`):**
  * Dodano kolumnę i indeks do tabel `b2b_orders` oraz `orders`.
  * Na froncie (`views/b2b/catalog.php` oraz `views/order/index.php`) formularze generują unikalny token transakcji wysyłany z zamówieniem.
  * Backend (`B2bController::actionSaveOrder` i `OrderController::actionSave`) sprawdza istnienie zamówienia o danym kluczu. W przypadku powtórzonego żądania (kliknięcie retry, zerwane połączenie) zwraca istniejące zamówienie bez tworzenia duplikatu.
* **Testy:** Dodano zautomatyzowane testy jednostkowe i integracyjne:
  * `tests/test_sqlite_concurrency.php`
  * `tests/test_order_idempotency.php`

---

## 2. Od czego zaczynamy jutro (Kolejne punkty grillowania)

* **🔥 Punkt 3: Stany magazynowe i asynchroniczność cen / wagi**
  * Obsługa braku towaru w magazynie (towar sprzedany w trakcie, gdy sklep ma otwarty koszyk).
  * Obsługa wagi rzeczywistej po skompletowaniu (skrzynka pomidorów waży np. 10,4 kg zamiast 10,0 kg) i korekta wartości zamówienia w ERP.
* **🔥 Punkt 4: Polityka backupów SQLite i wdrożeń**
  * Bezpieczny snapshot bazy SQLite w trybie WAL (`VACUUM INTO` / cron backup).
  * Zabezpieczenie katalogu `db/` przed nadpisaniem przy deploymentach.
* **🔥 Punkt 5: Pozycje spoza cennika (Off-Catalog) w ERP**
  * Ustalenie zasad wyceny i importu pozycji bezcennych do Subiekta GT/Nexo, Optimy, Symfonii i Wf-Maga.
