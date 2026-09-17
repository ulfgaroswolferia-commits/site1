# MVC w TwiiCoreF — specyfikacja obowiązująca

Dokument wiążący dla każdego projektu opartego na tym szkielecie. Opisuje, jak tworzyć
kontrolery, modele i widoki oraz gdzie przebiega granica między nimi.

---

## 1. Podział odpowiedzialności — reguła nadrzędna

| Warstwa | Katalog | Odpowiada za | NIE MOŻE zawierać |
|---|---|---|---|
| **Model** | `program/model/` | dostęp do danych, SQL, reguły biznesowe, walidacja, obliczenia | HTML, `echo`, `header()`, `$_GET`/`$_POST`/`$_SESSION`, przekierowań |
| **Kontroler** | `program/script/` | odczyt wejścia, sterowanie przepływem, wywołanie modelu, wybór widoku, przekierowania | SQL, zapytań do bazy, HTML, reguł biznesowych |
| **Widok** | `views/` | wyłącznie prezentacja danych otrzymanych w `$view` | SQL, `new Model`, logiki biznesowej, obliczeń cen/rabatów/statusów |
| **Narzędzia** | `program/lib/Tools.php` | bezstanowe funkcje pomocnicze wspólne dla całej aplikacji | dostępu do bazy, reguł biznesowych konkretnej domeny |

**Zasada w jednym zdaniu:** logika danych w modelu, sterowanie w kontrolerze,
prezentacja w widoku.

### Test przynależności kodu

Zanim dopiszesz linijkę, odpowiedz:

1. Czy ten kod pytałby o to samo, gdyby aplikacja nie miała interfejsu WWW (np. skrypt CLI)?
   → **model**.
2. Czy ten kod istnieje tylko dlatego, że przyszło żądanie HTTP (parametry, sesja, redirect)?
   → **kontroler**.
3. Czy ten kod decyduje wyłącznie o tym, jak coś wygląda?
   → **widok**.

### Czego nie robimy — konkretne antywzorce

```php
// ŹLE — SQL w kontrolerze
public function actionIndex()
{
    $db = new Db();
    $rows = $db->dbh->query('SELECT * FROM orders')->fetchAll();   // <- to należy do modelu
    ...
}

// ŹLE — reguła biznesowa w widoku
<?php $cena = $view['netto'] * 1.23 * (1 - $view['rabat'] / 100); ?>   <!-- <- to należy do modelu -->

// ŹLE — HTML w modelu
public function statusBadge(): string
{
    return '<span class="badge">Wysłane</span>';   // <- model zwraca dane, nie znaczniki
}

// ŹLE — $_POST w modelu
public function save()
{
    $name = $_POST['name'];   // <- model dostaje dane w argumencie, nie czyta superglobali
}
```

Poprawnie:

```php
// Kontroler: czyta wejście, oddaje modelowi, wybiera widok
public function actionStore()
{
    $this->requireCsrf();

    $model  = new \App\Order();
    $errors = $model->validate($_POST);

    if ($errors) {
        Tools::setFlashMsg('error', 'Popraw zaznaczone pola.');
        $this->outputData['errors'] = $errors;
        $this->outputData['form']   = $_POST;
        return 'form';
    }

    $id = $model->create($_POST);
    Tools::setFlashMsg('ok', 'Zamówienie zapisane.', 'success');
    App::redirect('order/view/id/' . $id);
}

// Model: SQL, walidacja, wyliczenia
public function grossTotal(array $order): float { ... }

// Widok: tylko wypisanie
<td><?= Tools::h(number_format($view['order']['gross'], 2, ',', ' ')) ?></td>
```

---

## 2. Cykl żądania

```
index.php
  └─ data.php → headers.php → autoload.php → includes.php
  └─ App::run()
       ├─ Router          parsuje URI  →  trasa / akcja / parametry
       ├─ {X}Controller::{prefix}{Action}()   zwraca nazwę szablonu
       ├─ View(views/{trasa}/{szablon}.php)   →  $content
       └─ View(views/layout/{layout}.php)     →  echo
```

Mapowanie URL-a:

```
/                          → domyślna trasa i akcja z Config (data.php)
/home                      → HomeController::actionIndex()
/home/about                → HomeController::actionAbout()
/order/view/id/7/tab/faq   → OrderController::actionView(), params: id=7, tab=faq
```

Query string (`?page=2`) **nie** jest parsowany przez router — czytasz go z `$_GET`.

