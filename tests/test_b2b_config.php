<?php
define('BASE_PATH', 'C:/laragon/www');
require_once BASE_PATH . '/program/config/data.php';

$routes = Config::get('routes');
$hasB2bRoute = isset($routes['b2b']) && $routes['b2b'] === 'action';
$hasDriverConst = defined('B2B_DB_DRIVER');
$hasStorage = is_dir(BASE_PATH . '/storage/b2b/orders');

echo "1. Trasa 'b2b': " . ($hasB2bRoute ? "OK" : "BRAK") . "\n";
echo "2. Stała B2B_DB_DRIVER: " . ($hasDriverConst ? "OK" : "BRAK") . "\n";
echo "3. Katalog storage/b2b/orders: " . ($hasStorage ? "OK" : "BRAK") . "\n";

exit(($hasB2bRoute && $hasDriverConst && $hasStorage) ? 0 : 1);
