<?php
/**
 * Test TDD: Weryfikacja blokowania klienta oraz zachowania aktywnej zakładki (Klienci Hurtowni).
 */
define('BASE_PATH', 'C:/laragon/www');
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';
require_once BASE_PATH . '/program/config/includes.php';

$cookieFile = tempnam(sys_get_temp_dir(), 'cook_toggle_tab_');
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);

function httpReq($url, $post = null, $headers = []) {
    global $ch;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    } else {
        curl_setopt($ch, CURLOPT_HTTPHEADER, []);
    }
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($post) ? http_build_query($post) : $post);
    } else {
        curl_setopt($ch, CURLOPT_HTTPGET, true);
    }
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    return ['code' => $code, 'body' => $body];
}

echo "=== TEST: Blokowanie klienta i zachowanie zakładki Klienci ===\n";

// 1. Zalogowanie do panelu administratora
$resLoginGet = httpReq('http://localhost/home/login');
preg_match('/name="_csrf" value="([^"]+)"/', $resLoginGet['body'], $m);
$loginCsrf = $m[1] ?? '';

httpReq('http://localhost/home/login', [
    'login' => APP_LOGIN,
    'password' => APP_PASSWORD,
    '_csrf' => $loginCsrf
]);

$resAdminGet = httpReq('http://localhost/b2b/admin');
preg_match('/const CSRF_TOKEN\s*=\s*\'([^\']+)\'/', $resAdminGet['body'], $mB2b);
$csrfToken = $mB2b[1] ?? $loginCsrf;
$adminHtml = $resAdminGet['body'];

// 2. Utworzenie klienta testowego
$unique = time() . '_' . mt_rand(100, 999);
$resCreate = httpReq('http://localhost/b2b/createclient', [
    'company_name'     => 'Klient Blokada ' . $unique,
    'nip'              => '9998887766',
    'phone'            => '+48 555 444 333',
    'email'            => 'blokada' . $unique . '@example.com',
    'delivery_address' => 'ul. Testowa 1',
    '_csrf'            => $csrfToken
], ['X-Requested-With: XMLHttpRequest', 'Accept: application/json']);

$dataCreate = json_decode($resCreate['body'], true);
$clientId = (int)($dataCreate['client_id'] ?? 0);
$token = $dataCreate['auth_token'] ?? '';
assert($clientId > 0, "Błąd tworzenia klienta testowego");
echo "1. Klient testowy utworzony (ID: {$clientId}, Token: {$token})\n";

// 3. Test API /b2b/toggleclient: zablokowanie
$resToggle1 = httpReq('http://localhost/b2b/toggleclient', [
    'id' => $clientId,
    '_csrf' => $csrfToken
], ['X-Requested-With: XMLHttpRequest', 'Accept: application/json']);

$dataToggle1 = json_decode($resToggle1['body'], true);
echo "2. Wynik toggleclient (zablokowanie): " . json_encode($dataToggle1) . "\n";

// Oczekujemy, że API zwraca is_active = 0
if (!isset($dataToggle1['is_active']) || $dataToggle1['is_active'] !== 0) {
    echo "FAIL: API toggleclient nie zwraca is_active: 0!\n";
} else {
    echo "PASS: API toggleclient poprawnie zwraca is_active: 0.\n";
}

// 4. Sprawdzenie próby dostępu przez klienta po zablokowaniu
$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_URL, "http://localhost/b2b?token={$token}");
curl_setopt($ch2, CURLOPT_FOLLOWLOCATION, false);
$resClientAccess = curl_exec($ch2);
$clientHttpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

if (in_array($clientHttpCode, [301, 302, 303, 403]) || strpos($resClientAccess, 'login') !== false) {
    echo "PASS: Zablokowany klient nie ma dostępu do katalogu (HTTP {$clientHttpCode})\n";
} else {
    echo "FAIL: Zablokowany klient uzyskał dostęp! HTTP {$clientHttpCode}\n";
}

// 5. Test API /b2b/toggleclient: odblokowanie
$resToggle2 = httpReq('http://localhost/b2b/toggleclient', [
    'id' => $clientId,
    '_csrf' => $csrfToken
], ['X-Requested-With: XMLHttpRequest', 'Accept: application/json']);
$dataToggle2 = json_decode($resToggle2['body'], true);
echo "3. Wynik toggleclient (odblokowanie): " . json_encode($dataToggle2) . "\n";

// 5b. Test blokady klienta przez formularz edycji (/b2b/updateclient)
$resUpdateStatus = httpReq('http://localhost/b2b/updateclient', [
    'id'               => $clientId,
    'company_name'     => 'Klient Blokada ' . $unique,
    'phone'            => '+48 555 444 333',
    'is_active'        => '0',
    '_csrf'            => $csrfToken
], ['X-Requested-With: XMLHttpRequest', 'Accept: application/json']);

$dataUpdateStatus = json_decode($resUpdateStatus['body'], true);
$updatedActive = (int)($dataUpdateStatus['client']['is_active'] ?? -1);
if ($updatedActive === 0) {
    echo "PASS: Edycja klienta przez updateclient poprawnie zablokowała konto (is_active = 0).\n";
} else {
    echo "FAIL: Edycja klienta nie ustawiła is_active = 0!\n";
    exit(1);
}

// 6. Sprawdzenie skryptu w admin.php:
// - Czy toggleClient nie robi bezmyślnego reloadu do strony głównej
// - Czy zaimplementowane jest zapamiętywanie/przywracanie aktywnej zakładki (localStorage / hash)
$adminSource = file_get_contents(BASE_PATH . '/views/b2b/admin.php');

$hasTabPersistence = (strpos($adminSource, 'b2b_admin_tab') !== false || strpos($adminSource, 'b2b_active_tab') !== false) &&
                     (strpos($adminSource, 'initActiveTab') !== false || strpos($adminSource, 'window.location.hash') !== false);

$toggleClientDoesNotBlindlyReload = !preg_match('/function toggleClient[^{]*\{[^}]*window\.location\.reload\(\)/s', $adminSource);

echo "4. Obsługa zapamiętywania aktywnej zakładki (Klienci/Cennik/Zamówienia): " . ($hasTabPersistence ? "PASS" : "FAIL") . "\n";
echo "5. toggleClient nie przeładowuje całego okna do domyślnej zakładki: " . ($toggleClientDoesNotBlindlyReload ? "PASS" : "FAIL") . "\n";

if (!$hasTabPersistence || !$toggleClientDoesNotBlindlyReload || !isset($dataToggle1['is_active']) || $dataToggle1['is_active'] !== 0) {
    exit(1);
}
echo "=== WSZYSTKIE TESTY ZAKOŃCZONE SUKCESEM ===\n";