---

## 3. Tworzenie kontrolera

### Krok 1 — zarejestruj trasę

`program/config/data.php`, tablica `Config::$routes`:

```php
private static $routes = array(
    'home'  => 'action',
    'order' => 'action',   // <- nowa trasa
);
```

Klucz = nazwa trasy (pierwszy segment URL-a), wartość = prefiks metod (`action`).
**Bez tego wpisu router nie rozpozna kontrolera.**

### Krok 2 — utwórz plik

`program/script/OrderController.php` — nazwa pliku musi odpowiadać nazwie klasy.

```php
<?php

class OrderController extends AppController
{
    /** Layout inny niż domyślny — opcjonalnie. */
    public $layout = 'main';

    public function actionIndex()
    {
        $this->requireAuth();

        $model = new \App\Order();

        $page       = PaginationHelper::currentPage($_GET);
        $perPage    = 25;
        $totalPages = PaginationHelper::totalPages($model->countForUser($this->uid()), $perPage);

        $this->outputData['orders'] = $model->pageForUser(
            $this->uid(),
            $perPage,
            PaginationHelper::offset($page, $perPage)
        );
        $this->outputData['pages'] = PaginationHelper::pages($page, $totalPages);

        return 'index';   // → views/order/index.php
    }
}
```

### Zasady kontrolera

- dziedziczy po `AppController` (nie po `Controller` bezpośrednio)
- nazwa klasy: `{Trasa}Controller` z wielkiej litery, np. `order` → `OrderController`
- nazwa metody: `action{Akcja}`, np. `/order/view` → `actionView()`
- akcja **zwraca nazwę szablonu** (string, bez `.php`) albo `null` gdy sama wysłała
  odpowiedź (`App::json()`, `App::redirect()`, plik do pobrania)
- dane dla widoku trafiają do `$this->outputData['klucz']` (lub `$this->set()`)
- **każda akcja zmieniająca stan zaczyna się od `$this->requireCsrf()`**
- każda akcja wymagająca logowania zaczyna się od `$this->requireAuth()`
- po udanym POST → `App::redirect()` (wzorzec POST/Redirect/GET, blokuje podwójny zapis)

### Dostępne metody `AppController`

| Metoda | Zastosowanie |
|---|---|
| `requireAuth()` | wymusza logowanie, w razie braku przekierowuje na `AUTH_LOGIN_ROUTE` |
| `isLoggedIn()` / `uid()` | stan sesji i ID użytkownika |
| `startUserSession($id, $extra)` | logowanie (regeneruje ID sesji — chroni przed session fixation) |
| `endUserSession()` | wylogowanie z czyszczeniem cookie |
| `requireCsrf()` | odrzuca POST bez ważnego tokenu (400) |
| `isAjax()` | rozpoznaje żądanie fetch/XHR |
| `safeReferer()` / `backUrl()` | powrót na poprzednią stronę bez ryzyka open redirectu |
| `param('id', 0)` | parametr ze ścieżki URL |
| `render('mail/_order', $data)` | render szablonu do stringa (np. treść maila) |

---

## 4. Tworzenie modelu

