<?php
/**
 * Test TDD: Błyskawiczne składanie zamówienia w B2B (ochrona przed blokowaniem przez SMTP i optymalizacja czasu odpowiedzi).
 */
define('BASE_PATH', 'C:/laragon\www');
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';
require_once BASE_PATH . '/program/config/includes.php';

use App\B2bRepository;

echo "=== TEST: Błyskawiczne składanie zamówienia B2B ===\n";

// 1. Sprawdzenie czasu wykonania Mailer::send gdy SMTP_HOST jest pusty
$t0 = microtime(true);
$mailResult = Mailer::send('<p>Test</p>', 'hurtownia@example.com', 'Test time');
$t1 = microtime(true);
$mailDurationMs = ($t1 - $t0) * 1000;

echo "1. Czas Mailer::send: " . round($mailDurationMs, 2) . " ms (oczekiwano < 100 ms): ";
$mailFast = ($mailDurationMs < 100);
echo ($mailFast ? "PASS" : "FAIL") . "\n";

// 2. Przygotowanie klienta i sesji do testu HTTP /b2b/saveorder
$repo = new B2bRepository();
$client = $repo->getClientByLogin('magda');
if (!$client) {
    $all = $repo->getAllClients();
    $client = !empty($all) ? $all[0] : null;
}
assert($client !== null, "Brak klienta B2B do testu!");

$cookieFile = tempnam(sys_get_temp_dir(), 'cook_fast_order_');
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);

// Pobranie katalogu z tokenem klienta
curl_setopt($ch, CURLOPT_URL, 'http://localhost/b2b?token=' . urlencode($client['auth_token']));
$catalogHtml = curl_exec($ch);
preg_match('/const CSRF_TOKEN = \'([^\']+)\';/', $catalogHtml, $mCsrf);
$csrfToken = $mCsrf[1] ?? '';
assert($csrfToken !== '', "Brak tokenu CSRF z katalogu!");

// 3. Wysłanie zamówienia i pomiar czasu całego żądania HTTP
$items = [
    [
        'product_name'    => 'Marchewka Test Szybkości',
        'price'           => 3.20,
        'quantity'        => 20,
        'unit'            => 'kg',
        'package_size'    => 10.0,
        'package_unit'    => 'worek',
        'package_summary' => '2 worki',
        'item_total'      => 64.00
    ]
];

$postData = [
    'items' => json_encode($items),
    'notes' => 'Test szybkiego zapisu',
    '_csrf' => $csrfToken
];

$tHttp0 = microtime(true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_URL, 'http://localhost/b2b/saveorder');
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-Requested-With: XMLHttpRequest',
    'Accept: application/json'
]);

$responseJson = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$tHttp1 = microtime(true);
$httpDurationMs = ($tHttp1 - $tHttp0) * 1000;

curl_close($ch);
@unlink($cookieFile);

echo "2. Kod HTTP: {$httpCode}: " . ($httpCode === 200 ? "PASS" : "FAIL") . "\n";
echo "3. Czas odpowiedzi serwera na złożenie zamówienia: " . round($httpDurationMs, 2) . " ms (oczekiwano < 800 ms): ";
$httpFast = ($httpDurationMs < 800);
echo ($httpFast ? "PASS" : "FAIL") . "\n";

$data = json_decode($responseJson, true);
$orderOk = (!empty($data['ok']) && !empty($data['order_number']));
echo "4. Prawidłowa odpowiedź JSON z numerem zamówienia: " . ($orderOk ? "PASS ({$data['order_number']})" : "FAIL") . "\n";

$hasMinDelay = (strpos($catalogHtml, 'setTimeout(resolve, 1000)') !== false);
echo "5. Płynne opóźnienie UX (min 1 sekunda animacji w JS): " . ($hasMinDelay ? "PASS" : "FAIL") . "\n";

// Sprzątanie po zamówieniu testowym
if (!empty($data['order_id'])) {
    $repo->getPdo()->exec("DELETE FROM b2b_order_items WHERE order_id = " . (int)$data['order_id']);
    $repo->getPdo()->exec("DELETE FROM b2b_orders WHERE id = " . (int)$data['order_id']);
}
if (!empty($data['export_filename'])) {
    @unlink(BASE_PATH . '/storage/b2b/orders/' . $data['export_filename']);
}

if (!$mailFast || $httpCode !== 200 || !$httpFast || !$orderOk || !$hasMinDelay) {
    echo "=== TEST ZAKOŃCZONY BŁĘDEM (Stan RED) ===\n";
    exit(1);
}

echo "=== TEST ZAKOŃCZONY SUKCESEM (Stan GREEN) ===\n";

