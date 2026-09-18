<?php
/**
 * Test weryfikujący opcję "Zatwierdź i wyślij" dla zamówień e-mail.
 */
define('BASE_PATH', 'C:/laragon/www');
require_once BASE_PATH . '/program/config/data.php';

$cookieFile = tempnam(sys_get_temp_dir(), 'cook_mail_');
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

echo "=== TEST: Order Save With Email Option ===\n";

// 1. Zaloguj się
$resLoginGet = httpReq('http://localhost/home/login');
preg_match('/name="_csrf" value="([^"]+)"/', $resLoginGet['body'], $m);
$loginCsrf = $m[1] ?? '';

httpReq('http://localhost/home/login', [
    'login' => APP_LOGIN,
    'password' => APP_PASSWORD,
    '_csrf' => $loginCsrf
], [], true);

// 2. Pobierz stronę /order/index i sprawdź CSRF
$resOrderGet = httpReq('http://localhost/order/index');
preg_match('/const CSRF_TOKEN = \'([^\']+)\'/', $resOrderGet['body'], $mOrder);
$orderCsrf = $mOrder[1] ?? '';

// 3. Sprawdź obecność przycisku "Zatwierdź i wyślij" w HTML
$hasSendBtn = strpos($resOrderGet['body'], 'id="btn-submit-send-order"') !== false;
echo "1. Przycisk 'Zatwierdź i wyślij' w HTML: " . ($hasSendBtn ? "TAK" : "NIE") . "\n";

// 4. Wyślij żądanie zapisu z send_email = 1
$items = [
    [
        'name' => 'Pomidory malinowe',
        'price' => 8.50,
        'quantity' => 5,
        'unit' => 'kg'
    ]
];

$resSave = httpReq('http://localhost/order/save', [
    'supplier_name' => 'Hurtownia E-mail Test',
    'original_filename' => 'cennik_mail.xlsx',
    'items' => json_encode($items),
    'send_email' => 1,
    'recipient_email' => 'hurtownia@example.com',
    '_csrf' => $orderCsrf
], ['X-Requested-With: XMLHttpRequest', 'Accept: application/json']);

echo "2. Status zapisu z e-mail: {$resSave['code']}\n";
echo "   Odpowiedź JSON: {$resSave['body']}\n";

$data = json_decode($resSave['body'], true);
$saveOk = ($resSave['code'] === 200 && ($data['ok'] ?? false) === true);
$hasEmailField = isset($data['email_status']);

echo "3. Zamówienie zapisane pomyślnie: " . ($saveOk ? "TAK" : "NIE") . "\n";
echo "4. Zwrócono status wysyłki email: " . ($hasEmailField ? "TAK ({$data['email_status']})" : "NIE") . "\n";

// 5. Zapis standardowy bez send_email (pobranie Excela)
$resSaveStandard = httpReq('http://localhost/order/save', [
    'supplier_name' => 'Hurtownia Standard',
    'original_filename' => 'cennik_std.xlsx',
    'items' => json_encode($items),
    '_csrf' => $orderCsrf
], ['X-Requested-With: XMLHttpRequest', 'Accept: application/json']);
$dataStd = json_decode($resSaveStandard['body'], true);
$standardNoEmail = !isset($dataStd['email_status']) && ($dataStd['ok'] ?? false) === true;
echo "5. Standardowy zapis bez send_email (brak flagi email): " . ($standardNoEmail ? "TAK" : "NIE") . "\n";

// 6. Sprawdzenie generatora Mailer::buildOrderEmailHtml
require_once BASE_PATH . '/program/config/includes.php';
$html = Mailer::buildOrderEmailHtml([
    'order_number' => 'ZAM/TEST/01',
    'supplier_name' => 'Agro-Test',
    'created_at' => '2026-09-18 21:00',
    'total_amount' => 42.50
], $items);
$htmlValid = (strpos($html, 'Zamawiarka Magdy') !== false && strpos($html, 'Pomidory malinowe') !== false && strpos($html, '42.50 zł') !== false);
echo "6. Generator szablonu HTML Mailera: " . ($htmlValid ? "TAK" : "NIE") . "\n";

@unlink($cookieFile);

$passed = ($hasSendBtn && $saveOk && $hasEmailField && $standardNoEmail && $htmlValid);
exit($passed ? 0 : 1);