Plik: `program/model/Order.php` (podkatalogi = podnamespace'y).
Wzorzec do skopiowania: `program/model/ExampleModel.php`.

```php
<?php

namespace App;

class Order extends \Model
{
    const TABLE    = 'orders';
    const FILLABLE = ['user_id', 'status', 'note'];

    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM ' . self::TABLE . ' WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function create(array $data): int
    {
        return (int) $this->insertRow(self::TABLE, $this->filterColumns($data, self::FILLABLE));
    }

    /** Reguła biznesowa — należy do modelu, nie do widoku. */
    public function grossTotal(array $lines, float $vatRate = 0.23): float
    {
        $net = array_sum(array_map(fn($l) => $l['qty'] * $l['price'], $lines));

        return round($net * (1 + $vatRate), 2);
    }
}
```

### Zasady modelu

- `namespace App;` — zgodny ze stałą `APP_NAMESPACE` z `data.php`
- dziedziczy po `\Model` (z backslashem — `Model` jest w globalnym namespace)
- nazwa pliku = nazwa klasy: `App\Billing\Invoice` → `program/model/Billing/Invoice.php`
- **wszystkie wartości przez prepared statements** — nigdy konkatenacja do SQL-a
  (`LIMIT`/`OFFSET` nie da się bindować — rzutuj na `int` i wstaw przez `sprintf('%d')`)
- kolumny do zapisu przez `filterColumns($data, self::FILLABLE)`
- metoda zwraca **czyste dane**: tablicę, skalar, `null`. Nigdy HTML-a
- model nie zna `$_GET`, `$_POST`, `$_SESSION` — dane dostaje w argumentach
- model nie przekierowuje i nie kończy żądania
- walidacja danych wejściowych jest w modelu (`validate()` zwraca tablicę błędów)

### Narzędzia z `\Model`

| Metoda | Opis |
|---|---|
| `$this->pdo()` | uchwyt PDO (współdzielony w obrębie żądania) |
| `$this->filterColumns($data, $allowed)` | biała lista kolumn |
| `$this->insertRow($table, $data)` | generyczny INSERT, zwraca ID |
| `$this->updateRow($table, $data, $where)` | generyczny UPDATE, zwraca liczbę wierszy |

### Wydajność na starszym MySQL

Na MySQL 5.5 konstrukcja `WHERE x IN (SELECT ...)` wykonuje się jako *dependent subquery*
— raz na wiersz. Zamiast niej używaj `JOIN`.

---

## 5. Tworzenie widoku

Plik: `views/{trasa}/{szablon}.php`. Akcja `OrderController::actionIndex()` zwracająca
`'index'` renderuje `views/order/index.php`.

```php
<?php
/** Dane z kontrolera dostępne w $view. */
?>
<section class="card">
    <h1><?= Tools::h($view['title']) ?></h1>

    <table>
        <?php foreach ($view['orders'] as $order): ?>
            <tr>
                <td><?= Tools::h($order['id']) ?></td>
                <td><?= Tools::h($order['status']) ?></td>
                <td><a href="<?= App::baseUrl() ?>order/view/id/<?= (int) $order['id'] ?>">Szczegóły</a></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <form method="post" action="<?= App::baseUrl() ?>order/store">
        <?= Tools::csrfField() ?>
        <input type="text" name="note" value="<?= Tools::h($view['form']['note'] ?? '') ?>">
        <button type="submit">Zapisz</button>
    </form>
</section>
```

### Zasady widoku

- **każda** wartość wypisywana przez `Tools::h()` — również dane z własnej bazy
- wyjątek: `$view['content']` w layoucie (to już wyrenderowany HTML)
- linki budowane przez `App::baseUrl()` — nigdy na sztywno (`/exportb2b/...`)
- każdy formularz POST zawiera `<?= Tools::csrfField() ?>`
- dopuszczalna logika: `if`, `foreach`, formatowanie (`number_format`, `date`)
- niedopuszczalna: zapytania do bazy, `new \App\Cokolwiek()`, wyliczanie cen i statusów
- fragmenty współdzielone: plik z podkreśleniem na początku, np. `views/order/_row.php`.
  Wewnątrz widoku `$this` wskazuje na obiekt `View`, nie na kontroler — fragment
  wstawiaj przez `include` (dziedziczy zmienną `$view`):

  ```php
  <?php foreach ($view['orders'] as $order): ?>
      <?php include BASE_PATH . '/views/order/_row.php'; ?>
  <?php endforeach; ?>
  ```

  Gdy fragment ma dostać własny, odrębny zestaw danych (np. treść maila), renderuj go
  w kontrolerze: `$html = $this->render('mail/_order', $dane);`

### Layout

`views/layout/main.php` — wybierany przez `$this->layout` w kontrolerze
(nazwa pliku bez `.php`). Otrzymuje `$view['content']` z wyrenderowanym widokiem.
`$this->layout = ''` renderuje sam widok, bez layoutu (przydatne przy fragmentach AJAX).

### Flash messages

```php
// Kontroler
Tools::setFlashMsg('ok', 'Zapisano.', 'success');
App::redirect('order/index');
```

Layout wypisuje je automatycznie przez `Tools::getAllFlashMsg()`. Komunikat znika po
jednym przeładowaniu strony.

---

## 6. Metody narzędziowe — zawsze w klasie `Tools`

**Reguła: każda metoda pomocnicza ogólnego przeznaczenia trafia do `Tools`
(`program/lib/Tools.php`) jako metoda statyczna. Nigdy do kontrolera, modelu ani widoku.**

Chodzi o to, żeby helper napisany przy jednym ekranie był od razu widoczny i dostępny
dla całej aplikacji — zamiast być kopiowany, przeklejany i rozjeżdżać się między plikami.

### Co należy do `Tools`

Metoda pasuje do `Tools`, gdy spełnia **wszystkie** warunki:

- jest **bezstanowa** — wynik zależy wyłącznie od argumentów (albo od sesji/żądania, jak CSRF)
- nie dotyka bazy danych
- nie zawiera reguły biznesowej konkretnej domeny
- przyda się w więcej niż jednym miejscu

Typowe przykłady: formatowanie (daty, kwoty, numery telefonów), konwersje kodowania,
sanityzacja wejścia, walidatory ogólne (NIP, e-mail, kod pocztowy), generowanie slugów,
skracanie tekstu, praca z sesją i flash messages, obsługa CSRF, escapowanie.

### Czego do `Tools` NIE wkładamy

| Kod | Gdzie należy |
|---|---|
| `grossTotal()`, `applyDiscount()`, `orderStatusLabel()` | model — to reguła biznesowa |
| `findUserByEmail()` | model — dotyka bazy |
| `requireAuth()`, `isAjax()`, `safeReferer()` | `AppController` — dotyczy cyklu żądania |
| `renderPaginationHtml()` | widok / fragment `_pagination.php` — to prezentacja |
| duży, spójny tematycznie zestaw metod (np. 15 metod filtrów listingu) | osobna klasa w `program/lib/` |

### Kiedy założyć osobną klasę zamiast dopisać do `Tools`

`Tools` to zbiór drobnych, niepowiązanych narzędzi. Gdy zbiera się **grupa metod wokół
jednego tematu** (paginacja, filtry, wysyłka poczty, klient HTTP), zakładasz dedykowaną
klasę w `program/lib/` i rejestrujesz ją w `program/config/includes.php` — tak powstały
`PaginationHelper`, `Crypt` i `Mailer`. Granica: trzy i więcej powiązanych metod
z wspólnym prefiksem w nazwie to sygnał do wydzielenia klasy.

### Jak dodać metodę

1. Dopisz metodę **statyczną** do odpowiedniej sekcji `Tools.php` (plik ma nagłówki sekcji:
   escapowanie, żądanie HTTP, sanityzacja, CSRF, flash, sesja, debugowanie)
2. Dodaj krótki docblock: co robi i co zwraca w przypadku brzegowym
3. Zadeklaruj typy argumentów i zwracany
4. Jeśli metoda nie pasuje do żadnej istniejącej sekcji — załóż nową z komentarzem-nagłówkiem

```php
// -------------------------------------------------------------------------
// Formatowanie
// -------------------------------------------------------------------------

/** Kwota w formacie polskim: 1 234,56. Null i pusty string dają '0,00'. */
public static function money($amount, int $decimals = 2): string
{
    return number_format((float) $amount, $decimals, ',', ' ');
}
```

Użycie identyczne wszędzie — `Tools` jest ładowane w `includes.php` przed rozpoczęciem
routingu, więc jest dostępne w kontrolerze, modelu i widoku:

```php
<td><?= Tools::h(Tools::money($view['order']['gross'])) ?></td>
```

### Przed dopisaniem metody

Sprawdź, czy w `Tools` nie ma już odpowiednika — plik ma rosnąć, ale nie duplikować się.
Jeśli istniejąca metoda robi *prawie* to samo, rozszerz ją o parametr zamiast tworzyć drugą.

---

## 7. Checklista — nowa funkcjonalność

- [ ] trasa dopisana do `Config::$routes` w `program/config/data.php`
- [ ] kontroler w `program/script/{Trasa}Controller.php`, dziedziczy po `AppController`
- [ ] akcje `action{Nazwa}()`, zwracają nazwę szablonu
- [ ] `requireAuth()` w akcjach wymagających logowania
- [ ] `requireCsrf()` w akcjach POST
- [ ] model w `program/model/`, namespace `App`, dziedziczy po `\Model`
- [ ] cały SQL w modelu, prepared statements, `filterColumns()` przy zapisie
- [ ] widok w `views/{trasa}/{szablon}.php`, wszystko przez `Tools::h()`
- [ ] formularze z `Tools::csrfField()`
- [ ] linki przez `App::baseUrl()`
- [ ] metody narzędziowe ogólnego przeznaczenia dopisane do `Tools`, nie do kontrolera
- [ ] migracja SQL w `db/migrations/RRRR-MM-DD-opis.sql`
- [ ] `php -l` czysto na nowych plikach
