# Specyfikacja Projektowa: Portal B2B Hurtownia Magdy

- **Data:** 2026-09-18
- **Projekt:** TwiiCoreF / Hurtownia Magdy
- **Status:** Zaakceptowany przez użytkownika
- **Autor:** Antigravity Team & TwiiCoreF Architecture

---

## 1. Cel i zakres systemu

Platforma B2B **Hurtownia Magdy** służy do automatyzacji codziennego obiegu zamówień pomiędzy lokalną hurtownią warzyw i owoców a jej stałymi odbiorcami B2B (sklepy osiedlowe, warzywniaki, punkty gastronomiczne, restauracje).

System eliminuje konieczność ręcznego przyjmowania zamówień telefonicznie lub przez SMS w godzinach nocnych/wczesnoporannych oraz ułatwia logistykę magazynową dzięki automatycznemu przeliczaniu zamawianych ilości na opakowania zbiorcze (klatki, skrzynki, kartony, worki).

### Kluczowe założenia biznesowe:
1. **Codziennie aktualizowana oferta z pliku Excel (.xlsx):**
   * Hurtownik wgrywa aktualny arkusz cennika hurtowni.
   * System automatycznie wykrywa kolumny (*Towar, Cena, Jednostka, Opakowanie*), pomijając banery i grafiki.
   * Inteligentna pamięć reguł opakowań dopasowuje wielkości opakowań (np. klatka mango = 7 szt., skrzynka pomidorów = 6 kg).
2. **Szybka edycja cennika na żywo w panelu Hurtownika:**
   * Możliwość natychmiastowej zmiany ceny, jednostki, opakowania zbiorczego oraz wyłączania towarów chwilowo niedostępnych bez konieczności ponownego wgrywania pliku Excel.
3. **Błyskawiczny dostęp dla Klientów B2B (Token Link / SMS):**
   * Hurtownik tworzy profile klientów z unikalnym 32-znakowym tokenem dostępowym.
   * Klient klika link ze swojego smartfona i natychmiast przechodzi do składania zamówienia bez wpisywania haseł o 5 rano. Dostępne jest również tradycyjne logowanie loginem i hasłem.
4. **Asystent pełnych opakowań (Box Optimizer):**
   * Interfejs podpowiada klientowi optymalne wielkości zamówienia (np. *„Wybrano 5 szt. mango. Klatka zawiera 7 szt. Zaokrąglij do pełnej klatki [+2]”*).
   * Klawiatura hurtowa: ergonomiczne przeskakiwanie pól ilości za pomocą Tab/Enter.
5. **Generowanie arkusza kompletacji (.xlsx) i powiadomienia e-mail:**
   * Każde zamówienie generuje zoptymalizowany pod kątem magazynu plik Excela z podziałem na pełne opakowania i luz oraz polem odhaczania dla magazyniera.
   * Automatyczna wysyłka e-mail do hurtowni (z załącznikiem `.xlsx`) oraz potwierdzenie dla klienta.
6. **Architektura bazy danych gotowa na MySQL:**
   * Domyślnie działa na plikowej bazie SQLite (`db/b2b.sqlite`) przez warstwę repozytorium PDO.
   * Gotowa do natychmiastowego przełączenia na MySQL w `program/config/data.php` bez żadnych zmian w kodzie aplikacji.
   * Zarezerwowane pole `price_group_id` pod przyszłe grupy rabatowe/cenniki indywidualne.
7. **Nowoczesny design z Tailwind CSS:**
   * Czytelny, kontrastowy interfejs tabelaryczny z subtelnymi akcentami świeżych warzyw i owoców w tle.

---

## 2. Architektura i komponenty systemu (MVC TwiiCoreF)

