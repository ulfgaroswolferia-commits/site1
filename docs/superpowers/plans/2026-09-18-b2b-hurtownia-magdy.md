# Portal B2B Hurtownia Magdy — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Budowa kompletnej platformy B2B dla lokalnej hurtowni warzyw i owoców „Hurtownia Magdy” łączącej panel hurtownika (codzienny import cennika `.xlsx` z pamięcią klatek/opakowań, szybka edycja cen i braków, lista zamówień ze statusami, zarządzanie klientami z generowaniem linków dostępowych) oraz panel klienta B2B (błyskawiczny dostęp tokenem, asystent pełnych opakowań Box Optimizer, klawiatura hurtowa, koszyk, generowanie arkusza kompletacji `.xlsx` i powiadomienia e-mail).

**Architecture:** Moduł MVC w TwiiCoreF zarejestrowany pod trasą `b2b` (`B2bController`). Warstwa danych oparta o uniwersalne repozytorium PDO `B2bRepository` (domyślnie SQLite `db/b2b.sqlite`, przygotowane na MySQL w `program/config/data.php`). Wykorzystanie bibliotek `XlsxParser` do importu cenników, `XlsxWriter` do generowania arkuszy kompletacji oraz `Mailer` do powiadomień. Interfejsy zbudowane w Tailwind CSS z subtelnymi akcentami świeżych warzyw i owoców.

**Tech Stack:** PHP 8.3, TwiiCoreF MVC, PDO SQLite / PDO MySQL, Tailwind CSS (CDN), Vanilla JavaScript, OpenXML (ZipArchive & SimpleXML), PHPMailer.

**Spec:** `docs/superpowers/specs/2026-09-18-b2b-hurtownia-magdy-design.md`

## Global Constraints

- Kompatybilność z PHP 8.3 i istniejącą strukturą TwiiCoreF.
- Gotowość na MySQL: tabele i zapytania w dialekcie ANSI SQL kompatybilnym z SQLite i MySQL; przełączanie w `program/config/data.php`.
- Wszystkie akcje administracyjne zabezpieczone `$this->requireAuth()`.
- Wszystkie akcje klienta B2B zabezpieczone autoryzacją sesyjną klienta (token lub login/hasło).
- Wszystkie żądania POST zabezpieczone tokenem CSRF (`Tools::requireCsrf()`).
- Wszystkie wartości w widokach bezpiecznie escapowane przez `Tools::h()`.
- Pliki generowane zapisywane w `storage/b2b/orders/`.

---

### Task 1: Konfiguracja modułu i tras (`b2b`)

**Files:**
- Modify: `program/config/data.php:95-120`
- Create: `storage/b2b/orders/.gitkeep`
- Test: `tests/test_b2b_config.php`

**Interfaces:**
- Produces:
  - Trasa `'b2b' => 'action'` w `Config::$routes`
  - Stałe: `B2B_DB_DRIVER` ('sqlite' / 'mysql'), `B2B_MYSQL_HOST`, `B2B_MYSQL_PORT`, `B2B_MYSQL_NAME`, `B2B_MYSQL_USER`, `B2B_MYSQL_PASS`
  - Katalog `storage/b2b/orders/`

- [ ] **Step 1: Napisz test weryfikujący konfigurację w `tests/test_b2b_config.php`**

```php
<?php
define('BASE_PATH', 'C:/laragon/www');
require_once BASE_PATH . '/program/config/data.php';

$routes = Config::get('routes');
$hasB2bRoute = isset($routes['b2b']) && $routes['b2b'] === 'action';
$hasDriverConst = defined('B2B_DB_DRIVER');
$hasStorage = is_dir(BASE_PATH . '/storage/b2b/orders');

echo "1. Trasa 'b2b': " . ($hasB2bRoute ? "OK" : "BRAK") . "\n";
echo "2. Stała B2B_DB_DRIVER: " . ($hasDriverConst ? "OK" : "BRAK") . "\n";
echo "3. Katalog storage/b2b/orders: " . ($hasStorage ? "OK" : "BRAK") . "\n";

exit(($hasB2bRoute && $hasDriverConst && $hasStorage) ? 0 : 1);
```

- [ ] **Step 2: Uruchom test i upewnij się, że kończy się błędem**

Run: `& "C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe" tests/test_b2b_config.php`
Expected: Kod wyjścia 1.

- [ ] **Step 3: Zaktualizuj `program/config/data.php` i utwórz katalogi**

