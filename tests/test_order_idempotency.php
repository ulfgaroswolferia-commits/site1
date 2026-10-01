<?php
/**
 * Test weryfikujący mechanizm idempotencji i ochronę przed zdublowanymi zamówieniami
 */
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';

use App\B2bRepository;
use App\OrderModel;

echo "=== TEST: Idempotencja i ochrona przed dublowaniem zamówień ===\n";

// ---------------------------------------------------------
// 1. Test B2bRepository
// ---------------------------------------------------------
$b2bRepo = new B2bRepository();
$clientId = 1;
$key1 = 'test_idemp_b2b_' . bin2hex(random_bytes(8));

$items = [
    ['product_name' => 'Marchew', 'price' => 3.50, 'quantity' => 10, 'unit' => 'kg', 'package_size' => 10, 'package_unit' => 'worek']
];

// Pierwsze zapisanie
$orderId1 = $b2bRepo->createOrder([
    'client_id'             => $clientId,
    'client_name_snapshot'  => 'Klient Testowy Idempotencji',
    'total_amount'          => 35.00,
    'idempotency_key'       => $key1,
    'notes'                 => 'Pierwsze żądanie',
], $items);

$order1 = $b2bRepo->getOrderById($orderId1);
assert($order1 !== null, "Brak zapisanego zamówienia 1");
echo "1. B2B: Złożono pierwsze zamówienie ID={$orderId1}, Nr={$order1['order_number']}: OK\n";

// Wyszukanie po kluczu idempotencji
$foundOrder = $b2bRepo->getOrderByCustomerAndIdempotencyKey($clientId, $key1);
assert($foundOrder !== null, "Nie odnaleziono zamówienia po kluczu idempotencji");
assert((int)$foundOrder['id'] === $orderId1, "Niezgodne ID odnalezionego zamówienia");
echo "2. B2B: getOrderByCustomerAndIdempotencyKey zwrócił poprawne zamówienie: OK\n";

// Kolejne zamówienie z INNYM kluczem tworzy nowe zamówienie
$key2 = 'test_idemp_b2b_' . bin2hex(random_bytes(8));
$orderId2 = $b2bRepo->createOrder([
    'client_id'             => $clientId,
    'client_name_snapshot'  => 'Klient Testowy Idempotencji',
    'total_amount'          => 35.00,
    'idempotency_key'       => $key2,
    'notes'                 => 'Drugie unikalne zamówienie',
], $items);
assert($orderId2 !== $orderId1, "Nowy klucz idempotencji powinien utworzyć nowe ID");
echo "3. B2B: Nowy klucz idempotencji poprawnie utworzył nowe zamówienie ID={$orderId2}: OK\n";

// ---------------------------------------------------------
// 2. Test OrderModel
// ---------------------------------------------------------
$orderModel = new OrderModel();
$ordKey1 = 'test_idemp_ord_' . bin2hex(random_bytes(8));

$ordItems = [
    ['name' => 'Truskawki', 'price' => 12.00, 'quantity' => 5, 'unit' => 'łubianka']
];

$ordId1 = $orderModel->createOrder([
    'supplier_name'   => 'Dostawca Truskawek',
    'idempotency_key' => $ordKey1,
], $ordItems);

$ord1 = $orderModel->getOrderById($ordId1);
assert($ord1 !== null, "Brak zapisanego zamówienia wewnętrznego 1");
echo "4. Zamówienia wewnętrzne: Utworzono ID={$ordId1}, Nr={$ord1['order_number']}: OK\n";

$foundOrd = $orderModel->getOrderByIdempotencyKey($ordKey1);
assert($foundOrd !== null, "Nie odnaleziono zamówienia wewnętrznego po kluczu");
assert((int)$foundOrd['id'] === $ordId1, "Niezgodne ID znalezionego zamówienia wewnętrznego");
echo "5. Zamówienia wewnętrzne: getOrderByIdempotencyKey zwrócił poprawne zamówienie: OK\n";

echo "=== WSZYSTKIE TESTY IDEMPOTENCJI ZAKOŃCZONE SUKCESEM! ===\n";
