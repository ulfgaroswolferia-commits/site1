<?php
/**
 * TwiiCoreF — punkt wejścia aplikacji.
 *
 * Kolejność ładowania jest istotna:
 *   1. data.php      — stałe konfiguracyjne (APP_ENV, DSN, APP_NAMESPACE, ...) i klasa Config
 *   2. headers.php   — nagłówki HTTP, strefa czasowa, start sesji (czyta APP_ENV)
 *   3. autoload.php  — autoloader klas (czyta APP_NAMESPACE)
 *   4. includes.php  — biblioteki ładowane ręcznie (bez autoloadera)
 */

// Katalog główny projektu. Wszystkie ścieżki w rdzeniu liczone są względem tej stałej —
// nigdy przez manipulację na __DIR__ (łamie się na Windows przez backslashe).
define('BASE_PATH', __DIR__);

require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/headers.php';
require_once BASE_PATH . '/program/config/autoload.php';
require_once BASE_PATH . '/program/config/includes.php';

App::run();
