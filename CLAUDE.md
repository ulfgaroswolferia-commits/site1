# CLAUDE.md

Wskazówki dla Claude Code przy pracy z projektami opartymi na szkielecie **TwiiCoreF**.

> Po starcie nowego projektu: uzupełnij sekcję „Opis projektu”, zaktualizuj listę tras
> i modeli, resztę zostaw — opisuje framework, nie wdrożenie.

## Opis projektu

_(uzupełnij: co robi aplikacja, kto jest użytkownikiem, gdzie stoi)_

## Architektura

Własny mikro-framework MVC. Bez Composera, bez npm, bez zależności zewnętrznych —
wszystko jest w repozytorium.

### Cykl żądania

1. `index.php` — definiuje `BASE_PATH`, ładuje konfigurację, autoloader i biblioteki
2. `App::run()` — parsuje URI przez `Router`, wywołuje
   `{Controller}Controller::{prefix}{Action}()`, renderuje widok i wkłada go w layout
3. URL: `/{trasa}/{akcja}/{klucz}/{wartość}/...` — nadmiarowe segmenty trafiają do
   `$router->params`
4. Trasy rejestrowane w `Config::$routes` (`program/config/data.php`)

### Katalogi

| Ścieżka | Zawartość |
|---|---|
| `program/core/` | rdzeń frameworka — **nie modyfikuj w ramach projektu** |
| `program/script/` | kontrolery + `Router`; klasy bez namespace'u |
| `program/model/` | modele; namespace `App\` (stała `APP_NAMESPACE`) |
| `program/lib/` | biblioteki ładowane ręcznie w `includes.php` |
| `program/config/` | konfiguracja; `data.php` poza gitem, wzorzec w `data.sample.php` |
| `views/{trasa}/` | widoki; `views/layout/` — layouty |
| `assets/` | CSS/JS/obrazki |
| `db/migrations/` | migracje SQL: `RRRR-MM-DD-opis.sql` |
| `docs/` | dokumentacja; `docs/MVC.md` = specyfikacja obowiązująca |

## Zasady kodowania — egzekwowane

**Pełna specyfikacja: `docs/MVC.md`. Przeczytaj przed pisaniem kodu.**

Reguła nadrzędna: **logika danych w modelu, sterowanie w kontrolerze, prezentacja w widoku.**

- SQL **wyłącznie** w `program/model/`. Kontroler i widok nie dotykają bazy.
- Zapytania zawsze przez prepared statements. Zapis przez `filterColumns()` z białą listą.
- Model zwraca dane (tablice, skalary), nigdy HTML. Nie czyta superglobali, nie przekierowuje.
- Kontroler dziedziczy po `AppController`, akcja zwraca nazwę szablonu (lub `null`,
  gdy sama wysłała odpowiedź).
- `requireCsrf()` w każdej akcji POST, `requireAuth()` w akcjach wymagających logowania.
- Po udanym POST → `App::redirect()` (POST/Redirect/GET).
- W widoku każda wartość przez `Tools::h()`. Formularze z `Tools::csrfField()`.
- Linki przez `App::baseUrl()` — nigdy hardkodowane ścieżki.
- Ścieżki plików przez `BASE_PATH`, nigdy przez manipulację na `__DIR__`.
- **Metody narzędziowe ogólnego przeznaczenia dopisuj do klasy `Tools`**
  (`program/lib/Tools.php`), jako statyczne — nie do kontrolera, modelu ani widoku.
  Warunek: bezstanowe, bez dostępu do bazy, bez reguł biznesowej domeny.
  Gdy zbierze się grupa 3+ powiązanych metod wokół jednego tematu — osobna klasa
  w `program/lib/` zarejestrowana w `includes.php`. Szczegóły: `docs/MVC.md` §6.

## Dodanie nowej funkcjonalności

1. Trasa w `Config::$routes` (`program/config/data.php`)
2. Kontroler `program/script/{Trasa}Controller.php` extends `AppController`
3. Model `program/model/{Nazwa}.php`, `namespace App;`, extends `\Model`
4. Widok `views/{trasa}/{szablon}.php`
5. Migracja w `db/migrations/` jeśli zmienia się schemat

Wzorce do skopiowania: `HomeController.php`, `ExampleModel.php`, `views/home/index.php`.

## Baza danych

- PDO, konfiguracja w `program/config/data.php` (`DSN`, `DBLOGIN`, `DBPASS`, `DB_CHARSET`)
- Połączenie współdzielone w obrębie żądania (`Db` trzyma statyczny uchwyt)
- Na MySQL 5.5: `WHERE x IN (SELECT ...)` to *dependent subquery* — używaj `JOIN`

## Debugowanie

- `Tools::spr($var)` — `var_dump` w `<pre>`, aktywne tylko przy `APP_ENV=development`
- Na produkcji błędy PHP idą wyłącznie do logu (`program/config/headers.php`)

## Weryfikacja przed zgłoszeniem gotowości

- `php -l` czysto na każdym zmienionym pliku
- strona faktycznie się renderuje (`php -S localhost:8000`), nie tylko „powinna”
- commity: użytkownik commituje ręcznie po wgraniu i weryfikacji na serwerze —
  nie commituj bez wyraźnej prośby
