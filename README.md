# TwiiCoreF

Lekki szkielet MVC w czystym PHP — bez Composera, bez npm, bez zależności zewnętrznych.
Baza startowa dla nowych projektów webowych.

Wyodrębniony z aplikacji ExportRejestracja / SklepExportowy: rdzeń został oczyszczony z
logiki konkretnego wdrożenia, a znalezione po drodze błędy naprawione (patrz sekcja
*Różnice względem projektu źródłowego*).

---

## Uruchomienie nowego projektu

```bash
# 1. Skopiuj szkielet
cp -r TwiiCoreF MojProjekt && cd MojProjekt

# 2. Konfiguracja lokalna
cp program/config/data.sample.php program/config/data.php
#    uzupełnij: APP_NAME, DSN/DBLOGIN/DBPASS, CRYPT_KEY, CRYPT_IV, SESSION_NAME

# 3. Wygeneruj sekrety
php -r "echo 'CRYPT_KEY: ' . bin2hex(random_bytes(12)) . PHP_EOL;"
php -r "echo 'CRYPT_IV:  ' . bin2hex(random_bytes(16)) . PHP_EOL;"

# 4. Uruchom lokalnie
php -S localhost:8000
```

Na Apache: ustaw `RewriteBase` i ścieżkę `index.php` w `.htaccess` oraz odkomentuj
wymuszenie HTTPS/www (patrz komentarze w pliku).

---

## Struktura

```
index.php                     punkt wejścia, definiuje BASE_PATH
.htaccess                     front controller + blokada botów (do dostrojenia)

program/
  config/
    data.sample.php           szablon konfiguracji (skopiuj do data.php)
    headers.php               nagłówki HTTP, sesja, obsługa błędów — żądania HTML
    headersjson.php           to samo dla endpointów JSON
    autoload.php              autoloader: model/ (namespace), script/, core/
    includes.php              ręcznie ładowane biblioteki z lib/
  core/                       RDZEŃ — nie modyfikuj per projekt
    App.php                   cykl żądania, redirect, json, 404
    BaseRouter.php            parser URI
    Controller.php            bazowy kontroler
    Model.php                 bazowy model + generyczne INSERT/UPDATE
    View.php                  renderer szablonów
  lib/
    Db.php                    wrapper PDO (współdzielone połączenie)
    Tools.php                 escapowanie, CSRF, flash, sesja, sanityzacja, PL charset
    Crypt.php                 AES-128-CBC — tokeny w linkach i cookie
    Mailer.php                wrapper PHPMailer (transport z konfiguracji)
    PaginationHelper.php      numery stron, offset, liczba stron
    class.phpmailer.php       PHPMailer (kopia lokalna)
    class.smtp.php            transport SMTP
    class.pop3.php            POP-before-SMTP (rzadko potrzebne)
  model/
    ExampleModel.php          wzorcowy model do skopiowania
  script/
    Router.php                Router aplikacji (czyta Config)
    AppController.php         bazowy kontroler projektu: auth, CSRF, AJAX
    HomeController.php        kontroler startowy / przykład

views/
  layout/main.php             layout domyślny
  home/index.php              ekran startowy
  home/example.php            przykład parametru w URL-u
  home/404.php                widok błędu 404

assets/css/app.css            minimalny arkusz startowy
assets/js/app.js              appFetch() z nagłówkiem AJAX

db/migrations/                migracje SQL: RRRR-MM-DD-opis.sql
docs/MVC.md                   OBOWIĄZUJĄCA specyfikacja MVC
bin/                          skrypty CLI (cron, importy)
```

---

## Zasady

Pełna specyfikacja: **[docs/MVC.md](docs/MVC.md)**. W skrócie:

- **logika danych w modelu, sterowanie w kontrolerze, prezentacja w widoku**
- cały SQL w `program/model/`, zawsze prepared statements
- kontroler nie dotyka bazy, widok nie liczy i nie pyta
- metody narzędziowe ogólnego przeznaczenia zawsze w klasie `Tools` (statyczne),
  a nie rozsypane po kontrolerach
- każda wartość w widoku przez `Tools::h()`
- każdy POST z `Tools::csrfField()` i `requireCsrf()` po stronie kontrolera
- linki przez `App::baseUrl()`, nigdy na sztywno

---

## Co dodać, gdy projekt tego wymaga

| Potrzeba | Krok |
|---|---|
| Wysyłka maili | odkomentuj PHPMailer + `Mailer` w `program/config/includes.php`, uzupełnij `MAIL_*` |
| Generowanie PDF | wrzuć TCPDF do `program/lib/tcpdf/`, dopisz `require` w `includes.php` |
| Bootstrap / inny framework CSS | dołóż do `assets/` i podepnij w `views/layout/main.php` |
| Logowanie użytkowników | model `App\User` + `AuthController`, sesję zakładaj przez `startUserSession()` |
| Filtrowanie i sortowanie list | własny `FilterHelper` / `SortHelper` w `program/lib/` (są domenowe — celowo nieprzeniesione) |
| Endpointy JSON | osobny punkt wejścia z `headersjson.php` albo `App::json()` w akcji |

---

## Różnice względem projektu źródłowego

Zmiany świadome, nie kosmetyczne:

- **`BASE_PATH`** zamiast `str_replace('/program/core', '', __DIR__)` — stary hack nie
  działał na Windows (backslashe w `__DIR__`)
- **`APP_NAMESPACE`** konfigurowalne — zamiast wbitego na sztywno `Ebiznes\`
- autoloader nie próbuje już ładować klas z obcych namespace'ów i **nie rzuca wyjątku**
  przy braku klasy (loguje). Rzucający autoloader psuł `class_exists()` — zarejestrowana
  trasa bez pliku kontrolera dawała 500 zamiast 404
- `Db` trzyma **jedno połączenie** na żądanie (wcześniej każdy model otwierał własne),
  domyślnie `utf8mb4`, `FETCH_ASSOC`, bez emulacji prepared statements
- `Crypt` czyta klucz i IV z konfiguracji (wcześniej wbite w kod), `unserialize()`
  blokuje deserializację obiektów
- `App::run()` zwraca **404 zamiast wyjątku** przy nieznanej trasie; akcja może zwrócić
  `null` i sama wysłać odpowiedź
- `App::redirect()` obsługuje URL absolutny (powrót na zweryfikowany referer)
- sesja: konfigurowalna nazwa i czas życia zamiast reguły „do północy” z panelu eksportowego
- `Tools::spr()` milczy poza `APP_ENV=development`
- `.htaccess`: wymuszenie HTTPS/www zakomentowane (psuło localhost), `RewriteBase`
  z wartością domyślną do zmiany, blokada dostępu do `data.php`
- `CLAUDE.md` **jest** w repozytorium (w źródle był ignorowany) — to dokumentacja konwencji

### Czego celowo nie przeniesiono

`FilterHelper` (filtry marek/cen/atrybutów), `SortHelper` (sortowanie po cenie i stanie
magazynowym), TCPDF, `assetsbs5/`, modele i kontrolery sklepu, migracje SQL sklepu,
mechanizmy synchronizacji z ERP. To logika konkretnego wdrożenia, nie frameworka.