```
├── db/
│   └── b2b.sqlite                       <- Baza danych SQLite dla modułu B2B
├── storage/
│   └── b2b/
│       └── orders/                      <- Archiwum generowanych arkuszy kompletacji .xlsx
├── program/
│   ├── config/
│   │   └── data.php                     <- Rejestracja trasy 'b2b' oraz stałe DB/SMTP
│   ├── lib/
│   │   ├── XlsxParser.php               <- Bezstanowy parser cennika hurtowni
│   │   ├── XlsxWriter.php               <- Generator arkusza kompletacji .xlsx
│   │   └── Mailer.php                   <- Wysyłka powiadomień e-mail z załącznikami
│   ├── model/
│   │   └── B2bRepository.php            <- Warstwa dostępu do danych (SQLite / MySQL PDO)
│   └── script/
│       └── B2bController.php            <- Kontroler obsługujący panel hurtownika i klienta
└── views/
    └── b2b/
        ├── catalog.php                  <- Panel klienta B2B (katalog, koszyk, historia)
        ├── admin.php                    <- Panel hurtownika (cennik, zamówienia, klienci)
        └── login.php                    <- Tradycyjne logowanie klienta B2B
```

---

## 3. Schemat bazy danych (SQLite / MySQL PDO)

Struktura tabel jest tworzona automatycznie przez `B2bRepository::initDatabase()` przy pierwszym uruchomieniu modułu.

### 3.1. Tabela `b2b_clients` (Baza odbiorców)
| Kolumna | Typ | Opis |
|---|---|---|
| `id` | INTEGER PRIMARY KEY AUTOINCREMENT | Identyfikator klienta |
| `company_name` | VARCHAR(150) NOT NULL | Nazwa sklepu / firmy (np. *Warzywniak U Ani*) |
| `nip` | VARCHAR(20) | NIP odbiorcy |
| `phone` | VARCHAR(50) | Telefon kontaktowy |
| `email` | VARCHAR(120) | Adres e-mail do potwierdzeń |
| `delivery_address` | TEXT | Adres i wskazówki dostawy |
| `auth_token` | VARCHAR(64) UNIQUE NOT NULL | Unikalny token do natychmiastowego logowania z linku |
| `login` | VARCHAR(50) UNIQUE | Login do opcjonalnego logowania hasłem |
| `password_hash` | VARCHAR(255) | Hash hasła klienta |
| `price_group_id` | INTEGER NOT NULL DEFAULT 1 | Rezerwacja pod przyszłe grupy cenowe |
| `is_active` | INTEGER NOT NULL DEFAULT 1 | Status aktywności konta (1 = aktywny) |
| `created_at` | DATETIME NOT NULL | Data rejestracji klienta |

### 3.2. Tabela `b2b_products` (Bieżąca oferta hurtowni)
| Kolumna | Typ | Opis |
|---|---|---|
| `id` | INTEGER PRIMARY KEY AUTOINCREMENT | Identyfikator produktu |
| `name` | VARCHAR(150) NOT NULL | Nazwa asortymentu (np. *Pomidor malinowy PL*) |
| `category` | VARCHAR(50) NOT NULL DEFAULT 'Inne' | Kategoria (*Warzywa, Owoce, Cytrusy, Zioła i sałaty*) |
| `unit` | VARCHAR(20) NOT NULL DEFAULT 'kg' | Jednostka podstawowa (*kg, szt., op., pęczek*) |
| `price` | REAL NOT NULL DEFAULT 0.00 | Aktualna cena hurtowa netto/brutto |
| `package_size` | REAL NOT NULL DEFAULT 1.0 | Wielkość opakowania zbiorczego (np. 7 dla klatki mango, 6 dla skrzynki) |
| `package_unit` | VARCHAR(30) NOT NULL DEFAULT 'op.' | Nazwa opakowania zbiorczego (*klatka, skrzynka, karton, worek*) |
| `is_available` | INTEGER NOT NULL DEFAULT 1 | Przełącznik dostępności na dziś (1 = dostępny, 0 = brak na stanie) |
| `sort_order` | INTEGER NOT NULL DEFAULT 0 | Kolejność sortowania |
| `updated_at` | DATETIME NOT NULL | Czas ostatniej modyfikacji pozycji |

