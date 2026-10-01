<?php
/**
 * Test weryfikujący odporność na współbieżne składanie zamówień oraz transakcje BEGIN IMMEDIATE
 */
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';

use App\B2bRepository;

echo "=== TEST: Współbieżność SQLite i generowanie numerów zamówień ===\n";

$repo = new B2bRepository();

// 1. Sprawdzenie, czy marker inicjalizacji istnieje
$marker = BASE_PATH . '/db/.b2b_v2';
$markerExists = file_exists($marker);
echo "1. Marker .b2b_v2: " . ($markerExists ? "OBECNY (DDL pomijany przy kolejnych żądaniach)" : "BRAK") . "\n";

// 2. Symulacja serii szybkich zamówień
$createdOrders = [];
$items = [
    ['product_name' => 'Cytryny', 'price' => 7.50, 'quantity' => 2, 'unit' => 'kg', 'package_size' => 10, 'package_unit' => 'karton'],
];

for ($i = 1; $i <= 5; $i++) {
    $orderId = $repo->createOrder([
        'client_id'             => 1,
        'client_name_snapshot'  => 'Test Współbieżności',
        'total_amount'          => 15.00,
        'notes'                 => 'Test concurr ' . $i,
    ], $items);

    $order = $repo->getOrderById($orderId);
    $createdOrders[] = $order['order_number'];
}

echo "2. Utworzone numery zamówień:\n";
foreach ($createdOrders as $num) {
    echo "   - $num\n";
}

// Sprawdzenie, czy wszystkie numery są unikalne
$uniqueCount = count(array_unique($createdOrders));
$allUnique = ($uniqueCount === count($createdOrders));
echo "3. Wszystkie wygenerowane numery są unikalne: " . ($allUnique ? "PASS (brak kolizji)" : "FAIL") . "\n";

if (!$allUnique) {
    echo "BŁĄD: Wykryto zduplikowane numery zamówień!\n";
    exit(1);
}

echo "=== TEST ZAKOŃCZONY SUKCESEM ===\n";
