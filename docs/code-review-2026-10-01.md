# Code review — site1 (TwiiCoreF + moduł Zamówienia + B2B „Hurtownia Magdy")

Data: 2026-10-01 · Stan: `main` @ `03c8c0f` · Zakres: cały projekt, nacisk na **bezpieczeństwo** i **interfejs**.

Metoda: przegląd kodu (backend, widoki/JS, UI) + weryfikacja kluczowych znalezisk na żywo
(`php -S` na PHP 8.3.33, lokalny `data.php`). Oznaczenia: **[zweryfikowane]** — odtworzone
lub prześledzone w kodzie; **[prawdopodobne]** — wynika z kodu, zależy od konfiguracji/środowiska.

`php -l` (PHP 8.3): wszystkie pliki czyste; jedynie deprecation w `program/lib/class.pop3.php:145`.

---

## Podsumowanie

| # | Problem | Waga |
|---|---|---|
| S1 | Logowanie do panelu hurtownika jako `admin` z **pustym hasłem** | **Krytyczna** |
| S2 | Wstrzyknięcie rekordów do plików eksportu ERP (CR/LF w uwagach / nazwach) | Wysoka |
| S3 | Hashe haseł i tokeny wszystkich klientów w źródle HTML panelu admina | Wysoka |
| S4 | Fatal error (`App::error()`, `getParam()` nie istnieją) + `display_errors` ze ścieżkami | Średnia |
| S5 | Zablokowany/usunięty klient zachowuje dostęp w trwającej sesji | Średnia |
| S6 | Klient może wybrać dowolną datę dostawy (omija cut-off) | Średnia |
| S7 | Brak regeneracji ID sesji przy logowaniu klienta B2B | Średnia |
| S8 | Brak limitu prób logowania; `APP_USERS` akceptuje hasła jawne / sam hash | Średnia |
| S9 | Tailwind Play CDN (bez SRI) z dostępem do DOM panelu admina, brak CSP | Średnia |
| C1 | Kod wymaga **PHP 8.0+** — nie uruchomi się na 3W (5.3) ani 3W-ADAX (7.4) | Wdrożeniowa |
| U1 | Przycisk „Wróć do edycji” w modalu zamówienia B2B nie działa | Wysoka (UI) |
| U2 | Katalog B2B nieużywalny na telefonie, brak linku do historii na mobile | Wysoka (UI) |

**Status (2026-10-01, niezacommitowane):**
- Naprawione: S1, S2, S3 (bez hasza; `auth_token` zostaje, bo panel buduje z niego linki), S4, S5, S6, S7, S8
  (`APP_USERS`: hash tylko przez `password_verify`; limit 5 prób / 15 min / IP — `program/lib/LoginThrottle.php`).
- Naprawione z „niższej wagi”: limit rozmiaru i MIME uploadu B2B (`Tools::validateXlsxUpload()`), limit ilości (100 000)
  i pozycji (500), `rel="noopener"`, `autocomplete` w formularzach logowania i edycji klienta, escapowanie `$statusLabel`,
  `.sftpignore` (`.worktrees/`, `cookies.txt`, pliki runtime).
- UI naprawione: U1 („Wróć do edycji”, Esc, kliknięcie w tło, ARIA modalu), brak `escapeHtml` w `b2b/history.php`,
  przewijanie tabeli historii na mobile, odmiana „pozycji”, etykieta „Pobierz specyfikację (Excel .xlsx)”.
- Testy: 55/55 (naprawione kodowanie 35 plików, nieaktualne asercje, brak fixture `magda`, kolizje numerów zamówień).
- Decyzje i realizacja: C1 — serwer docelowy PHP 8.x (kod bez zmian, opis w `CLAUDE.md`); S9/U3 — Tailwind v3
  budowany statycznie do `assets/css/b2b.css` (`bin/tailwind/README.md`) + nagłówek CSP; `db/` w `.sftpignore`.
- UI zrealizowane: U2 (karty produktów < 640 px, przyciski 44 px, historia na mobile), U4 (szkic koszyka/zamówienia
  w localStorage + `beforeunload`), U5 (komunikat „Sesja wygasła”), toasty i błędy przy polach zamiast `alert()`,
  format kwot pl-PL (`Tools::money()`, `formatPLN`), role/Esc/fokus w modalach, widoczny fokus, kontrast,
  `shadow-2xs/xs` → `shadow-sm`, licznik zakładki cennika czytelny na nieaktywnej zakładce.