### 3.3. Tabela `b2b_package_rules` (Inteligentna pamięć reguł opakowań)
| Kolumna | Typ | Opis |
|---|---|---|
| `id` | INTEGER PRIMARY KEY AUTOINCREMENT | Identyfikator reguły |
| `name_pattern` | VARCHAR(150) UNIQUE NOT NULL | Znormalizowana fraza produktu (np. *mango*, *pomidor malinowy*) |
| `package_size` | REAL NOT NULL | Zapamiętana wielkość opakowania (np. 7) |
| `package_unit` | VARCHAR(30) NOT NULL | Zapamiętana jednostka opakowania (*klatka*) |
| `unit` | VARCHAR(20) NOT NULL | Domyślna jednostka bazowa (*szt.*) |

### 3.4. Tabela `b2b_orders` (Zamówienia hurtowe)
| Kolumna | Typ | Opis |
|---|---|---|
| `id` | INTEGER PRIMARY KEY AUTOINCREMENT | Identyfikator zamówienia |
| `order_number` | VARCHAR(40) UNIQUE NOT NULL | Numer zamówienia (np. `B2B/2026/09/19/01`) |
| `client_id` | INTEGER NOT NULL | ID klienta składającego zamówienie |
| `client_name_snapshot` | VARCHAR(150) NOT NULL | Kopia nazwy klienta w chwili zamówienia |
| `client_phone_snapshot`| VARCHAR(50) | Kopia telefonu klienta |
| `delivery_address_snapshot`| TEXT | Kopia adresu dostawy |
| `status` | VARCHAR(20) NOT NULL DEFAULT 'new' | Status: `new` (Nowe), `processing` (W kompletacji), `completed` (Zrealizowane), `cancelled` (Anulowane) |
| `export_filename` | VARCHAR(150) | Nazwa wygenerowanego arkusza kompletacji `.xlsx` |
| `total_items` | INTEGER NOT NULL DEFAULT 0 | Liczba unikalnych pozycji |
| `total_amount` | REAL NOT NULL DEFAULT 0.00 | Łączna wartość zamówienia w PLN |
| `notes` | TEXT | Uwagi klienta do dostawy/kierowcy |
| `created_at` | DATETIME NOT NULL | Data i czas złożenia zamówienia |

### 3.5. Tabela `b2b_order_items` (Pozycje zamówienia)
| Kolumna | Typ | Opis |
|---|---|---|
| `id` | INTEGER PRIMARY KEY AUTOINCREMENT | Identyfikator pozycji |
| `order_id` | INTEGER NOT NULL | Relacja do `b2b_orders(id)` |
| `product_id` | INTEGER | Relacja do `b2b_products(id)` |
| `product_name` | VARCHAR(150) NOT NULL | Nazwa zamówionego produktu |
| `price` | REAL NOT NULL | Cena jednostkowa w momencie zamówienia |
| `quantity` | REAL NOT NULL | Zamówiona łączna ilość |
| `unit` | VARCHAR(20) NOT NULL | Jednostka miary |
| `package_size` | REAL NOT NULL DEFAULT 1.0 | Wielkość opakowania zbiorczego |
| `package_unit` | VARCHAR(30) NOT NULL DEFAULT 'op.' | Typ opakowania zbiorczego |
| `package_summary` | VARCHAR(100) | Wyliczone rozbicie (np. *„2 klatki + 1 szt.”*) |
| `item_total` | REAL NOT NULL | Wartość łączna pozycji (ilość * cena) |

---

## 4. Gotowość pod bazę MySQL (Konfiguracja w `program/config/data.php`)

Domyślnie system wykorzystuje sterownik SQLite. Aby w przyszłości przełączyć moduł na serwer MySQL, wystarczy uzupełnić stałe w pliku `program/config/data.php`:

```php
// Sterownik bazy danych dla modułu Hurtownia Magdy: 'sqlite' lub 'mysql'
define('B2B_DB_DRIVER', 'sqlite');

// Konfiguracja MySQL (wykorzystywana tylko gdy B2B_DB_DRIVER === 'mysql'):
define('B2B_MYSQL_HOST', '127.0.0.1');
define('B2B_MYSQL_PORT', 3306);
define('B2B_MYSQL_NAME', 'hurtownia_magdy');
define('B2B_MYSQL_USER', 'root');
define('B2B_MYSQL_PASS', '');
```

Klasa `B2bRepository` automatycznie wykrywa zdefiniowany sterownik i inicjuje odpowiednie połączenie PDO, realizując operacje za pomocą standardowego dialektu ANSI SQL.