W `program/config/data.php` dodaj sekcję konfiguracyjną modułu B2B Hurtownia Magdy oraz zarejestruj trasę `'b2b' => 'action'` w `Config::$routes`. Utwórz katalog `storage/b2b/orders`.

- [ ] **Step 4: Uruchom test ponownie i potwierdź sukces**

Run: `& "C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe" tests/test_b2b_config.php`
Expected: Kod wyjścia 0 (Wszystkie punkty OK).

- [ ] **Step 5: Zacommituj zmiany**

```bash
git add program/config/data.php tests/test_b2b_config.php
git commit -m "feat(b2b): zarejestruj trase b2b i konfiguracje bazy danych"
```

---

### Task 2: Repozytorium danych PDO (`B2bRepository`)

**Files:**
- Create: `program/model/B2bRepository.php`
- Test: `tests/test_b2b_repository.php`

**Interfaces:**
- Produces:
  - `B2bRepository::__construct()`
  - `B2bRepository::initDatabase(): void`
  - `B2bRepository::createClient(array $data): int`
  - `B2bRepository::getClientByToken(string $token): ?array`
  - `B2bRepository::getClientByLogin(string $login): ?array`
  - `B2bRepository::getAllClients(): array`
  - `B2bRepository::updateClient(int $id, array $data): bool`
  - `B2bRepository::saveProductsBatch(array $products, bool $clearOld = true): int`
  - `B2bRepository::getActiveProducts(?string $category = null): array`
  - `B2bRepository::updateProduct(int $id, array $data): bool`
  - `B2bRepository::toggleProductAvailability(int $id, ?int $forceStatus = null): bool`
  - `B2bRepository::getPackageRule(string $name): ?array`
  - `B2bRepository::savePackageRule(string $name, float $size, string $packageUnit, string $unit): void`
  - `B2bRepository::createOrder(array $orderData, array $items): int`
  - `B2bRepository::getOrderById(int $id): ?array`
  - `B2bRepository::getOrderItems(int $orderId): array`
  - `B2bRepository::getAllOrders(int $limit = 100, ?string $status = null): array`
  - `B2bRepository::getClientOrders(int $clientId, int $limit = 50): array`
  - `B2bRepository::updateOrderStatus(int $id, string $status): bool`
  - `B2bRepository::generateOrderNumber(): string`

- [ ] **Step 1: Napisz test TDD dla `B2bRepository` w `tests/test_b2b_repository.php`**

Test powinien weryfikować:
1. Inicjalizację bazy i utworzenie 5 tabel (`b2b_clients`, `b2b_products`, `b2b_package_rules`, `b2b_orders`, `b2b_order_items`).
2. Tworzenie klienta i wyszukiwanie po `auth_token`.
3. Zapis produktów z regułami opakowań (klatki/skrzynki) i przełączanie dostępności.
4. Składanie zamówienia z przeliczeniem na klatki i zmianę statusu zamówienia.

- [ ] **Step 2: Uruchom test i potwierdź błąd braku klasy**

Run: `& "C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe" tests/test_b2b_repository.php`
Expected: FAIL z błędem braku klasy `App\B2bRepository`.

- [ ] **Step 3: Zaimplementuj `App\B2bRepository` w `program/model/B2bRepository.php`**

