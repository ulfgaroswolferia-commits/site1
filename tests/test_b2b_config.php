<?php
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
require_once BASE_PATH . '/program/config/data.php';

$routes = Config::get('routes');
$hasB2bRoute = isset($routes['b2b']) && $routes['b2b'] === 'action';
// B2B_DB_DRIVER jest opcjonalne — brak = 'sqlite' (B2bRepository). Jeśli ustawione, musi być obsługiwane.
$hasDriverConst = !defined('B2B_DB_DRIVER') || in_array(strtolower((string)B2B_DB_DRIVER), ['sqlite', 'mysql'], true);
$hasStorage = is_dir(BASE_PATH . '/storage/b2b/orders');

echo "1. Trasa 'b2b': " . ($hasB2bRoute ? "OK" : "BRAK") . "\n";
echo "2. Sterownik B2B_DB_DRIVER (opcjonalny, domyślnie sqlite): " . ($hasDriverConst ? "OK" : "BRAK") . "\n";
echo "3. Katalog storage/b2b/orders: " . ($hasStorage ? "OK" : "BRAK") . "\n";

exit(($hasB2bRoute && $hasDriverConst && $hasStorage) ? 0 : 1);