- Pozostaje (świadomie): `'unsafe-inline'` w CSP do czasu przeniesienia inline JS/`onclick` do plików;
  pułapka fokusu w modalach panelu; Google Fonts z zewnątrz; aktualizacja PHPMailera; `Crypt.php` (nieużywany).

Sugerowana kolejność napraw: **S1 → S3 → S2 → S4 → C1 (decyzja o serwerze) → S5–S8 → U1/U2 → reszta.**

---

## 1. Bezpieczeństwo

### S1. [Krytyczna] Logowanie admina z pustym hasłem — [zweryfikowane]
`program/script/B2bController.php:567-578`

```php
$adminPass = defined('APP_PASSWORD') ? APP_PASSWORD : '';
...
} elseif ($adminLogin !== '' && $login === $adminLogin && $pass === $adminPass) {
```

`data.php` definiuje tylko `APP_PASSWORD_HASH`, a `APP_PASSWORD` nie istnieje, więc `$adminPass === ''`.
Odtworzone lokalnie: `POST /b2b/login` z `login=admin&password=` + token CSRF z formularza
zwraca **303 → /b2b/admin**, a panel odpowiada 200. Dostęp obejmuje klientów, tokeny, cennik i eksporty ERP.
Dodatkowo porównanie odbywa się na jawnym tekście, `===` nie działa w stałym czasie, a hash zostaje pominięty.
Commit e7d0f91 („admin login uses password_verify”) naprawił to tylko w `HomeController`.

**Naprawa:** jedna wspólna metoda weryfikacji admina (np. w `AppController`) oparta na
`password_verify($pass, APP_PASSWORD_HASH)`, używana w obu kontrolerach. Pusty login lub hasło od razu odrzucać.
Sprawdzić `data.php` na serwerze produkcyjnym. Jeśli nie ma tam `APP_PASSWORD`, dziura jest otwarta.

### S2. [Wysoka] Injection w plikach eksportu ERP — [zweryfikowane w kodzie]
`program/lib/ErpExporter.php:261-263` (EPP), `:426-450` (Symfonia), źródła danych:
`B2bController.php:209` (`notes`), `B2bRepository.php:621` (nazwa pozycji spoza cennika, tylko `strip_tags`).

Eksporter escapuje wyłącznie `"`, a `\r\n` przechodzi. Klient B2B może w uwagach wpisać
`"\r\n[ZAWARTOSC]\r\n1,9,1,1000,0.01,...`, co tworzy dodatkowe rekordy lub pozycje z dowolną ceną w pliku
importowanym przez księgowość do Subiekta/Symfonii. Skutek zależy od parsera ERP.

**Naprawa:** jedna funkcja sanityzacji pól tekstowych eksportu: usuwa `\r \n \t` i znaki sterujące
(`preg_replace('/[\x00-\x1F\x7F]/u', ' ', ...)`) i jest stosowana do każdego pola w każdym formacie.
Limit długości `notes` przy zapisie zamówienia.

### S3. [Wysoka] Hashe haseł i tokeny klientów w HTML — [zweryfikowane]
`views/b2b/admin.php:1326` ← `B2bRepository::getAllClients()` (`SELECT * FROM b2b_clients`, `:355`).
Również odpowiedź JSON `b2b/updateclient` (`B2bController.php:~1062`) zwraca pełny wiersz.

`CLIENTS_DATA` zawiera `password_hash` i `auth_token` każdego klienta. W połączeniu z S9 (obcy skrypt
z CDN w tym samym DOM) albo dowolnym XSS daje to wyciek całej bazy hashy do łamania offline i przejęcie kont.

**Naprawa:** w `getAllClients()` jawna lista kolumn bez `password_hash`. Do JS przekazać
tylko pola używane w modalu. Token pokazywać tylko na żądanie (osobny endpoint).

