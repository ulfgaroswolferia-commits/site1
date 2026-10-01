<?php
/**
 * Test TDD: Weryfikacja zmiany etykiety na "ZamĂłwienia" oraz licznika tylko nowych zamĂłwieĹ„.
 */
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';
require_once BASE_PATH . '/program/config/includes.php';

use App\B2bRepository;

echo "=== TEST: Etykieta 'ZamĂłwienia' i licznik nowych zamĂłwieĹ„ ===\n";

$repo = new B2bRepository();

// Pobierz wszystkie zamĂłwienia z bazy i policz nowe
$allOrders = $repo->getAllOrders(100);
$newOrdersCount = count(array_filter($allOrders, fn($o) => ($o['status'] ?? '') === 'new'));
$totalOrdersCount = count($allOrders);

echo "1. ZamĂłwienia w bazie: ĹÄ…cznie = {$totalOrdersCount}, Nowe (status 'new') = {$newOrdersCount}\n";

$cookieFile = tempnam(sys_get_temp_dir(), 'cook_orders_tab_');
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

// 1. Sprawdzenie etykiety w przycisku tab-btn-orders
$tabBtnOk = false;
if (preg_match('/<button[^>]*id="tab-btn-orders"[^>]*>(.*?)<\/button>/s', $html, $btnMatches)) {
    $btnContent = $btnMatches[1];
    $hasSplywajace = strpos($btnContent, 'SpĹ‚ywajÄ…ce ZamĂłwienia') !== false;
    $hasZamowienia = strpos($btnContent, 'ZamĂłwienia') !== false;

    if (!$hasSplywajace && $hasZamowienia) {
        echo "2. Etykieta zakĹ‚adki to 'ZamĂłwienia' (usuniÄ™to 'SpĹ‚ywajÄ…ce'): PASS\n";
        $tabBtnOk = true;
    } else {
        echo "FAIL: W przycisku zakĹ‚adki nadal wystÄ™puje 'SpĹ‚ywajÄ…ce ZamĂłwienia' lub brak 'ZamĂłwienia'!\n";
    }
} else {
    echo "FAIL: Nie znaleziono przycisku #tab-btn-orders!\n";
}

// 2. Sprawdzenie wartoĹ›ci znacznika badge-orders-count
$badgeOk = false;
if (preg_match('/<span[^>]*id="badge-orders-count"[^>]*>(.*?)<\/span>/s', $html, $badgeMatches)) {
    $badgeVal = trim(strip_tags($badgeMatches[1]));
    echo "3. WartoĹ›Ä‡ znacznika badge-orders-count: '{$badgeVal}' (oczekiwano: '{$newOrdersCount}')\n";
    if ((int)$badgeVal === (int)$newOrdersCount) {
        echo "   Licznik zawiera TYLKO nowe zamĂłwienia: PASS\n";
        $badgeOk = true;
    } else {
        echo "FAIL: Licznik w badge-orders-count to '{$badgeVal}', a powinno byÄ‡ '{$newOrdersCount}'!\n";
    }
} else {
    echo "FAIL: Nie znaleziono znacznika #badge-orders-count!\n";
}

// 3. Sprawdzenie funkcji odĹ›wieĹĽania licznika w JS
$hasJsRefresh = strpos($html, 'refreshNewOrdersBadge') !== false;
echo "4. Funkcja odĹ›wieĹĽania licznika nowych zamĂłwieĹ„ w JS: " . ($hasJsRefresh ? "PASS" : "FAIL") . "\n";

@unlink($cookieFile);

if (!$tabBtnOk || !$badgeOk || !$hasJsRefresh) {
    echo "=== TESTY ZAKOĹCZONE BĹÄDEM ===\n";
    exit(1);
}

echo "=== WSZYSTKIE TESTY ETYKIETY I LICZNIKA ZAMĂ“WIEĹ ZAKOĹCZONE SUKCESEM ===\n";
