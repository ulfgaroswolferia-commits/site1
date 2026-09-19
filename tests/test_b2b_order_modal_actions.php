<?php
/**
 * Test TDD: Weryfikacja opcji zmiany statusu oraz pobrania Excela w oknie podglądu zamówienia (panel hurtownika)
 */
define('BASE_PATH', 'C:/laragon/www');
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';
require_once BASE_PATH . '/program/config/includes.php';

use App\B2bRepository;

echo "=== TEST: Zmiana statusu i pobranie Excela w modalu zamówienia ===\n";

$repo = new B2bRepository();

// Upewnij się, że mamy przynajmniej jedno zamówienie
$orders = $repo->getAllOrders(1);
if (empty($orders)) {
    $orderNumber = $repo->generateOrderNumber();
    $orderId = $repo->createOrder([
        'order_number'              => $orderNumber,
        'client_id'                 => 1,
        'client_name_snapshot'      => 'Test Odbiorca',
        'client_phone_snapshot'     => '123456789',
        'delivery_address_snapshot' => 'ul. Hurtowa 1',
        'total_amount'              => 50.0,
        'notes'                     => 'Test'
    ], [
        [
            'product_id'      => 1,
            'product_name'    => 'Jabłka',
            'price'           => 5.0,
            'quantity'        => 10,
            'unit'            => 'kg',
            'package_size'    => 1.0,
            'package_unit'    => 'op.',
            'package_summary' => '10 kg',
            'item_total'      => 50.0
        ]
    ]);
} else {
    $orderId = (int)$orders[0]['id'];
}

$cookieFile = tempnam(sys_get_temp_dir(), 'cook_modal_actions_');
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);

// Logowanie admina
curl_setopt($ch, CURLOPT_URL, 'http://localhost/home/login');
$resLogin = curl_exec($ch);
preg_match('/name="_csrf" value="([^"]+)"/', $resLogin, $m);
$loginCsrf = $m[1] ?? '';

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'login' => APP_LOGIN,
    'password' => APP_PASSWORD,
    '_csrf' => $loginCsrf
]));
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_exec($ch);

// Pobranie widoku admina
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_URL, 'http://localhost/b2b/admin');
$html = curl_exec($ch);

// 1. Sprawdzenie przycisku pobrania Excela w modalu
$hasDownloadBtn = strpos($html, 'id="modal-order-download-btn"') !== false;
echo "1. Przycisk pobrania Excela (#modal-order-download-btn) w modalu: " . ($hasDownloadBtn ? "PASS" : "FAIL") . "\n";

// 2. Sprawdzenie pola zmiany statusu w modalu
$hasStatusSelect = strpos($html, 'id="modal-order-status"') !== false;
echo "2. Selektor zmiany statusu (#modal-order-status) w modalu: " . ($hasStatusSelect ? "PASS" : "FAIL") . "\n";

// 3. Sprawdzenie funkcji changeModalOrderStatus w skrypcie JS
$hasChangeFn = strpos($html, 'changeModalOrderStatus') !== false;
echo "3. Funkcja obsługi zmiany statusu z poziomu modalu (changeModalOrderStatus): " . ($hasChangeFn ? "PASS" : "FAIL") . "\n";

// 4. Test endpointu pobierania Excela b2b/download?id=...
curl_setopt($ch, CURLOPT_URL, "http://localhost/b2b/download?id={$orderId}");
$excelBody = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

$excelOk = ($httpCode === 200 && strlen($excelBody) > 1000);
echo "4. Pobranie karty kompletacji Excel (/b2b/download?id={$orderId}): " . ($excelOk ? "PASS" : "FAIL") . " (HTTP {$httpCode}, " . strlen($excelBody) . " bajtów)\n";

// 5. Test aktualizacji statusu przez API
preg_match('/const CSRF_TOKEN\s*=\s*\'([^\']+)\'/', $html, $mAdminCsrf);
$csrfToken = $mAdminCsrf[1] ?? $loginCsrf;

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'id'     => $orderId,
    'status' => 'processing',
    '_csrf'  => $csrfToken
]));
curl_setopt($ch, CURLOPT_URL, 'http://localhost/b2b/updateorderstatus');
$resUpdate = curl_exec($ch);
$updateData = json_decode($resUpdate, true);
$updateOk = (($updateData['ok'] ?? false) === true);

$orderAfter = $repo->getOrderById($orderId);
$statusChanged = ($orderAfter && $orderAfter['status'] === 'processing');
echo "5. Zmiana statusu zamówienia na 'processing': " . (($updateOk && $statusChanged) ? "PASS" : "FAIL") . "\n";

@unlink($cookieFile);

if (!$hasDownloadBtn || !$hasStatusSelect || !$hasChangeFn || !$excelOk || !$updateOk || !$statusChanged) {
    echo "=== TESTY ZAKOŃCZONE BŁĘDEM ===\n";
    exit(1);
}

echo "=== WSZYSTKIE TESTY MODALU ZAMÓWIENIA ZAKOŃCZONE SUKCESEM ===\n";