### S4. [Średnia] Fatal errors + wyciek ścieżek — [zweryfikowane]
`B2bController.php:314-440, 1222`: wywołania `App::error()` i `$this->getParam()`, które nie istnieją
(jest `param()` w `AppController:139`). Odtworzone: `GET /b2b/orderdetails` zwraca
`Fatal error: Call to undefined method B2bController::getParam()` z pełną ścieżką `C:\Users\...` i stack trace,
bo lokalnie `APP_ENV='development'`. Autoryzacja przypadkiem działa, bo skrypt umiera wcześniej,
ale każda odpowiedź 403/404 w B2B to biały ekran lub trace.

**Naprawa:** dodać `App::error(int $code, string $msg)` (albo użyć istniejącego mechanizmu 404)
i zamienić `getParam` na `param`. Upewnić się, że na serwerze jest `APP_ENV='production'`.

### S5. [Średnia] Odebranie dostępu nie działa na aktywne sesje — [zweryfikowane w kodzie]
Sesja trzyma tylko `b2b_client_id`. `regenerate_token` i `toggleclient` jej nie unieważniają.
`actionHistory` (500-518), `actionOrderdetails` (1220), `actionDownload` i `actionExporterp` nie sprawdzają
`is_active`. Zablokowany lub usunięty klient dalej czyta historię i pobiera pliki.
`requireClientAuth()` (linia ~18) to martwy kod.

**Naprawa:** jeden guard `requireClientAuth()` wołany w każdej akcji klienta. Ładuje klienta z bazy,
sprawdza `is_active` i porównuje `auth_token` z sesją. Rotacja tokenu wtedy wylogowuje klienta.

### S6. [Średnia] Dowolna data dostawy — [zweryfikowane w kodzie]
`B2bController.php:212-213`: data jest sprawdzana tylko regexem `\d{4}-\d{2}-\d{2}`. Klient omija godzinę
graniczną i dni dostaw, może też podać datę przeszłą.
**Naprawa:** akceptować wyłącznie wartości z `getDeliverySchedule()['options']`.

### S7. [Średnia] Session fixation przy logowaniu klienta B2B — [prawdopodobne]
`B2bController.php:589-593`: brak `session_regenerate_id(true)`. Ma go `HomeController:96`, choć commit e7d0f91 twierdzi inaczej.
Brak `session.use_strict_mode`. Podobnie `b2b?token=` loguje bez regeneracji (login CSRF).
**Naprawa:** regeneracja przy każdym logowaniu i wylogowaniu, `ini_set('session.use_strict_mode', '1')` w `headers.php`.

### S8. [Średnia] Logowanie: brak rate limitu, słabe `APP_USERS` — [zweryfikowane w kodzie]
`HomeController:53,76`, `B2bController:538,574`:
- brak limitu prób, więc brute force jest nieograniczony,
- `password_verify(...) || hash_equals($expected, $pass)`: wpis w `APP_USERS` działa jako hasło jawne,
  a jeśli jest hashem, to **sam ciąg hasha też otwiera konto**,
- każdy użytkownik `APP_USERS` dostaje uid=1 (pełny admin, brak ról).

**Naprawa:** w `APP_USERS` tylko hashe i tylko `password_verify`. Licznik nieudanych prób
(IP + login, w SQLite) z opóźnieniem lub blokadą.

### S9. [Średnia] Zewnętrzny JS bez SRI + brak CSP — [zweryfikowane]
`<script src="https://cdn.tailwindcss.com">` w `views/b2b/admin.php:24`, `catalog.php:15`,
`history.php:13`, `login.php:13`. Skrypt ma pełny dostęp do DOM (CSRF token, `CLIENTS_DATA`).
SRI jest niemożliwe, bo skrypt generowany jest dynamicznie. W `catalog.php` wykonuje się przed `replaceState` czyszczącym `?token=`.
**Naprawa:** zbudować Tailwind raz do `assets/css/b2b.css` (Tailwind CLI) i dodać nagłówek CSP
(`default-src 'self'`; inline JS przenieść do plików albo użyć nonce).

### Niższa waga