Napisz klasę `B2bRepository` zgodną z przestrzenią nazw `App\` (autoloader w TwiiCoreF) z obsługą PDO SQLite (lub MySQL w zależności od `B2B_DB_DRIVER`), transakcjami i schematem tabel zgodnym ze specyfikacją.

- [ ] **Step 4: Uruchom test i potwierdź przejście wszystkich asercji**

Run: `& "C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe" tests/test_b2b_repository.php`
Expected: Wszystkie testy repozytorium zakończone sukcesem.

- [ ] **Step 5: Zacommituj model repozytorium**

```bash
git add program/model/B2bRepository.php tests/test_b2b_repository.php
git commit -m "feat(b2b): dodaj model B2bRepository z pelna obsluga schematu PDO"
```

---

### Task 3: Backend Kontrolera `B2bController` (Zarządzanie, Import i API)

**Files:**
- Create: `program/script/B2bController.php`
- Test: `tests/test_b2b_controller_admin.php`

**Interfaces:**
- Produces:
  - `actionAdmin()` — widok panelu hurtownika
  - `actionImportUpload()` — upload cennika .xlsx z autodetekcją
  - `actionImportProcess()` — wdrożenie cennika z dopasowaniem reguł opakowań
  - `actionUpdateProduct()` — szybka edycja ceny, jednostki i klatki na żywo
  - `actionToggleProduct()` — szybkie włączenie/wyłączenie towaru ze sklepu
  - `actionCreateClient()` — dodanie nowego odbiorcy B2B i wygenerowanie tokenu
  - `actionUpdateOrderStatus()` — zmiana statusu zamówienia (Nowe -> W kompletacji -> Zrealizowane)

- [ ] **Step 1: Napisz test integracyjny HTTP w `tests/test_b2b_controller_admin.php`**

Test zaloguje się do panelu admina, wywoła endpointy API (`/b2b/update-product`, `/b2b/toggle-product`, `/b2b/create-client`, `/b2b/update-order-status`) i sprawdzi odpowiedzi JSON oraz zapis w bazie.

- [ ] **Step 2: Uruchom test i sprawdź błąd braku kontrolera**

Run: `& "C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe" tests/test_b2b_controller_admin.php`
Expected: 404 lub błąd braku `B2bController`.

- [ ] **Step 3: Zaimplementuj metody administracyjne w `B2bController.php`**

Utwórz `program/script/B2bController.php` dziedziczący po `AppController` z zabezpieczeniami `$this->requireAuth()`, walidacją CSRF oraz integracją z `B2bRepository` i `XlsxParser`.

- [ ] **Step 4: Uruchom test i potwierdź sukces API administracyjnego**

Run: `& "C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe" tests/test_b2b_controller_admin.php`
Expected: Kod 200, statusy JSON `ok: true`.

- [ ] **Step 5: Zacommituj metody backendu hurtowni**

```bash
git add program/script/B2bController.php tests/test_b2b_controller_admin.php
git commit -m "feat(b2b): dodaj B2bController z obsluga zarzadzania i importu cennika"
```

---

### Task 4: Widok Panelu Hurtownika (`views/b2b/admin.php`)

**Files:**
- Create: `views/b2b/admin.php`
- Test: `tests/test_b2b_admin_view.php`

**Features:**
- Tailwind CSS z estetycznymi akcentami tła
- Zakładka 1: Aktualna oferta i import cennika (Dropzone .xlsx, mapowanie, tabela asortymentu z natychmiastową edycją ceny/klatki i przełącznikiem In Stock / Out of Stock)
- Zakładka 2: Spływające zamówienia z badge'ami statusów, podglądem pozycji i zmianą statusu jednym klikiem
- Zakładka 3: Baza klientów z przyciskiem kopiowania stałego linku z tokenem do schowka

- [ ] **Step 1: Napisz test renderowania widoku w `tests/test_b2b_admin_view.php`**

Sprawdź obecność zakładek (*Cennik & Oferta*, *Zamówienia*, *Klienci*), formularza uploadu, selektora statusów i skryptów Tailwind.

- [ ] **Step 2: Utwórz widok `views/b2b/admin.php`**

Zbuduj nowoczesny, responsywny interfejs z Tailwind CSS, kartami, przełącznikiem zakładek (Vanilla JS), modalem podglądu zamówień oraz asynchronicznymi wywołaniami AJAX (`fetch`) do aktualizacji pozycji na żywo.

- [ ] **Step 3: Uruchom test renderowania widoku hurtownika**

Run: `& "C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe" tests/test_b2b_admin_view.php`
Expected: PASS.

- [ ] **Step 4: Zacommituj widok panelu hurtownika**

```bash
git add views/b2b/admin.php tests/test_b2b_admin_view.php
git commit -m "feat(b2b): utworz widok panelu hurtownika w Tailwind CSS"
```

---

### Task 5: Panel Klienta B2B (`views/b2b/catalog.php` & `login.php`) z Asystentem Opakowań

**Files:**
- Create: `views/b2b/catalog.php`
- Create: `views/b2b/login.php`
- Modify: `program/script/B2bController.php` (autologowanie z tokena w URL `?token=...`, akcje `actionIndex()`, `actionLogin()`, `actionLogout()`, `actionSaveOrder()`, `actionHistory()`)
- Test: `tests/test_b2b_catalog_flow.php`

**Features:**
- Natychmiastowa autoryzacja z linku z tokenem (`GET /b2b?token=...`) z powitaniem sklepu
- Katalog z filtrami kategorii (*Warzywa, Owoce, Cytrusy, Zioła*) i wyszukiwarką na żywo
- Klawiatura hurtowa (Tab/Enter) do szybkiego wprowadzania ilości
- Asystent pełnych opakowań (Box Optimizer): badge opakowania zbiorczego (np. `Klatka: 7 szt.`) oraz sugestia zaokrąglenia, gdy wpisano niepełną ilość
- Pływający koszyk, podsumowanie z uwagami dla kierowcy i finalizacja zamówienia

- [ ] **Step 1: Napisz test przepływu klienta w `tests/test_b2b_catalog_flow.php`**

Test zweryfikuje:
1. Wejście z tokenem klienta i poprawne ustawienie sesji B2B.
2. Złożenie zamówienia z produktem z klatkami (np. 14 szt. mango = 2 klatki).
3. Prawidłowe wyliczenie kwoty i pozycji w bazie.

- [ ] **Step 2: Zaimplementuj obsługę sesji klienta i akcje zamawiania w `B2bController.php`**

Obsłuż parametr `token` w `actionIndex()`: jeśli podano poprawny token, zaloguj klienta do sesji `b2b_client_id`. Zaimplementuj `actionSaveOrder()` z walidacją i przeliczeniem opakowań zbiorczych.

- [ ] **Step 3: Utwórz widok `views/b2b/catalog.php` i `views/b2b/login.php` z Tailwind CSS**

Zbuduj interfejs klienta: nagłówek z nazwą sklepu, wyszukiwarka na żywo, asystent opakowań Box Optimizer, pływający pasek zamówienia i modal podsumowania.

- [ ] **Step 4: Uruchom test przepływu zamawiania i potwierdź sukces**

Run: `& "C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe" tests/test_b2b_catalog_flow.php`
Expected: Wszystkie testy autoryzacji i zamawiania zakończone pomyślnie.

- [ ] **Step 5: Zacommituj moduł klienta B2B**

```bash
git add program/script/B2bController.php views/b2b/catalog.php views/b2b/login.php tests/test_b2b_catalog_flow.php
git commit -m "feat(b2b): dodaj panel zamawiania klienta B2B z asystentem opakowan"
```

---

### Task 6: Arkusz kompletacji magazynowej (.xlsx), powiadomienia e-mail i launcher

**Files:**
- Modify: `program/script/B2bController.php` (generowanie pliku kompletacji i wysyłka e-mail)
- Modify: `views/home/launcher.php` (dodanie kafelka modułu B2B)
- Test: `tests/test_b2b_e2e_full.php`

**Features:**
- Wygenerowanie arkusza `.xlsx` dla magazynu w `storage/b2b/orders/kompletacja_B2B_...xlsx` z kolumną odhaczania magazyniera i rozbiciem na klatki/skrzynki
- Pobieranie arkusza przez hurtownika (`/b2b/download/id/{id}`)
- Wysłanie powiadomienia e-mail z załącznikiem przez `Mailer`
- Dodanie dedykowanego kafelka **Hurtownia Magdy (Portal B2B)** na pulpicie głównym (`/home/index`)

- [ ] **Step 1: Napisz test E2E całego cyklu B2B w `tests/test_b2b_e2e_full.php`**

Test sprawdza pełny łańcuch:
1. Dodanie klienta i wygenerowanie tokenu.
2. Zapis cennika z produktami w klatkach.
3. Klient wchodzi przez token i składa zamówienie.
4. Generuje się poprawny fizyczny plik `.xlsx` kompletacji na dysku.
5. Hurtownik zmienia status na `processing` i `completed`.

- [ ] **Step 2: Zaimplementuj generowanie arkusza kompletacji i pobieranie w `B2bController.php`**

Użyj `XlsxWriter` do wygenerowania czytelnej tabeli kompletacji z kolumną kontrolną „Skompletowano [ ]” i rozbiciem na klatki. Podepnij bezpieczną wysyłkę przez `Mailer::send()`.

- [ ] **Step 3: Dodaj kafelek „Hurtownia Magdy” w `views/home/launcher.php`**

Wstaw kartę modułu B2B prowadzącą do `/b2b/admin` (dla administratora) lub `/b2b` (dla klientów).

- [ ] **Step 4: Uruchom pełny test E2E i potwierdź sukces**

Run: `& "C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe" tests/test_b2b_e2e_full.php`
Expected: Wszystkie kroki cyklu B2B zakończone pomyślnie (100% PASS).

- [ ] **Step 5: Zacommituj finalne integracje**

```bash
git add program/script/B2bController.php views/home/launcher.php tests/test_b2b_e2e_full.php
git commit -m "feat(b2b): dodaj generowanie arkusza kompletacji xlsx i kafelek w launcherze"
```
