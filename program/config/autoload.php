<?php
/**
 * Autoloader klas. Trzy lokalizacje, sprawdzane w tej kolejności:
 *
 *   1. program/model/   — klasy z namespace'em APP_NAMESPACE (domyślnie "App\")
 *                         App\User            → program/model/User.php
 *                         App\Billing\Invoice → program/model/Billing/Invoice.php
 *   2. program/script/  — klasy aplikacji bez namespace'u (kontrolery, Router)
 *   3. program/core/    — klasy rdzenia frameworka (App, Controller, View, ...)
 *
 * Biblioteki z program/lib/ ładowane są ręcznie w includes.php (część z nich to
 * kod zewnętrzny bez konwencji nazewniczej pasującej do autoloadera).
 */

spl_autoload_register(function ($class) {
    $programDir = dirname(__DIR__);
    $checked    = [];

    // 1. Namespace modeli → program/model/
    $prefix = defined('APP_NAMESPACE') ? APP_NAMESPACE : 'App\\';
    if ($prefix !== '' && strncmp($prefix, $class, strlen($prefix)) === 0) {
        $relative = substr($class, strlen($prefix));
        $file     = $programDir . '/model/' . str_replace('\\', '/', $relative) . '.php';
        $checked[] = $file;
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }

    // Klasy z namespace'em innym niż APP_NAMESPACE nie należą do nas — nie zgadujemy ścieżki.
    if (strpos($class, '\\') !== false) {
        return;
    }

    // 2. Klasy aplikacji → program/script/
    $file = $programDir . '/script/' . $class . '.php';
    $checked[] = $file;
    if (is_file($file)) {
        require_once $file;
        return;
    }

    // 3. Klasy rdzenia → program/core/
    $file = $programDir . '/core/' . $class . '.php';
    $checked[] = $file;
    if (is_file($file)) {
        require_once $file;
        return;
    }

    // Nieznaleziona klasa TYLKO do logu — bez wyjątku.
    //
    // Autoloader, który rzuca, psuje class_exists(): funkcja ma zwrócić false, a zamiast
    // tego leci wyjątek. App::run() używa class_exists() do rozpoznania nieistniejącego
    // kontrolera — z rzucającym autoloaderem zamiast czystego 404 dostajemy 500.
    //
    // Przy prawdziwej literówce i tak zobaczysz fatal "Class not found" w momencie użycia,
    // a tu w logu masz komplet sprawdzonych ścieżek do diagnozy.
    error_log(
        'Autoloader: nie znaleziono klasy ' . $class
        . ' (sprawdzono: ' . implode(', ', $checked) . ')'
        . ' — sprawdź wielkość liter w nazwie pliku oraz namespace wewnątrz pliku.'
    );
});
