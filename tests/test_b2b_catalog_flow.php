<?php
/**
 * Test przepływu klienta B2B: autologowanie tokenem, asystent opakowań i złożenie zamówienia
 */
define('BASE_PATH', 'C:/laragon/www');
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';

$cookieFile = tempnam(sys_get_temp_dir(), 'cook_b2b_client_');
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);

function httpReq($url, $post = null, $headers = [], $follow = false) {
    global $ch;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $follow);
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    } else {
        curl_setopt($ch, CURLOPT_HTTPHEADER, []);
    }
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        $hasFile = false;
        if (is_array($post)) {
            foreach ($post as $v) {
                if ($v instanceof CURLFile) { $hasFile = true; break; }
            }
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, ($hasFile || !is_array($post)) ? $post : http_build_query($post));
    } else {
        curl_setopt($ch, CURLOPT_HTTPGET, true);
    }
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $ct = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    return ['code' => $code, 'ct' => $ct, 'body' => $body];
}

echo "=== TEST: B2B Client Flow ===\n";

$repo = new \App\B2bRepository();

// 1. Dodaj klienta testowego i produkty
$clientToken = bin2hex(random_bytes(16));
$clientId = $repo->createClient([
    'company_name'     => 'Warzywniak Zielony Zakątek',
    'phone'            => '511222333',
    'delivery_address' => 'ul. Polna 8, Lublin',
    'auth_token'       => $clientToken
]);

$repo->saveProductsBatch([
    [
        'name'         => 'Mango Brazylia',
        'category'     => 'Owoce',
        'unit'         => 'szt.',
        'price'        => 6.00,
        'package_size' => 7.0,
        'package_unit' => 'klatka',
        'is_available' => 1
    ],
    [
        'name'         => 'Ziemniak jadalny',
        'category'     => 'Warzywa',
        'unit'         => 'kg',
        'price'        => 2.20,
        'package_size' => 15.0,
        'package_unit' => 'worek',
        'is_available' => 1
    ]
], true);

// 2. Wejście z tokenem do /b2b?token=...
$resCatalog = httpReq('http://localhost/b2b?token=' . $clientToken, null, [], true);
echo "1. Wejście z tokenem autologowania: {$resCatalog['code']}\n";

$hasClientName = strpos($resCatalog['body'], 'Warzywniak Zielony Zakątek') !== false;
$hasMango      = strpos($resCatalog['body'], 'Mango Brazylia') !== false;
$hasBoxBadge   = strpos($resCatalog['body'], 'klatka') !== false;

echo "2. Rozpoznano nazwę klienta w nagłówku: " . ($hasClientName ? "OK" : "BŁĄD") . "\n";
echo "3. Wyświetlono towar 'Mango Brazylia': " . ($hasMango ? "OK" : "BŁĄD") . "\n";
echo "4. Wyświetlono asystenta opakowań: " . ($hasBoxBadge ? "OK" : "BŁĄD") . "\n";

// 3. Odczyt CSRF z katalogu
preg_match('/const CSRF_TOKEN = \'([^\']+)\'/', $resCatalog['body'], $m);
$csrfToken = $m[1] ?? '';

// 4. Złożenie zamówienia B2B przez API
$itemsToOrder = [
    [
        'product_name' => 'Mango Brazylia',
        'price'        => 6.00,
        'quantity'     => 14, // 2 pełne klatki
        'unit'         => 'szt.',
        'package_size' => 7.0,
        'package_unit' => 'klatka'
    ],
    [
        'product_name' => 'Ziemniak jadalny',
        'price'        => 2.20,
        'quantity'     => 35, // 2 worki + 5 kg luzem
        'unit'         => 'kg',
        'package_size' => 15.0,
        'package_unit' => 'worek'
    ]
];

$resOrder = httpReq('http://localhost/b2b/saveorder', [
    'items' => json_encode($itemsToOrder),
    'notes' => 'Dostawa przed godziną 6:30',
    '_csrf' => $csrfToken
], ['X-Requested-With: XMLHttpRequest', 'Accept: application/json']);

echo "5. Złożenie zamówienia B2B (HTTP code): {$resOrder['code']}\n";
echo "   Odpowiedź JSON: {$resOrder['body']}\n";

$dataOrder = json_decode($resOrder['body'], true);
$orderOk = ($resOrder['code'] === 200 && ($dataOrder['ok'] ?? false) === true && !empty($dataOrder['order_number']));

echo "6. Sukces zapisu zamówienia: " . ($orderOk ? "OK ({$dataOrder['order_number']})" : "BŁĄD") . "\n";

@unlink($cookieFile);

$passed = ($resCatalog['code'] === 200 && $hasClientName && $hasMango && $hasBoxBadge && $orderOk);
echo $passed ? "=== ALL B2B CLIENT FLOW TESTS PASSED ===\n" : "=== TESTS FAILED ===\n";
exit($passed ? 0 : 1);
