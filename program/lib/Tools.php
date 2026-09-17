<?php
/**
 * Zestaw statycznych narzędzi: escapowanie, CSRF, flash messages, sesja, sanityzacja.
 * Ładowany ręcznie w program/config/includes.php.
 */
class Tools
{
    /** Nazwa pola formularza i klucz sesji dla tokenu CSRF. */
    const CSRF_FIELD       = '_csrf';
    const CSRF_SESSION_KEY = '_csrf_token';

    // -------------------------------------------------------------------------
    // Escapowanie HTML
    // -------------------------------------------------------------------------

    /**
     * Bezpieczne escapowanie wartości do HTML. Używaj W KAŻDYM miejscu, gdzie
     * do szablonu trafia dana z bazy, $_GET, $_POST lub sesji.
     */
    public static function h($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    /** Escapowanie wartości do wstawienia w atrybut JS/JSON w szablonie. */
    public static function j($value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    // -------------------------------------------------------------------------
    // Żądanie HTTP
    // -------------------------------------------------------------------------

    public static function isPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
    }

    public static function isGet(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'GET';
    }

    /** Sprawdza metodę żądania; przy niezgodności ustawia 400 i zwraca false. */
    public static function isRequestMethod(string $method): bool
    {
        if ($method === ($_SERVER['REQUEST_METHOD'] ?? '')) {
            return true;
        }
        http_response_code(400);
        return false;
    }

    /** IP klienta. Za proxy ufaj X-Forwarded-For tylko gdy proxy jest zaufane. */
    public static function getUserIP(): string
    {
        foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                // X-Forwarded-For może być listą — bierzemy pierwszy adres.
                return trim(explode(',', $_SERVER[$key])[0]);
            }
        }
        return 'UNKNOWN';
    }

    // -------------------------------------------------------------------------
    // Sanityzacja danych
    // -------------------------------------------------------------------------

    /** Usuwa znaki specjalne z wartości formularza (do pól typu kod, symbol, login). */
    public static function cleanPostValue(string $value): string
    {
        $search = ['!','#','$','%','^','&','*','(',')','[',']','{','}',':',';','"',"'",'`','<','>','?',',','\\','/','~'];
        return str_replace($search, '', $value);
    }

    /** Pozostawia wyłącznie cyfry. */
    public static function cleanPostNumericValue(string $value): string
    {
        return preg_replace('/[^0-9]/', '', $value);
    }

    /** Escapuje znaki niedozwolone w XML-u. */
    public static function xmlCharOut(string $value): string
    {
        return str_replace(['&', '<', '>', "\n", "\r"], ['&amp;', '&lt;', '&gt;', '', ''], $value);
    }

    /** "companyName" -> "company_name" */
    public static function camelToSnake(string $str): string
    {
        return strtolower(preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $str));
    }

    /** Zamienia wszystkie klucze tablicy z camelCase na snake_case. */
    public static function mapKeysCamelToSnake(array $data): array
    {
        $mapped = [];
        foreach ($data as $key => $value) {
            $mapped[self::camelToSnake($key)] = $value;
        }
        return $mapped;
    }

    /**
     * Konwersja polskich znaków między CP1250 / ISO-8859-2 / UTF-8.
     * Przydatne przy danych z systemów ERP i baz MS SQL w CP1250.
     * $type: WIN1250_TO_UTF8 | UTF8_TO_WIN1250 | ISO88592_TO_UTF8 | UTF8_TO_ISO88592
     *        | WIN1250_TO_ISO88592 | ISO88592_TO_WIN1250
     */
    public static function plConventer(string $string, string $type = 'WIN1250_TO_UTF8'): string
    {
        $win2utf = [
            "\xb9" => "\xc4\x85", "\xa5" => "\xc4\x84",
            "\xe6" => "\xc4\x87", "\xc6" => "\xc4\x86",
            "\xea" => "\xc4\x99", "\xca" => "\xc4\x98",
            "\xb3" => "\xc5\x82", "\xa3" => "\xc5\x81",
            "\xf3" => "\xc3\xb3", "\xd3" => "\xc3\x93",
            "\x9c" => "\xc5\x9b", "\x8c" => "\xc5\x9a",
            "\xbf" => "\xc5\xbc", "\x8f" => "\xc5\xb9",
            "\x9f" => "\xc5\xba", "\xaf" => "\xc5\xbb",
            "\xf1" => "\xc5\x84", "\xd1" => "\xc5\x83",
        ];

        $iso2utf = [
            "\xb1" => "\xc4\x85", "\xa1" => "\xc4\x84",
            "\xe6" => "\xc4\x87", "\xc6" => "\xc4\x86",
            "\xea" => "\xc4\x99", "\xca" => "\xc4\x98",
            "\xb3" => "\xc5\x82", "\xa3" => "\xc5\x81",
            "\xf3" => "\xc3\xb3", "\xd3" => "\xc3\x93",
            "\xb6" => "\xc5\x9b", "\xa6" => "\xc5\x9a",
            "\xbc" => "\xc5\xba", "\xac" => "\xc5\xb9",
            "\xbf" => "\xc5\xbc", "\xaf" => "\xc5\xbb",
            "\xf1" => "\xc5\x84", "\xd1" => "\xc5\x83",
        ];

        $win2iso = [
            "\xa5" => "\xa1", "\x8c" => "\xa6",
            "\x8f" => "\xac", "\xb9" => "\xb1",
            "\x9c" => "\xb6", "\x9f" => "\xbc",
        ];

        switch ($type) {
            case 'UTF8_TO_ISO88592':    $tab = array_flip($iso2utf); break;
            case 'ISO88592_TO_UTF8':    $tab = $iso2utf;             break;
            case 'WIN1250_TO_UTF8':     $tab = $win2utf;             break;
            case 'UTF8_TO_WIN1250':     $tab = array_flip($win2utf); break;
            case 'ISO88592_TO_WIN1250': $tab = array_flip($win2iso); break;
            case 'WIN1250_TO_ISO88592': $tab = $win2iso;             break;
            default:                    return $string;
        }

        return strtr($string, $tab);
    }

    // -------------------------------------------------------------------------
    // CSRF
    // -------------------------------------------------------------------------

    /** Token CSRF z sesji; przy braku generuje nowy. */
    public static function csrfToken(): string
    {
        if (empty($_SESSION[self::CSRF_SESSION_KEY])) {
            $_SESSION[self::CSRF_SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::CSRF_SESSION_KEY];
    }

    /** Gotowy hidden input z tokenem — wstaw do każdego formularza POST. */
    public static function csrfField(): string
    {
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            self::CSRF_FIELD,
            self::h(self::csrfToken())
        );
    }

    /** Weryfikacja tokenu. hash_equals() — odporne na timing attacks. */
    public static function csrfValidate(?string $submitted = null): bool
    {
        if ($submitted === null) {
            $submitted = (string) ($_POST[self::CSRF_FIELD] ?? '');
        }
        $expected = (string) ($_SESSION[self::CSRF_SESSION_KEY] ?? '');

        if ($expected === '' || $submitted === '') {
            return false;
        }
        return hash_equals($expected, $submitted);
    }

    // -------------------------------------------------------------------------
    // Flash messages — komunikaty przeżywające jedno przekierowanie
    // -------------------------------------------------------------------------

    /** $class: nazwa klasy CSS komunikatu (np. bootstrapowe danger/success/warning/info). */
    public static function setFlashMsg(string $name, string $msg, string $class = 'danger'): void
    {
        $_SESSION['fmsg'][$name] = ['msg' => $msg, 'class' => $class, 'c' => 1];
    }

    /** Zwraca komunikat lub strukturę z pustymi polami — widok może czytać bez isset(). */
    public static function getFlashMsg(string $name): array
    {
        return $_SESSION['fmsg'][$name] ?? ['msg' => '', 'class' => '', 'c' => 0];
    }

    public static function getAllFlashMsg(): array
    {
        return $_SESSION['fmsg'] ?? [];
    }

    /**
     * Dekrementuje licznik życia komunikatów i usuwa wygasłe.
     * Wywoływane raz na żądanie na początku App::run().
     */
    public static function sanitizeFlashMsg(): int
    {
        if (!isset($_SESSION['fmsg'])) {
            $_SESSION['fmsg'] = [];
        }
        foreach ($_SESSION['fmsg'] as $name => $msg) {
            if ((int) $msg['c'] === 0) {
                unset($_SESSION['fmsg'][$name]);
            } else {
                $_SESSION['fmsg'][$name]['c']--;
            }
        }
        return count($_SESSION['fmsg']);
    }

    // -------------------------------------------------------------------------
    // Sesja
    // -------------------------------------------------------------------------

    public static function setSessionVar(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    /** Wartość zmiennej sesji lub false, gdy nie istnieje. */
    public static function getSessionVar(string $key)
    {
        return $_SESSION[$key] ?? false;
    }

    public static function unsetSessionVar(string $key): void
    {
        unset($_SESSION[$key]);
    }

    // -------------------------------------------------------------------------
    // Debugowanie
    // -------------------------------------------------------------------------

    /** var_dump w <pre>. Działa tylko w APP_ENV=development. */
    public static function spr($var): void
    {
        if (defined('APP_ENV') && APP_ENV !== 'development') {
            return;
        }
        echo '<pre>';
        var_dump($var);
        echo '</pre>';
    }
}