| Problem | Miejsce | Naprawa |
|---|---|---|
| Upload cennika B2B bez limitu rozmiaru/MIME (w `OrderController` jest); `XlsxParser::getFromName()` bez limitu po rozpakowaniu → zip bomb (tylko admin) | `B2bController.php:642-670`, `XlsxParser` | limit rozmiaru pliku i rozmiaru wpisów ZIP; sprzątanie `tmp/` |
| Brak górnej granicy ilości (`1e300`) i liczby pozycji w zamówieniu | `B2bRepository.php:607-613` | limit np. 10 000 / 500 pozycji |
| `storage/`, `tmp/` (xlsx z danymi klientów, przewidywalne nazwy) chronione tylko `mod_rewrite` | `.htaccess` | `.htaccess` z `Require all denied` w tych katalogach |
| `.worktrees/` nie jest w `.sftpignore`, a może zawierać `data.php`, `cookies.txt` | `.sftpignore` | dopisać |
| HSTS i `secure` ufają `X-Forwarded-Proto`; redirect HTTPS na `%{HTTP_HOST}` | `headers.php:30`, `.htaccess` | ufać nagłówkowi tylko za znanym proxy; stała domena w redirect |
| PHPMailer 5.1 (2009, CVE-2016-10033/10045); dziś wektor nieosiągalny (`From` stałe) | `program/lib/class.*.php` | aktualizacja do 6.x |
| `OrderController:303`: wysyłka na dowolny `recipient_email` z POST (za loginem) | | biała lista lub walidacja |
| Wylogowanie przez GET bez CSRF; `b2b/logout` bez regeneracji ID | `HomeController`, `B2bController` | POST + CSRF, `session_regenerate_id(true)` |
| JSON-y zwracają `$e->getMessage()` | `B2bController` | ogólny komunikat, szczegóły do logu |
| `Crypt.php`: AES-128-CBC, stały IV, brak MAC, klucz obcinany do 16 znaków; nieużywany | `program/lib/Crypt.php` | usunąć albo AES-256-GCM z losowym IV |
| Pole hasła w modalu edycji klienta bez `autocomplete="new-password"`, więc przeglądarka może wpisać dane admina | `views/b2b/admin.php:1225` | `autocomplete="new-password"` / `off` |
| Brak escapowania (obrona w głąb): `$statusLabel` (`match default => $st`), `${r}`, `'<?= $csrfToken ?>'` / `$base` w JS | `b2b/history.php:107-113`, `order/index.php:1967`, `admin.php:1324`, `catalog.php:590` | `Tools::h()` / `Tools::j()` |
| `target="_blank"` bez `rel="noopener"` | `admin.php:115,1135`, `catalog.php:567`, `history.php:225`, `dashboard.php:917` | dodać `rel` |
| Dashboard pokazuje `PHP_VERSION` | `dashboard.php:672` | usunąć |
| Token klienta w URL trafia do logów Apache, nie wygasa, leży w bazie jawnym tekstem | | hash tokenu w bazie; termin ważności |

### Co jest zrobione dobrze
- Wszystkie zapytania przez prepared statements; `ORDER BY` z białej listy, `LIMIT` rzutowany na int.
- Ceny zamówień liczone po stronie serwera; ujemne i nieskończone ilości odrzucane.
- CSRF na wszystkich akcjach POST (`hash_equals`), cookie `HttpOnly` + `SameSite=Lax`.
- Kontrola właściciela (brak IDOR) w `orderdetails`, `download` i `exporterp`.
- Tokeny klientów 128-bit z `random_bytes`, hasła klientów przez `password_hash`.
- Uploady z losowymi nazwami, `basename()` na identyfikatorach, libxml bez `NOENT` (XXE zamknięte).
- XLSX zapisuje tekst jako `inlineStr`, więc nie ma formula injection.
- Widoki: `Tools::h()` (ENT_QUOTES, UTF-8) konsekwentnie, w JS `textContent` / `escapeHtml`. Nie znaleziono XSS.
- `App::redirect` bez open redirect; JSON z nagłówkiem `application/json; charset=utf-8`.

---

## 2. Zgodność ze środowiskiem

### C1. Kod wymaga PHP ≥ 8.0 — [zweryfikowane]
- `match` — `views/b2b/history.php:100,107` (i w B2B)
- union types `float|int` — `B2bRepository.php:865`, `XlsxWriter.php:462`
- `str_contains` / `str_starts_with` / `str_ends_with` — `XlsxParser.php` (wiele), `B2bRepository.php:941`

