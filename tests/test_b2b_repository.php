<?php
/**
 * Test jednostkowy i integracyjny dla App\B2bRepository
 */
define('BASE_PATH', 'C:/laragon/www');
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';

echo "=== TEST: B2bRepository ===\n";

$repo = new \App\B2bRepository();

// 1. Inicjalizacja bazy
$repo->initDatabase();
echo "1. Inicjalizacja bazy: OK\n";

// 2. Klienci - dodanie i wyszukiwanie po tokenie
$token = bin2hex(random_bytes(16));
$clientId = $repo->createClient([
    'company_name'     => 'Warzywniak U Ani Test',
    'nip'              => '1234567890',
    'phone'            => '500600700',
    'email'            => 'ania@example.com',
    'delivery_address' => 'ul. Kwiatowa 5, 00-001 Warszawa',
    'auth_token'       => $token,
    'login'            => 'warzywniak_ania',
    'password'         => 'tajne123'
]);

echo "2. Utworzono klienta o ID: {$clientId}\n";

$clientByToken = $repo->getClientByToken($token);
$tokenMatch = ($clientByToken && $clientByToken['company_name'] === 'Warzywniak U Ani Test');
echo "3. Wyszukiwanie po tokenie: " . ($tokenMatch ? "OK" : "BŁĄD") . "\n";

$clientByLogin = $repo->getClientByLogin('warzywniak_ania');
$loginMatch = ($clientByLogin && password_verify('tajne123', $clientByLogin['password_hash']));
echo "4. Wyszukiwanie po loginie i weryfikacja hasła: " . ($loginMatch ? "OK" : "BŁĄD") . "\n";

// 3. Reguły opakowań (inteligentna pamięć klatek/opakowań)
$repo->savePackageRule('mango', 7.0, 'klatka', 'szt.');
$repo->savePackageRule('pomidor malinowy', 6.0, 'skrzynka', 'kg');

$ruleMango = $repo->getPackageRule('Mango Ready to Eat');
$ruleOk = ($ruleMango && (float)$ruleMango['package_size'] === 7.0 && $ruleMango['package_unit'] === 'klatka');
echo "5. Reguła inteligentnego opakowania dla Mango: " . ($ruleOk ? "OK (7 szt./klatka)" : "BŁĄD") . "\n";

// 4. Zapis produktów z cennika
$products = [
    [
        'name'         => 'Mango Ready to Eat',
        'category'     => 'Owoce',
        'unit'         => 'szt.',
        'price'        => 6.50,
        'package_size' => 7.0,
        'package_unit' => 'klatka',
        'is_available' => 1
    ],
    [
        'name'         => 'Pomidor malinowy PL',
        'category'     => 'Warzywa',
        'unit'         => 'kg',
        'price'        => 8.20,
        'package_size' => 6.0,
        'package_unit' => 'skrzynka',
        'is_available' => 1
    ],
    [
        'name'         => 'Ziemniak młody',
        'category'     => 'Warzywa',
        'unit'         => 'kg',
        'price'        => 2.50,
        'package_size' => 15.0,
        'package_unit' => 'worek',
        'is_available' => 1
    ]
];

$savedCount = $repo->saveProductsBatch($products, true);
echo "6. Zapisano partię produktów: {$savedCount}\n";

$activeProducts = $repo->getActiveProducts();
$countOk = (count($activeProducts) >= 3);
echo "7. Pobrano aktywny asortyment: " . ($countOk ? "OK (" . count($activeProducts) . " poz.)" : "BŁĄD") . "\n";

// 5. Szybka zmiana dostępności i edycja ceny
$firstProdId = (int)$activeProducts[0]['id'];
$repo->toggleProductAvailability($firstProdId, 0);
$afterToggle = $repo->getActiveProducts();
$toggleOk = (count($afterToggle) === count($activeProducts) - 1);
echo "8. Przełącznik dostępności towaru na dziś: " . ($toggleOk ? "OK" : "BŁĄD") . "\n";

// Przywróć dostępność
$repo->toggleProductAvailability($firstProdId, 1);

// 6. Tworzenie zamówienia B2B
$orderNumber = $repo->generateOrderNumber();
$orderItems = [
    [
        'product_id'      => $firstProdId,
        'product_name'    => 'Mango Ready to Eat',
        'price'           => 6.50,
        'quantity'        => 14,
        'unit'            => 'szt.',
        'package_size'    => 7.0,
        'package_unit'    => 'klatka',
        'package_summary' => '2 klatki',
        'item_total'      => 91.00
    ],
    [
        'product_id'      => (int)$activeProducts[1]['id'],
        'product_name'    => 'Pomidor malinowy PL',
        'price'           => 8.20,
        'quantity'        => 15,
        'unit'            => 'kg',
        'package_size'    => 6.0,
        'package_unit'    => 'skrzynka',
        'package_summary' => '2 skrzynki + 3 kg',
        'item_total'      => 123.00
    ]
];

$orderId = $repo->createOrder([
    'order_number'               => $orderNumber,
    'client_id'                  => $clientId,
    'client_name_snapshot'       => 'Warzywniak U Ani Test',
    'client_phone_snapshot'      => '500600700',
    'delivery_address_snapshot'  => 'ul. Kwiatowa 5, Warszawa',
    'total_amount'               => 214.00,
    'notes'                      => 'Dostawa przed 7:00 rano'
], $orderItems);

echo "9. Utworzono zamówienie B2B o ID: {$orderId} ({$orderNumber})\n";

$order = $repo->getOrderById($orderId);
$items = $repo->getOrderItems($orderId);
$orderOk = ($order && $order['status'] === 'new' && count($items) === 2);
echo "10. Odczyt zamówienia i pozycji z bazy: " . ($orderOk ? "OK" : "BŁĄD") . "\n";

// 7. Zmiana statusu zamówienia
$repo->updateOrderStatus($orderId, 'processing');
$orderUpdated = $repo->getOrderById($orderId);
$statusOk = ($orderUpdated['status'] === 'processing');
echo "11. Zmiana statusu na 'processing': " . ($statusOk ? "OK" : "BŁĄD") . "\n";

$passed = ($clientId > 0 && $tokenMatch && $loginMatch && $ruleOk && $savedCount === 3 && $countOk && $toggleOk && $orderOk && $statusOk);
echo $passed ? "=== ALL B2B REPOSITORY TESTS PASSED ===\n" : "=== TESTS FAILED ===\n";
exit($passed ? 0 : 1);
