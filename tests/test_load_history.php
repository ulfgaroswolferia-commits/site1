<?php
/**
 * Test TDD dla wczytywania cennika z historii zamówień.
 */
define('BASE_PATH', 'C:/laragon/www');
require_once BASE_PATH . '/program/config/data.php';

$cookieFile = tempnam(sys_get_temp_dir(), 'cook_hist_');
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

echo "=== TEST: Load Price List From History ===\n";

// 1. Zaloguj się
$resLoginGet = httpReq('http://localhost/home/login');
preg_match('/name="_csrf" value="([^"]+)"/', $resLoginGet['body'], $m);
$loginCsrf = $m[1] ?? '';

$resLoginPost = httpReq('http://localhost/home/login', [
    'login' => APP_LOGIN,
    'password' => APP_PASSWORD,
    '_csrf' => $loginCsrf
], [], true);

// 2. Pobierz stronę /order/index i sprawdź czy zawiera recent_orders oraz sekcję wyboru
$resOrderGet = httpReq('http://localhost/order/index');
preg_match('/const CSRF_TOKEN = \'([^\']+)\'/', $resOrderGet['body'], $mOrder);
$orderCsrf = $mOrder[1] ?? '';
$hasSelect = strpos($resOrderGet['body'], 'id="history-order-select"') !== false;
echo "2. Select historii w HTML: " . ($hasSelect ? "TAK" : "NIE") . "\n";

// 3. Wywołaj endpoint /order/load-history dla istniejącego zamówienia
// Sprawdźmy jakie ID zamówienia istnieje w bazie SQLite
require_once BASE_PATH . '/program/core/Model.php';
require_once BASE_PATH . '/program/model/OrderModel.php';
$model = new \App\OrderModel();
$orders = $model->getAllOrders(1);
if (empty($orders)) {
    // Stwórzmy jedno przykładowe zamówienie
    $orderId = $model->createOrder([
        'order_number'      => $model->generateOrderNumber(),
        'supplier_name'     => 'Hurtownia Testowa TDD',
        'original_filename' => 'cennik_test.xlsx',
        'export_filename'   => 'zamowienie_test.xlsx'
    ], [
        ['name' => 'Marchew', 'unit_price' => 2.50, 'quantity' => 10, 'unit' => 'kg', 'item_total' => 25.0]
    ]);
} else {
    $orderId = (int)$orders[0]['id'];
}

echo "Wybrane ID zamówienia do testu: $orderId\n";

$resLoad = httpReq('http://localhost/order/loadhistory', [
    'order_id' => $orderId,
    '_csrf' => $orderCsrf
], ['X-Requested-With: XMLHttpRequest', 'Accept: application/json']);

echo "Status odpowiedzi: " . $resLoad['code'] . "\n";
echo "Typ odpowiedzi: " . $resLoad['ct'] . "\n";
echo "Treść odpowiedzi: " . $resLoad['body'] . "\n";

$data = json_decode($resLoad['body'], true);
$ok = ($resLoad['code'] === 200 && ($data['ok'] ?? false) === true && !empty($data['products']));

if ($ok) {
    echo "[PASS] Endpoint /order/load-history zwrócił poprawny asortyment!\n";
} else {
    echo "[FAIL] Endpoint nie zadziałał poprawnie.\n";
}

@unlink($cookieFile);
exit($ok ? 0 : 1);