Na PHP 8.3 wszystko przechodzi `php -l`, a plany w `docs/superpowers/plans/` zakładają PHP 8.3.
Na serwerach 3W (5.3) i 3W-ADAX (7.4) moduły B2B i Zamówienia się nie uruchomią.
**Decyzja:** potwierdzić serwer docelowy. Jeśli ma to być 7.4: zamienić `match` na `switch` lub tablice,
usunąć union types i dodać polyfille `str_*` do `Tools`.

Inne: `data.php` ustawia `DB_CHARSET='utf8mb4'` (dotyczy MySQL; B2B działa na SQLite).
Szablon `program/config/data.sample.php` opisany w README i `.gitignore` **nie istnieje w repo**.

---

## 3. Interfejs (UI/UX)

### Architektura widoków
Każdy widok jest osobnym dokumentem HTML (`$this->layout = ''`), więc `views/layout/main.php`, `app.css`
i `app.js` praktycznie nie są używane. Są dwa niezależne systemy wyglądu:
- panel wewnętrzny (`order/*`, `home/*`): ok. 2600 linii CSS inline, blok `:root{...}` skopiowany w 6 plikach,
  `.btn-primary` ×3, samo `order/index.php` ma 1172 linie CSS;
- B2B: Tailwind Play CDN + Google Fonts.

`views/b2b/admin.php` (3531 linii) to ok. 1320 linii HTML i ok. 2200 linii JS inline (41 × `onclick=`,
funkcje globalne, cały dokument wydruku w template stringu `:3204-3306`).

### Wysoki priorytet
1. **„Wróć do edycji” nie działa.** `views/b2b/catalog.php:535/608`: `btnCancelModal` pobrany, bez listenera.
   Modal nie zamyka się też przez Esc ani kliknięcie w tło. Klient utyka w podsumowaniu zamówienia.
   *Naprawa:* `btnCancelModal.addEventListener('click', closeModal)` i obsługa Esc, jak w `b2b/history.php:331`.
2. **Katalog B2B na telefonie.** Wymaganie ze specyfikacji („pełna optymalizacja pod smartfony, duże przyciski”) nie jest spełnione:
   tabela 7 kolumn ze stałymi szerokościami (`catalog.php:186-192`) przewija się poziomo na 375 px,
   przyciski +/− mają 32 px (`:255/263`), link „Historia” jest `hidden sm:inline-flex` (`:120`), więc na telefonie historii nie ma.
   *Naprawa:* poniżej `sm` układ kart, cele dotyku ≥ 44 px, historia w menu mobilnym.
3. **Tailwind Play CDN na produkcji.** Kompilacja w przeglądarce, ok. 300 KB JS, mignięcie niestylowanej strony, a bez CDN strona jest bez stylów.
   Klasy `animate-in fade-in zoom-in-95` wymagają pluginu, `shadow-2xs` / `shadow-xs` istnieją dopiero w v4, więc nie działają.
   *Naprawa:* wspólna z S9, czyli zbudowany CSS w `assets/`.
4. **Utrata koszyka.** `catalog.php` i `order/index.php` nie mają `beforeunload` ani zapisu szkicu (admin.php ma, `:2069`).
   *Naprawa:* szkic w `localStorage` i ostrzeżenie przy niepustym koszyku.
5. **Wygaśnięcie sesji jako „Błąd sieciowy: Unexpected token <”.** `res.json()` na przekierowaniu HTML
   (`catalog.php:1216`, `admin.php:2429`). *Naprawa:* sprawdzać status i `content-type`, pokazywać „Sesja wygasła — zaloguj się ponownie”.
6. **Historia zamówień ucięta na mobile.** `order/history.php:200-203` ma `.table-wrap{overflow:hidden}` i nie ma żadnego `@media`.
   *Naprawa:* `overflow-x:auto`.
7. **Modal szczegółów zamówienia w historii B2B się wysypuje.** `b2b/history.php:286` wywołuje `escapeHtml()`,
   której w pliku nie ma, więc zamówienie z pozycją spoza cennika kończy się `ReferenceError`. *Naprawa:* zdefiniować funkcję
   (nie usuwać wywołania, bo otworzyłoby to XSS).

