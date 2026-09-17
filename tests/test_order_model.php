<?php
define('BASE_PATH', 'C:/laragon/www');
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/core/Model.php';
require_once BASE_PATH . '/program/lib/Db.php';
require_once BASE_PATH . '/program/model/OrderModel.php';

echo "=== TEST ORDER MODEL (SQLITE) ===\n";

$model = new \App\OrderModel();
$orderNum = $model->generateOrderNumber();
echo "[OK] Wygenerowany numer zamówienia: $orderNum\n";

$testItems = [
    ['name' => 'Pomidory malinowe', 'price' => 8.50, 'quantity' => 10.5, 'unit' => 'kg'],
    ['name' => 'Ogórki gruntowe',   'price' => 5.00, 'quantity' => 20.0, 'unit' => 'kg'],
    ['name' => 'Koperek świeży',    'price' => 1.80, 'quantity' => 15,   'unit' => 'pęczek'],
    ['name' => 'Rzodkiewka',        'price' => 2.50, 'quantity' => 0,    'unit' => 'pęczek'], // nie zamówione
];

$orderId = $model->createOrder([
    'order_number'      => $orderNum,
    'supplier_name'     => 'Hurtownia Agro-Plon',
    'original_filename' => 'cennik_wrzesien.xlsx',
    'export_filename'   => 'zamowienie_001.xlsx',
], $testItems);

echo "[OK] Utworzono zamówienie o ID: $orderId\n";

$order = $model->getOrderById($orderId);
if (!$order) {
    echo "ERROR: Nie znaleziono zapisanego zamówienia!\n";
    exit(1);
}
echo "[OK] Odczyt nagłówka: {$order['order_number']}, kwota: {$order['total_amount']} zł, pozycje: {$order['total_items']}\n";

$items = $model->getOrderItems($orderId);
echo "[OK] Liczba zapisanych pozycji w bazie: " . count($items) . "\n";

foreach ($items as $it) {
    echo "  - {$it['product_name']}: {$it['quantity']} {$it['unit']} x {$it['unit_price']} zł = {$it['item_total']} zł\n";
}

if (count($items) !== 3) {
    echo "ERROR: Oczekiwano dokładnie 3 pozycji o ilości > 0, otrzymano: " . count($items) . "\n";
    exit(1);
}

$all = $model->getAllOrders();
echo "[OK] Wszystkich zamówień w bazie: " . count($all) . "\n";

echo "=== ALL ORDER MODEL TESTS PASSED! ===\n";
