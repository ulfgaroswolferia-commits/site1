<?php
/**
 * Test TDD: Logika okna czasowego (Cut-off Time) i harmonogramu dostaw
 */
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';
require_once BASE_PATH . '/program/script/B2bController.php';

use App\B2bRepository;

echo "=== TEST: Harmonogram dostaw i okno Cut-off Time ===\n";

$repo = new B2bRepository();
$repo->setSetting('cutoff_time', '21:30');
$repo->setSetting('delivery_days', 'mon,tue,wed,thu,fri,sat'); // niedziela wolna

$ctrl = new B2bController();

// 1. Test przed godzinÄ… granicznÄ… (np. godzina 15:00 w Ĺ›rodÄ™ 2026-09-23)
$timeWed1500 = strtotime('2026-09-23 15:00:00'); // Ĺšroda
$schedBefore = $ctrl->getDeliverySchedule($timeWed1500);

echo "1. Przed cut-off: cutoff_passed = false: ";
$beforeOk = ($schedBefore['is_cutoff_passed'] === false);
echo ($beforeOk ? "PASS" : "FAIL") . "\n";

echo "2. Przed cut-off: domyĹ›lna data to jutro (Czwartek 2026-09-24): ";
$firstDateBefore = $schedBefore['options'][0]['date'] ?? '';
$dateTomorrowOk = ($firstDateBefore === '2026-09-24');
echo ($dateTomorrowOk ? "PASS" : "FAIL (got {$firstDateBefore})") . "\n";

// 2. Test po godzinie granicznej (np. godzina 21:45 w Ĺ›rodÄ™ 2026-09-23)
$timeWed2145 = strtotime('2026-09-23 21:45:00'); // Ĺšroda po 21:30
$schedAfter = $ctrl->getDeliverySchedule($timeWed2145);

echo "3. Po cut-off: cutoff_passed = true: ";
$afterOk = ($schedAfter['is_cutoff_passed'] === true);
echo ($afterOk ? "PASS" : "FAIL") . "\n";

echo "4. Po cut-off: domyĹ›lna data to pojutrze (PiÄ…tek 2026-09-25): ";
$firstDateAfter = $schedAfter['options'][0]['date'] ?? '';
$dateDayAfterOk = ($firstDateAfter === '2026-09-25');
echo ($dateDayAfterOk ? "PASS" : "FAIL (got {$firstDateAfter})") . "\n";

// 3. Test ominiÄ™cia niedzieli: sobota rano (2026-09-26 10:00)
// Jutro jest niedziela (brak dostaw) -> najbliĹĽsza dostawa to poniedziaĹ‚ek 2026-09-28!
$timeSat1000 = strtotime('2026-09-26 10:00:00');
$schedSat = $ctrl->getDeliverySchedule($timeSat1000);
$firstDateSat = $schedSat['options'][0]['date'] ?? '';
echo "5. Sobota rano (omija niedzielÄ™): najbliĹĽsza dostawa w PoniedziaĹ‚ek 2026-09-28: ";
$sundaySkippedOk = ($firstDateSat === '2026-09-28');
echo ($sundaySkippedOk ? "PASS" : "FAIL (got {$firstDateSat})") . "\n";

// 4. Test zapisu zamĂłwienia z wybranÄ… datÄ… dostawy przez HTTP
$client = $repo->getClientByLogin('magda') ?: $repo->getAllClients()[0];
$cookieFile = tempnam(sys_get_temp_dir(), 'cook_deliv_');
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);

// Katalog
curl_setopt($ch, CURLOPT_URL, 'http://localhost/b2b?token=' . urlencode($client['auth_token']));
$catHtml = curl_exec($ch);
preg_match('/const CSRF_TOKEN = \'([^\']+)\';/', $catHtml, $mCsrf);
$csrf = $mCsrf[1] ?? '';

$hasDeliveryCards = (strpos($catHtml, 'input-delivery-date') !== false);
echo "6. Widok katalogu posiada kafelki wyboru daty dostawy: " . ($hasDeliveryCards ? "PASS" : "FAIL") . "\n";

// ZĹ‚oĹĽenie zamĂłwienia z jawnÄ… datÄ… dostawy '2026-09-28'
$orderPost = [
    'items' => json_encode([[
        'product_name' => 'Pomidor Malinowy Test Dostawy',
        'price' => 7.00,
        'quantity' => 10,
        'unit' => 'kg',
        'package_size' => 5.0,
        'package_unit' => 'karton',
        'package_summary' => '2 kartony',
        'item_total' => 70.00
    ]]),
    'delivery_date' => '2026-09-28',
    'notes' => 'Test daty dostawy',
    '_csrf' => $csrf
];

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_URL, 'http://localhost/b2b/saveorder');
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($orderPost));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Requested-With: XMLHttpRequest', 'Accept: application/json']);
$respJson = curl_exec($ch);
curl_close($ch);
@unlink($cookieFile);

$respData = json_decode($respJson, true);
$createdId = $respData['order_id'] ?? 0;
$orderDb = $createdId > 0 ? $repo->getOrderById($createdId) : null;
$savedDateOk = ($orderDb && $orderDb['delivery_date'] === '2026-09-28');
echo "7. Zapisana data dostawy w zamĂłwieniu to 2026-09-28: " . ($savedDateOk ? "PASS" : "FAIL") . "\n";

// SprzÄ…tanie
if ($createdId > 0) {
    $repo->getPdo()->exec("DELETE FROM b2b_order_items WHERE order_id = {$createdId}");
    $repo->getPdo()->exec("DELETE FROM b2b_orders WHERE id = {$createdId}");
}

if (!$beforeOk || !$dateTomorrowOk || !$afterOk || !$dateDayAfterOk || !$sundaySkippedOk || !$hasDeliveryCards || !$savedDateOk) {
    echo "=== TEST ZAKOĹCZONY BĹÄDEM (Stan RED) ===\n";
    exit(1);
}

echo "=== TEST ZAKOĹCZONY SUKCESEM (Stan GREEN) ===\n";