### Średni priorytet
- **`alert()` / `confirm()` zamiast UI:** 21 w `admin.php` (obok istniejącego `showToast()`), 11 w `order/index.php`, 4 w `catalog.php`.
  Link klienta w `alert()` (`admin.php:2432`) trudno skopiować. `href="javascript:alert(...)"` (`admin.php:2838, 2843`).
  Walidację pól („Proszę podać nazwę…”, `catalog.php:870`, `order/index.php:2171`) pokazywać przy polu z `aria-invalid`.
- **Format liczb:** wszędzie `number_format(…, '.', ' ')` / `toFixed(2)`, co daje „12.50 zł”. Dodać `Tools::money()` (`,` dziesiętny)
  i `Intl.NumberFormat('pl-PL', {style:'currency', currency:'PLN'})` w JS.
- **Pola ilości:** brak `inputmode="decimal"`; `step=0.5` w polu, a +/− zawsze o 1 (`catalog.php:966`:
  `unit==='szt.' ? 1 : 1`, martwy warunek); brak `aria-label` na polach ilości; etykieta jednostki nachodzi na wartość (`:261`).
- **Modale bez a11y:** brak `role="dialog"`, `aria-modal`, pułapki i powrotu fokusu, Esc. Przyciski „X” jako samo SVG
  bez `aria-label` (`catalog.php:430`, `admin.php:1089, 1184, 1278`).
- **Fokus:** `focus:outline-none` ×20 w `admin.php` bez zamiennika na zakładkach i filtrach; dashboard i `order/history|view` nie mają reguł `:focus`.
- **Kontrast:** 10–11 px `text-slate-400` na bieli (ok. 2,6:1) — `catalog.php:228, 249, 562`. Nie spełnia WCAG AA.
- **Copy:** regresja „Pobierz specyfikację (Excel .xlsx)” → „Pobierz plik” (`catalog.php:572`); anglicyzm „draft” (`:369, 843`);
  błędna odmiana „pozycji” dla 12–14 i 22–24 (`:920`), choć `inflectPolishJs` już istnieje.
- **Login B2B:** brak `autocomplete="username" / "current-password"`; placeholder „np. admin” podpowiada login admina;
  CSRF wysyłany podwójnie (`_csrf` i `csrf_token`).

### Niski priorytet
- Google Fonts w B2B: zależność zewnętrzna i RODO (IP do Google). Lepiej hostować font lokalnie.
- Duplikacja `escapeHtml`, logiki koszyka i formularza „spoza cennika” między `catalog.php` a `order/index.php`.
  Wydzielić `assets/js/cart.js`, wspólne tokeny do `app.css`, JS panelu do `assets/js/b2b-admin.js` (cache, lint),
  wydruk do osobnego widoku PHP.
- Emoji jako ikona (`catalog.php:65`) przy SVG w reszcie interfejsu.

### Co działa dobrze
- `lang="pl"` i meta viewport wszędzie; spójne escapowanie.
- Ochrona przed podwójnym wysłaniem i spinner przy składaniu zamówienia (`catalog.php:1190-1237`).
- Potwierdzenia przy usuwaniu klienta i czyszczeniu koszyka.
- Panel admina: `beforeunload` przy niezapisanych zmianach, Ctrl+S, toasty, poprawny wzorzec ARIA tablist (`:174-220`).
- Przemyślane puste stany, Enter przechodzi do następnego pola ilości, polska odmiana jednostek, podpowiedź „zaokrąglij do pełnej skrzynki”.
- `order/index.php` ma media queries i `prefers-reduced-motion`; `home/login.php` ma poprawne etykiety i autocomplete.

---

## 4. Rozbieżności między commitami a kodem

| Commit | Twierdzenie | Stan faktyczny |
|---|---|---|
| e7d0f91 | admin login przez `password_verify()` | tylko `HomeController`; `B2bController` porównuje z nieistniejącym `APP_PASSWORD` (S1) |
| e7d0f91 | `session_regenerate_id` po loginie | brak przy logowaniu klienta B2B (S7) |
| e7d0f91 | ochrona przed upload DoS | limit tylko w `OrderController`, nie w B2B |
| 03c8c0f | token B2B usunięty z URL (PRG) | działa w przeglądarce, ale token wciąż trafia do logów serwera i nie wygasa |
