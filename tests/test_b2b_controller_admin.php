<?php
/**
 * Test integracyjny API administracyjnego B2bController
 */
define('BASE_PATH', 'C:/laragon/www');
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';

$cookieFile = tempnam(sys_get_temp_dir(), 'cook_b2b_admin_');
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

echo "=== TEST: B2bController Admin API ===\n";

// 1. Logowanie do panelu administratora
$resLoginGet = httpReq('http://localhost/home/login');
preg_match('/name="_csrf" value="([^"]+)"/', $resLoginGet['body'], $m);
$loginCsrf = $m[1] ?? '';

httpReq('http://localhost/home/login', [
    'login' => APP_LOGIN,
    'password' => APP_PASSWORD,
    '_csrf' => $loginCsrf
], [], true);

// 2. Pobierz stronę /b2b/admin i odczytaj CSRF
$resAdminGet = httpReq('http://localhost/b2b/admin');
echo "1. Wejście na /b2b/admin jako admin: {$resAdminGet['code']}\n";
preg_match('/const CSRF_TOKEN = \'([^\']+)\'/', $resAdminGet['body'], $mB2b);
$csrfToken = $mB2b[1] ?? $loginCsrf;

// 3. Utwórz nowego klienta B2B przez API
$resClient = httpReq('http://localhost/b2b/createclient', [
    'company_name'     => 'Warzywkowo Sp. z o.o.',
    'nip'              => '9876543210',
    'phone'            => '600700800',
    'email'            => 'kontakt@warzywkowo.pl',
    'delivery_address' => 'ul. Główna 12, Radom',
    '_csrf'            => $csrfToken
], ['X-Requested-With: XMLHttpRequest', 'Accept: application/json']);

$dataClient = json_decode($resClient['body'], true);
$clientOk = ($resClient['code'] === 200 && ($dataClient['ok'] ?? false) === true && !empty($dataClient['auth_token']));
echo "2. Utworzenie klienta B2B przez API: " . ($clientOk ? "OK (Token: {$dataClient['auth_token']})" : "BŁĄD: {$resClient['body']}") . "\n";

// 4. Edycja produktu (cena, klatka) przez API
$repo = new \App\B2bRepository();
$allProds = $repo->getAllProductsAdmin();
if (empty($allProds)) {
    // Zainicjuj produkt do testu
    $repo->saveProductsBatch([[
        'name' => 'Cytryna hiszpańska',
        'category' => 'Cytrusy',
        'unit' => 'kg',
        'price' => 7.00,
        'package_size' => 10.0,
        'package_unit' => 'karton',
        'is_available' => 1
    ]]);
    $allProds = $repo->getAllProductsAdmin();
}
$prodId = (int)$allProds[0]['id'];

$resUpdate = httpReq('http://localhost/b2b/updateproduct', [
    'id'           => $prodId,
    'price'        => 9.99,
    'package_size' => 8.0,
    'package_unit' => 'karton',
    'category'     => 'Cytrusy',
    '_csrf'        => $csrfToken
], ['X-Requested-With: XMLHttpRequest', 'Accept: application/json']);

$dataUpdate = json_decode($resUpdate['body'], true);
$updateOk = ($resUpdate['code'] === 200 && ($dataUpdate['ok'] ?? false) === true);
echo "3. Edycja produktu przez API: " . ($updateOk ? "OK" : "BŁĄD: {$resUpdate['body']}") . "\n";

// 5. Przełącznik dostępności towaru
$resToggle = httpReq('http://localhost/b2b/toggleproduct', [
    'id'    => $prodId,
    '_csrf' => $csrfToken
], ['X-Requested-With: XMLHttpRequest', 'Accept: application/json']);

$dataToggle = json_decode($resToggle['body'], true);
$toggleOk = ($resToggle['code'] === 200 && ($dataToggle['ok'] ?? false) === true);
echo "4. Przełączenie dostępności produktu: " . ($toggleOk ? "OK" : "BŁĄD") . "\n";

@unlink($cookieFile);

$passed = ($resAdminGet['code'] === 200 && $clientOk && $updateOk && $toggleOk);
echo $passed ? "=== ALL B2B ADMIN API TESTS PASSED ===\n" : "=== TESTS FAILED ===\n";
exit($passed ? 0 : 1);