---

## 5. Przepływy funkcjonalne i interfejsy

### 5.1. Panel Hurtownika (`GET /b2b/admin`)
Dostępny wyłącznie dla zalogowanego administratora platformy (`requireAuth()`).

1. **Zakładka 1: Cennik & Oferta hurtowni:**
   * **Strefa Dropzone:** Wgranie arkusza `.xlsx`, autodetekcja kolumn (*Towar, Cena, Jednostka, Opakowanie*).
   * **Podgląd i potwierdzenie mapowania:** Użytkownik weryfikuje wykryte kolumny przed załadowaniem do oferty.
   * **Inteligentne uzupełnianie:** Automatyczne kojarzenie produktów ze słownikiem reguł opakowań `b2b_package_rules`.
   * **Zarządzanie ofertą na żywo:**
     * Tabela bieżących towarów z filtrem kategorii i wyszukiwarką.
     * Przełącznik *„Dostępny na dziś”* (In stock / Out of stock) umożliwiający błyskawiczne wyłączenie towaru, którego zabrakło.
     * Szybka edycja ceny, jednostki oraz zalecanego opakowania zbiorczego w locie.
     * Przycisk wyczyszczenia oferty lub dodania pojedynczego nowego produktu ręcznie.

2. **Zakładka 2: Spływające Zamówienia B2B:**
   * Tabela zamówień z dynamicznymi badge'ami statusów (*Nowe [zielony], W kompletacji [żółty], Zrealizowane [szary], Anulowane [czerwony]*).
   * Zmiana statusu jednym kliknięciem (np. przekazanie z *Nowe* do *W kompletacji*).
   * Szczegółowy podgląd zamówienia (dane sklepu, telefon, uwagi do dostawy, tabela pozycji z rozbiciem na klatki).
   * Przycisk pobrania dedykowanego arkusza kompletacji magazynowej `.xlsx`.

3. **Zakładka 3: Klienci Hurtowni:**
   * Lista zarejestrowanych sklepów i punktów gastronomicznych.
   * Formularz dodawania nowego klienta (*Nazwa firmy, NIP, Telefon, E-mail, Adres dostawy, Opcjonalny login i hasło*).
   * Przycisk **„Kopiuj szybki link dostępowy”** generujący URL autologowania z tokenem:
     `http://twojadomena/b2b?token=...`
   * Możliwość aktywacji/deaktywacji klienta oraz edycji jego danych.

---

### 5.2. Panel Klienta B2B (`GET /b2b`)
Dostępny dla klienta autoryzowanego przez token w linku (`GET /b2b?token=...`) lub po zalogowaniu na `GET /b2b/login`.

1. **Górna belka klienta:**
   * Tytuł: **Hurtownia Magdy — Zamówienia B2B**.
   * Badge profilu: *„Zalogowano: Warzywniak U Ani”*.
   * Baner informacyjny: *„Cennik na dzień dzisiejszy (aktualizowany na bieżąco)”*.
   * Przycisk przejścia do historii zamówień klienta.

2. **Katalog towarów z Klawiaturą Hurtową:**
   * Szybka wyszukiwarka na żywo (np. *„pomidor”, „arbuz”*).
   * Filtry kategorialne: *Wszystkie / Warzywa / Owoce / Cytrusy / Zioła i sałaty* oraz przełącznik *„Tylko w koszyku”*.
   * Obsługa Tab/Enter: Kursor automatycznie przeskakuje do kolejnego pola ilości, umożliwiając błyskawiczne klepanie zamówień z klawiatury numerycznej.

3. **Asystent Pełnych Opakowań (Box Optimizer):**
   * Przy towarach z opakowaniem zbiorczym widnieje etykieta: `📦 Klatka: 7 szt.`
   * Przy wpisaniu niepełnej wielkości (np. 5 szt.) pojawia się podpowiedź: *„Brakuje 2 szt. do pełnej klatki [Zaokrąglij do 7 szt.]”*.
   * Przyciski szybkiego dodawania wielokrotności klatek: `+1 klatka (+7)`, `+2 klatki (+14)`.

