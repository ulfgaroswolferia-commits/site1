<?php
/**
 * Test TDD: Weryfikacja zmiany etykiety na "Zamówienia" oraz licznika tylko nowych zamówień.
 */
define('BASE_PATH', 'C:/laragon/www');
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';
require_once BASE_PATH . '/program/config/includes.php';

use App\B2bRepository;

echo "=== TEST: Etykieta 'Zamówienia' i licznik nowych zamówień ===\n";

$repo = new B2bRepository();

// Pobierz wszystkie zamówienia z bazy i policz nowe
$allOrders = $repo->getAllOrders(100);
$newOrdersCount = count(array_filter($allOrders, fn($o) => ($o['status'] ?? '') === 'new'));
$totalOrdersCount = count($allOrders);

echo "1. Zamówienia w bazie: Łącznie = {$totalOrdersCount}, Nowe (status 'new') = {$newOrdersCount}\n";

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
    $hasSplywajace = strpos($btnContent, 'Spływające Zamówienia') !== false;
    $hasZamowienia = strpos($btnContent, 'Zamówienia') !== false;

    if (!$hasSplywajace && $hasZamowienia) {
        echo "2. Etykieta zakładki to 'Zamówienia' (usunięto 'Spływające'): PASS\n";
        $tabBtnOk = true;
    } else {
        echo "FAIL: W przycisku zakładki nadal występuje 'Spływające Zamówienia' lub brak 'Zamówienia'!\n";
    }
} else {
    echo "FAIL: Nie znaleziono przycisku #tab-btn-orders!\n";
}

// 2. Sprawdzenie wartości znacznika badge-orders-count
$badgeOk = false;
if (preg_match('/<span[^>]*id="badge-orders-count"[^>]*>(.*?)<\/span>/s', $html, $badgeMatches)) {
    $badgeVal = trim(strip_tags($badgeMatches[1]));
    echo "3. Wartość znacznika badge-orders-count: '{$badgeVal}' (oczekiwano: '{$newOrdersCount}')\n";
    if ((int)$badgeVal === (int)$newOrdersCount) {
        echo "   Licznik zawiera TYLKO nowe zamówienia: PASS\n";
        $badgeOk = true;
    } else {
        echo "FAIL: Licznik w badge-orders-count to '{$badgeVal}', a powinno być '{$newOrdersCount}'!\n";
    }
} else {
    echo "FAIL: Nie znaleziono znacznika #badge-orders-count!\n";
}

// 3. Sprawdzenie funkcji odświeżania licznika w JS
$hasJsRefresh = strpos($html, 'refreshNewOrdersBadge') !== false;
echo "4. Funkcja odświeżania licznika nowych zamówień w JS: " . ($hasJsRefresh ? "PASS" : "FAIL") . "\n";

@unlink($cookieFile);

if (!$tabBtnOk || !$badgeOk || !$hasJsRefresh) {
    echo "=== TESTY ZAKOŃCZONE BŁĘDEM ===\n";
    exit(1);
}

echo "=== WSZYSTKIE TESTY ETYKIETY I LICZNIKA ZAMÓWIEŃ ZAKOŃCZONE SUKCESEM ===\n";
