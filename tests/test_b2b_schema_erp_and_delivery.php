<?php
/**
 * Test TDD: Weryfikacja rozszerzenia schematu bazy: delivery_date, erp_code oraz b2b_settings
 */
define('BASE_PATH', 'C:/laragon\www');
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';

use App\B2bRepository;

echo "=== TEST: Schemat bazy danych dla ERP i daty dostawy ===\n";

$repo = new B2bRepository();
$pdo = $repo->getPdo();

// 1. Sprawdzenie tabeli b2b_settings i domyślnych wartości
$cutoff = $repo->getSetting('cutoff_time');
$erpFormat = $repo->getSetting('default_erp_format');
$deliveryDays = $repo->getSetting('delivery_days');

echo "1. Domyślny cutoff_time: '{$cutoff}' (oczekiwano: 21:30): ";
$cutoffOk = ($cutoff === '21:30');
echo ($cutoffOk ? "PASS" : "FAIL") . "\n";

echo "2. Domyślny format ERP: '{$erpFormat}' (oczekiwano: subiekt): ";
$erpOk = ($erpFormat === 'subiekt');
echo ($erpOk ? "PASS" : "FAIL") . "\n";

// Zmiana i odczyt ustawienia
$repo->setSetting('cutoff_time', '22:00');
$cutoffUpdated = $repo->getSetting('cutoff_time');
$repo->setSetting('cutoff_time', '21:30'); // przywrócenie
echo "3. Zapis i odczyt ustawienia w b2b_settings: " . ($cutoffUpdated === '22:00' ? "PASS" : "FAIL") . "\n";

// 2. Sprawdzenie kolumny erp_code w b2b_products
$prods = $repo->getAllProductsAdmin();
$firstProd = !empty($prods) ? $prods[0] : null;
assert($firstProd !== null, "Brak produktów w bazie!");

$testCode = 'TOW-TEST-99';
$repo->updateProduct((int)$firstProd['id'], ['erp_code' => $testCode]);
$prodAfter = $repo->getProductById((int)$firstProd['id']);
$erpCodeOk = (isset($prodAfter['erp_code']) && $prodAfter['erp_code'] === $testCode);
echo "4. Zapis i odczyt erp_code w b2b_products: " . ($erpCodeOk ? "PASS" : "FAIL") . "\n";

// 3. Sprawdzenie kolumny delivery_date w b2b_orders
$orderId = $repo->createOrder([
    'order_number'              => 'TEST/DELIVERY/' . time(),
    'client_id'                 => 1,
    'client_name_snapshot'      => 'Test Delivery Client',
    'delivery_date'             => '2026-09-22',
    'total_amount'              => 50.00,
    'status'                    => 'new'
], [
    [
        'product_name'    => 'Jabłko Test',
        'price'           => 5.0,
        'quantity'        => 10,
        'unit'            => 'kg',
        'package_size'    => 1.0,
        'package_unit'    => 'op.',
        'package_summary' => '10 kg',
        'item_total'      => 50.0
    ]
]);

$savedOrder = $repo->getOrderById($orderId);
$deliveryDateOk = (isset($savedOrder['delivery_date']) && $savedOrder['delivery_date'] === '2026-09-22');
echo "5. Zapis i odczyt delivery_date w b2b_orders: " . ($deliveryDateOk ? "PASS" : "FAIL (got " . ($savedOrder['delivery_date'] ?? 'null') . ")") . "\n";

// Sprzątanie
$pdo->exec("DELETE FROM b2b_order_items WHERE order_id = {$orderId}");
$pdo->exec("DELETE FROM b2b_orders WHERE id = {$orderId}");

if (!$cutoffOk || !$erpOk || $cutoffUpdated !== '22:00' || !$erpCodeOk || !$deliveryDateOk) {
    echo "=== TEST ZAKOŃCZONY BŁĘDEM (Stan RED) ===\n";
    exit(1);
}

echo "=== TEST ZAKOŃCZONY SUKCESEM (Stan GREEN) ===\n";