4. **Pływający pasek podsumowania i Modal zamówienia:**
   * U dołu ekranu widoczny pasek z liczbą pozycji, łączną kwotą i przyciskiem *„Podsumuj i zamów”*.
   * Okno podsumowania:
     * Lista pozycji z wyliczonym rozbiciem logistycznym (np. *2 klatki + 1 szt. luzem*).
     * Dane dostawy sklepu pobrane automatycznie z profilu.
     * Pole tekstowe na uwagi dla kierowcy (np. *„Dostawa do godz. 6:30, brama od zaplecza”*).
     * Przycisk *„Zatwierdź i wyślij zamówienie”*.
   * Ekran potwierdzenia z nadanym numerem zamówienia i opcją pobrania potwierdzenia.

---

### 5.3. Obieg Zamówienia i Kompletacja Magazynowa

1. **Zapis zamówienia:**
   * Tworzony jest rekord w `b2b_orders` i pozycje w `b2b_order_items`.
   * Status ustawiany jest na `new`.

2. **Generowanie arkusza kompletacji (.xlsx):**
   * Zapisywany w `storage/b2b/orders/kompletacja_B2B_...xlsx`.
   * Zawiera:
     * Nagłówek: Numer zamówienia, Klient, Telefon, Adres dostawy, Uwagi kierowcy, Data/godzina.
     * Tabela kompletacyjna: *Lp., Nazwa produktu, Ilość, Jednostka, Przeliczenie logistyczne (np. „3 klatki”), Cena, Wartość, Kolumna kontrolna „Skompletowano [ ]”*.
     * Podsumowanie: Łączna liczba pozycji, łączna kwota.

3. **Wysyłka powiadomienia e-mail:**
   * Wysyłana do hurtowni za pośrednictwem biblioteki `Mailer` z załączonym wygenerowanym plikiem `.xlsx`.
   * Jeśli odbiorca zdefiniował adres e-mail w swoim profilu, otrzymuje potwierdzenie z kopią zamówienia.
   * Pełna odporność na błędy: brak konfiguracji SMTP w `data.php` nie blokuje procesu złożenia zamówienia.

---

## 6. Design i Wytyczne Wizualne (Tailwind CSS)

* **Framework:** Tailwind CSS (nowoczesny, zwięzły, bez zbędnego narzutu).
* **Stylistyka:** Profesjonalny, czysty panel B2B o wysokim kontraście, zoptymalizowany pod długą pracę przy danych tabelarycznych.
* **Akcenty tła:** Dyskretne, estetyczne ilustracje świeżych owoców i warzyw oraz łagodne gradienty ambientowe w tle strony (spójne z wypracowanym stylem Zamawiarki Magdy).
* **Responsywność (RWD):**
  * Klient B2B: Pełna optymalizacja pod smartfony (duże przyciski dotykowe, czytelny koszyk kciukowy) oraz desktopy (szybka klawiatura).
  * Panel Hurtownika: Zoptymalizowany pod ekrany laptopów i tabletów magazynowych na rampie załadunkowej.

---

## 7. Plan Testowania i Weryfikacji (TDD / Integracja)

1. **Testy jednostkowe repozytorium (`B2bRepository`):**
   * Inicjalizacja schematu bazy SQLite.
   * Zarządzanie klientami (tworzenie, wyszukiwanie po `auth_token`, weryfikacja unikalności).
   * Zarządzanie katalogiem produktów i regułami opakowań.
   * Zapis zamówienia z rozbiciem logistycznym na klatki/opakowania.
2. **Testy kontrolera (`B2bController`):**
   * Autoryzacja tokenem w URL (`?token=...`).
   * Import cennika Excel przez panel hurtownika.
   * Składanie zamówienia przez klienta z wyliczeniem kwoty i klatek.
   * Zmiana statusu zamówienia i generowanie arkusza kompletacji `.xlsx`.
3. **Test E2E pełnego cyklu:**
   * Wgranie cennika -> Edycja klatki mango -> Zalogowanie klienta tokenem -> Złożenie zamówienia z asystentem opakowań -> Zapis w bazie -> Weryfikacja arkusza kompletacji.
