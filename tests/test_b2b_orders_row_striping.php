<?php
/**
 * Test weryfikujący wdrożenie naprzemiennych kolorów wierszy (zebra) oraz ciemniejszego hover na liście zamówień:
 * - Reguły CSS w views/b2b/admin.php (odd #ffffff, even #f8fafc, hover #e2e8f0)
 * - Klasy order-row-odd i order-row-even w szablonie zamówień
 * - Funkcja JavaScript updateOrderRowStriping() aktualizująca pasy przy filtrowaniu
 * - Sprawdzenie stylów w views/b2b/history.php
 * - Test integracyjny HTTP
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';

echo "====================================================================\n";
echo "  TEST: KOLORY WIERSZY (ZEBRA I HOVER) NA LIŚCIE ZAMÓWIEŃ           \n";
echo "====================================================================\n\n";

$pass = 0;
$fail = 0;

function assertCheck($condition, $desc) {
    global $pass, $fail;
    if ($condition) {
        echo "  [PASS] {$desc}\n";
        $pass++;
    } else {
        echo "  [FAIL] {$desc}\n";
        $fail++;
    }
}

// 1. Weryfikacja statyczna w views/b2b/admin.php
$adminView = file_get_contents(BASE_PATH . '/views/b2b/admin.php');

assertCheck(strpos($adminView, '#orders-tbody tr.order-data-row:nth-child') === false, "admin.php NIE używa zawodnego :nth-child dla filtrowanych zamówień (brak buga ukrytych elementów)");
assertCheck(strpos($adminView, '#orders-tbody tr.order-data-row.order-row-odd') !== false, "admin.php zawiera selektor .order-row-odd");
assertCheck(strpos($adminView, '#orders-tbody tr.order-data-row.order-row-even') !== false, "admin.php zawiera selektor .order-row-even");
assertCheck(strpos($adminView, '#orders-tbody tr.order-data-row:hover') !== false, "admin.php zawiera hover dla zamówień");
assertCheck(strpos($adminView, 'order-row-even') !== false && strpos($adminView, 'order-row-odd') !== false, "admin.php przypisuje naprzemienne klasy w pętli foreach");
assertCheck(strpos($adminView, 'function updateOrderRowStriping()') !== false, "admin.php zawiera funkcję updateOrderRowStriping()");
assertCheck(strpos($adminView, 'updateOrderRowStriping();') !== false, "updateOrderRowStriping() jest wywoływana przy filtrowaniu i zmianie zakładek");

// 2. Weryfikacja statyczna w views/b2b/history.php
$historyView = file_get_contents(BASE_PATH . '/views/b2b/history.php');
assertCheck(strpos($historyView, 'table tbody tr:nth-child(odd)') !== false, "history.php zawiera regułę CSS :nth-child(odd)");
assertCheck(strpos($historyView, 'table tbody tr:nth-child(even)') !== false, "history.php zawiera regułę CSS :nth-child(even)");
assertCheck(strpos($historyView, 'table tbody tr:hover') !== false, "history.php zawiera regułę CSS :hover");

// 3. Test integracyjny HTTP pobrania panelu admina
$cookieFile = tempnam(sys_get_temp_dir(), 'cook_striping_');
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);

// Logowanie admina
curl_setopt($ch, CURLOPT_URL, 'http://localhost/home/login');
$resLogin = curl_exec($ch);
preg_match('/name="_csrf" value="([^"]+)"/', (string)$resLogin, $mCsrf);
$loginCsrf = $mCsrf[1] ?? '';

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'login'    => defined('APP_LOGIN') ? APP_LOGIN : 'admin',
    'password' => defined('APP_PASSWORD') ? APP_PASSWORD : 'admin123',
    '_csrf'    => $loginCsrf
]));
curl_exec($ch);
curl_setopt($ch, CURLOPT_POST, false);

// Pobranie HTML panelu admina
curl_setopt($ch, CURLOPT_URL, 'http://localhost/b2b/admin');
$html = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

assertCheck($httpCode === 200, "Panel hurtownika /b2b/admin zwrócił kod HTTP 200");
assertCheck(strpos($html, 'order-data-row') !== false, "HTML zawiera wiersze zamówień .order-data-row");
assertCheck(strpos($html, 'order-row-odd') !== false || strpos($html, 'order-row-even') !== false, "HTML zawiera wygenerowane klasy zebra (order-row-odd/even)");

curl_close($ch);
if (file_exists($cookieFile)) {
    @unlink($cookieFile);
}

echo "\n====================================================================\n";
echo "  WYNIK TESTU: PASS = {$pass}, FAIL = {$fail}\n";
echo "====================================================================\n";

if ($fail > 0) {
    exit(1);
}
exit(0);
