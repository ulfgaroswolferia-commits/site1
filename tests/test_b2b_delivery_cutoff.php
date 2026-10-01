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

// Katalog (b2b?token= przekierowuje na adres bez tokenu — PRG, 03c8c0f)
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_URL, 'http://localhost/b2b?token=' . urlencode($client['auth_token']));
$catHtml = curl_exec($ch);
preg_match('/const CSRF_TOKEN = \'([^\']+)\';/', $catHtml, $mCsrf);
$csrf = $mCsrf[1] ?? '';

$hasDeliveryCards = (strpos($catHtml, 'input-delivery-date') !== false);
echo "6. Widok katalogu posiada kafelki wyboru daty dostawy: " . ($hasDeliveryCards ? "PASS" : "FAIL") . "\n";

// Złożenie zamówienia z datą wybraną spośród oferowanych w katalogu (ostatni kafelek, czyli nie domyślna).
// Serwer liczy cenę z katalogu (e7d0f91) — zamawiamy istniejący, dostępny produkt.
preg_match_all('/name="modal_delivery_date" value="(\d{4}-\d{2}-\d{2})"/', (string)$catHtml, $mDates);
$chosenDate = !empty($mDates[1]) ? end($mDates[1]) : '';
$product = $repo->getActiveProducts()[0] ?? null;
$orderPost = [
    'items' => json_encode([[
        'product_id'   => (int)($product['id'] ?? 0),
        'product_name' => $product['name'] ?? '',
        'quantity'     => 10,
        'unit'         => $product['unit'] ?? 'kg',
    ]]),
    'delivery_date' => $chosenDate,
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
$savedDateOk = ($orderDb && $chosenDate !== '' && $orderDb['delivery_date'] === $chosenDate);
echo "7. Zapisana data dostawy w zamówieniu to wybrana {$chosenDate}: " . ($savedDateOk ? "PASS" : "FAIL") . "\n";

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
