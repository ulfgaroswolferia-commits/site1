<?php
/**
 * Test TDD: Logika okna czasowego (Cut-off Time) i harmonogramu dostaw
 */
define('BASE_PATH', 'C:/laragon\www');
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';
require_once BASE_PATH . '/program/script/B2bController.php';

use App\B2bRepository;

echo "=== TEST: Harmonogram dostaw i okno Cut-off Time ===\n";

$repo = new B2bRepository();
$repo->setSetting('cutoff_time', '21:30');
$repo->setSetting('delivery_days', 'mon,tue,wed,thu,fri,sat'); // niedziela wolna

$ctrl = new B2bController();

// 1. Test przed godziną graniczną (np. godzina 15:00 w środę 2026-09-23)
$timeWed1500 = strtotime('2026-09-23 15:00:00'); // Środa
$schedBefore = $ctrl->getDeliverySchedule($timeWed1500);

echo "1. Przed cut-off: cutoff_passed = false: ";
$beforeOk = ($schedBefore['is_cutoff_passed'] === false);
echo ($beforeOk ? "PASS" : "FAIL") . "\n";

echo "2. Przed cut-off: domyślna data to jutro (Czwartek 2026-09-24): ";
$firstDateBefore = $schedBefore['options'][0]['date'] ?? '';
$dateTomorrowOk = ($firstDateBefore === '2026-09-24');
echo ($dateTomorrowOk ? "PASS" : "FAIL (got {$firstDateBefore})") . "\n";

// 2. Test po godzinie granicznej (np. godzina 21:45 w środę 2026-09-23)
$timeWed2145 = strtotime('2026-09-23 21:45:00'); // Środa po 21:30
$schedAfter = $ctrl->getDeliverySchedule($timeWed2145);

echo "3. Po cut-off: cutoff_passed = true: ";
$afterOk = ($schedAfter['is_cutoff_passed'] === true);
echo ($afterOk ? "PASS" : "FAIL") . "\n";

echo "4. Po cut-off: domyślna data to pojutrze (Piątek 2026-09-25): ";
$firstDateAfter = $schedAfter['options'][0]['date'] ?? '';
$dateDayAfterOk = ($firstDateAfter === '2026-09-25');
echo ($dateDayAfterOk ? "PASS" : "FAIL (got {$firstDateAfter})") . "\n";

// 3. Test ominięcia niedzieli: sobota rano (2026-09-26 10:00)
// Jutro jest niedziela (brak dostaw) -> najbliższa dostawa to poniedziałek 2026-09-28!
$timeSat1000 = strtotime('2026-09-26 10:00:00');
$schedSat = $ctrl->getDeliverySchedule($timeSat1000);
$firstDateSat = $schedSat['options'][0]['date'] ?? '';
echo "5. Sobota rano (omija niedzielę): najbliższa dostawa w Poniedziałek 2026-09-28: ";
$sundaySkippedOk = ($firstDateSat === '2026-09-28');
echo ($sundaySkippedOk ? "PASS" : "FAIL (got {$firstDateSat})") . "\n";

// 4. Test zapisu zamówienia z wybraną datą dostawy przez HTTP
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

// Złożenie zamówienia z jawną datą dostawy '2026-09-28'
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
echo "7. Zapisana data dostawy w zamówieniu to 2026-09-28: " . ($savedDateOk ? "PASS" : "FAIL") . "\n";

// Sprzątanie
if ($createdId > 0) {
    $repo->getPdo()->exec("DELETE FROM b2b_order_items WHERE order_id = {$createdId}");
    $repo->getPdo()->exec("DELETE FROM b2b_orders WHERE id = {$createdId}");
}

if (!$beforeOk || !$dateTomorrowOk || !$afterOk || !$dateDayAfterOk || !$sundaySkippedOk || !$hasDeliveryCards || !$savedDateOk) {
    echo "=== TEST ZAKOŃCZONY BŁĘDEM (Stan RED) ===\n";
    exit(1);
}

echo "=== TEST ZAKOŃCZONY SUKCESEM (Stan GREEN) ===\n";
