<?php
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/core/Model.php';
require_once BASE_PATH . '/program/lib/Db.php';
require_once BASE_PATH . '/program/model/OrderModel.php';

echo "=== TEST ORDER MODEL (SQLITE) ===\n";

$model = new \App\OrderModel();
$orderNum = $model->generateOrderNumber();
echo "[OK] Wygenerowany numer zamĂłwienia: $orderNum\n";

$testItems = [
    ['name' => 'Pomidory malinowe', 'price' => 8.50, 'quantity' => 10.5, 'unit' => 'kg'],
    ['name' => 'OgĂłrki gruntowe',   'price' => 5.00, 'quantity' => 20.0, 'unit' => 'kg'],
    ['name' => 'Koperek Ĺ›wieĹĽy',    'price' => 1.80, 'quantity' => 15,   'unit' => 'pÄ™czek'],
    ['name' => 'Rzodkiewka',        'price' => 2.50, 'quantity' => 0,    'unit' => 'pÄ™czek'], // nie zamĂłwione
];

$orderId = $model->createOrder([
    'order_number'      => $orderNum,
    'supplier_name'     => 'Hurtownia Agro-Plon',
    'original_filename' => 'cennik_wrzesien.xlsx',
    'export_filename'   => 'zamowienie_001.xlsx',
], $testItems);

echo "[OK] Utworzono zamĂłwienie o ID: $orderId\n";

$order = $model->getOrderById($orderId);
if (!$order) {
    echo "ERROR: Nie znaleziono zapisanego zamĂłwienia!\n";
    exit(1);
}
echo "[OK] Odczyt nagĹ‚Ăłwka: {$order['order_number']}, kwota: {$order['total_amount']} zĹ‚, pozycje: {$order['total_items']}\n";

$items = $model->getOrderItems($orderId);
echo "[OK] Liczba zapisanych pozycji w bazie: " . count($items) . "\n";

foreach ($items as $it) {
    echo "  - {$it['product_name']}: {$it['quantity']} {$it['unit']} x {$it['unit_price']} zĹ‚ = {$it['item_total']} zĹ‚\n";
}

if (count($items) !== 3) {
    echo "ERROR: Oczekiwano dokĹ‚adnie 3 pozycji o iloĹ›ci > 0, otrzymano: " . count($items) . "\n";
    exit(1);
}

$all = $model->getAllOrders();
echo "[OK] Wszystkich zamĂłwieĹ„ w bazie: " . count($all) . "\n";

echo "=== ALL ORDER MODEL TESTS PASSED! ===\n";
