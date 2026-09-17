<?php
/**
 * Szablon konfiguracji lokalnej.
 *
 * Skopiuj do program/config/data.php i uzupełnij prawdziwymi wartościami.
 * data.php NIE jest śledzone w git (patrz .gitignore), więc sekrety tu wpisane
 * nie trafiają do repozytorium.
 */

// -----------------------------------------------------------------------------
// Środowisko
// -----------------------------------------------------------------------------

// Steruje wyświetlaniem błędów PHP (patrz program/config/headers.php).
// Na produkcji MUSI być 'production' — błędy tylko do logu, nigdy do przeglądarki.
define('APP_ENV', 'development');

// Nazwa aplikacji — używana w tytule strony i nagłówku layoutu.
define('APP_NAME', 'TwiiCoreF');

// -----------------------------------------------------------------------------
// Autoloader
// -----------------------------------------------------------------------------

// Prefiks namespace'u modeli (program/model/). Klasa App\Foo\Bar → program/model/Foo/Bar.php.
// Musi kończyć się backslashem.
define('APP_NAMESPACE', 'App\\');

// -----------------------------------------------------------------------------
// Baza danych
// -----------------------------------------------------------------------------

define('DSN',     'mysql:dbname=CHANGEME;host=127.0.0.1');
define('DBLOGIN', 'CHANGEME');
define('DBPASS',  'CHANGEME');

// utf8mb4 = pełny Unicode (emoji, 4-bajtowe znaki). Na MySQL < 5.5.3 zmień na 'utf8'.
define('DB_CHARSET', 'utf8mb4');

// -----------------------------------------------------------------------------
// Sesja
// -----------------------------------------------------------------------------

// Nazwa cookie sesji — unikalna per aplikacja, żeby dwie aplikacje na tej samej
// domenie nie nadpisywały sobie sesji.
define('SESSION_NAME', 'twiicoref_sid');

// Czas życia sesji w sekundach (0 = do zamknięcia przeglądarki). 8h = 28800.
define('SESSION_LIFETIME', 28800);

// Klucz sesji przechowujący identyfikator zalogowanego użytkownika
// (używany przez AppController::requireAuth()).
define('AUTH_SESSION_KEY', 'app_uid');

// Trasa, na którą trafia niezalogowany użytkownik.
define('AUTH_LOGIN_ROUTE', 'home/index');

// -----------------------------------------------------------------------------
// Szyfrowanie (lib/Crypt.php) — tokeny w linkach, dane w cookie
// -----------------------------------------------------------------------------

// Klucz — max 32 znaki. WYGENERUJ WŁASNY dla każdego projektu.
define('CRYPT_KEY', 'CHANGEME');

// Wektor inicjalizacji — dokładnie 32 znaki szesnastkowe.
// Wygeneruj:  php -r "echo bin2hex(random_bytes(16));"
define('CRYPT_IV', 'CHANGEME0000000000000000CHANGEME');

// -----------------------------------------------------------------------------
// Poczta (lib/Mailer.php)
// -----------------------------------------------------------------------------

define('MAIL_FROM',      'noreply@example.com');
define('MAIL_FROM_NAME', 'TwiiCoreF');

// Transport: 'mail' (funkcja mail() PHP) albo 'smtp'.
define('MAIL_TRANSPORT', 'mail');

// Używane tylko gdy MAIL_TRANSPORT === 'smtp'.
define('SMTP_HOST',   '');
define('SMTP_PORT',   587);
define('SMTP_USER',   '');
define('SMTP_PASS',   '');
define('SMTP_SECURE', 'tls');   // 'tls' | 'ssl' | ''

// -----------------------------------------------------------------------------
// Routing
// -----------------------------------------------------------------------------

/**
 * Tablica tras: 'nazwa_trasy' => 'prefiks_metody'.
 *
 * Trasa 'home' + akcja 'index' + prefiks 'action' → HomeController::actionIndex().
 * Dopisz tu każdy nowy kontroler, inaczej router go nie rozpozna.
 */
class Config
{
    private static $routes = array(
        'home' => 'action',
    );

    private static $default_route      = 'home';
    private static $default_controller = 'home';
    private static $default_action     = 'index';

    public static function get($var)
    {
        return self::$$var;
    }
}
