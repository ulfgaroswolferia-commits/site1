<?php
/**
 * Test weryfikujący funkcjonalność:
 * "Dodaj produkt spoza cennika" w module Zamawiarka Magdy (/order).
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';
require_once BASE_PATH . '/program/core/Model.php';
require_once BASE_PATH . '/program/lib/Db.php';
require_once BASE_PATH . '/program/model/OrderModel.php';
require_once BASE_PATH . '/program/lib/XlsxWriter.php';
require_once BASE_PATH . '/program/lib/Mailer.php';

echo "====================================================================\n";
echo "  TEST: PRODUKTY SPOZA CENNIKA W ZAMAWIARCE MAGDY (/order)         \n";
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

// -------------------------------------------------------------------------
// 1. Sprawdzenie migracji bazy SQLite i kolumny is_custom w order_items
// -------------------------------------------------------------------------
$model = new \App\OrderModel();
$pdo = \App\OrderModel::initDatabase();
$cols = $pdo->query("PRAGMA table_info(order_items)")->fetchAll();
$colNames = array_column($cols, 'name');

assertCheck(in_array('is_custom', $colNames), "Kolumna 'is_custom' istnieje w tabeli order_items");

// -------------------------------------------------------------------------
// 2. Tworzenie zamówienia mieszanego (pozycje z cennika + spoza cennika)
// -------------------------------------------------------------------------
$orderNum = $model->generateOrderNumber();
$items = [
    [
        'name'      => 'Pomidory malinowe',
        'is_custom' => 0,
        'price'     => 12.50,
        'quantity'  => 5.0,
        'unit'      => 'kg',
    ],
    [
        'name'      => 'Ogórki szklarniowe',
        'is_custom' => 0,
        'price'     => 6.00,
        'quantity'  => 10.0,
        'unit'      => 'kg',
    ],
    [
        'name'      => 'Koper włoski świeży',
        'is_custom' => 1,
        'price'     => 0.00,
        'quantity'  => 2.5,
        'unit'      => 'kg',
    ],
    [
        'name'      => 'Rzodkiew biała długa',
        'is_custom' => 1,
        'price'     => 0.00,
        'quantity'  => 3.0,
        'unit'      => 'pęczek',
    ],
    [
        'name'      => 'Ziemniaki młode',
        'is_custom' => 0,
        'price'     => 3.00,
        'quantity'  => 0.0, // nie zamówione
        'unit'      => 'kg',
    ]
];

$orderId = $model->createOrder([
    'order_number'      => $orderNum,
    'supplier_name'     => 'Hurtownia Zieleniak Test',
    'original_filename' => 'cennik_testowy.xlsx',
    'export_filename'   => 'zamowienie_test_custom.xlsx',
], $items);

assertCheck($orderId > 0, "Utworzono zamówienie mieszane (ID: {$orderId})");

$order = $model->getOrderById($orderId);
assertCheck($order !== null, "Odczytano zamówienie po ID");
assertCheck((int)$order['total_items'] === 4, "Liczba zamówionych pozycji w nagłówku to 4 (2 cennik + 2 custom)");

// Suma: (5 * 12.50) + (10 * 6.00) = 62.50 + 60.00 = 122.50 zł
$expectedTotal = 122.50;
assertCheck(abs((float)$order['total_amount'] - $expectedTotal) < 0.01, "Łączna kwota zamówienia to {$expectedTotal} zł (pozycje custom nie zawyżają kwoty)");

$savedItems = $model->getOrderItems($orderId);
assertCheck(count($savedItems) === 4, "W tabeli order_items zapisano dokładnie 4 pozycje");

$customCount = 0;
$regularCount = 0;
foreach ($savedItems as $it) {
    if (!empty($it['is_custom'])) {
        $customCount++;
        assertCheck((float)$it['unit_price'] === 0.0, "Pozycja '{$it['product_name']}' spoza cennika ma unit_price = 0.00");
        assertCheck((float)$it['item_total'] === 0.0, "Pozycja '{$it['product_name']}' spoza cennika ma item_total = 0.00");
    } else {
        $regularCount++;
    }
}
assertCheck($customCount === 2, "Zapisano dokładnie 2 pozycje spoza cennika");
assertCheck($regularCount === 2, "Zapisano dokładnie 2 pozycje standardowe z cennika");

// -------------------------------------------------------------------------
// 3. Tworzenie zamówienia wyłącznie z pozycji spoza cennika
// -------------------------------------------------------------------------
$orderNumOnlyCustom = $model->generateOrderNumber() . '-CUST';
$onlyCustomItems = [
    [
        'name'      => 'Kurki świeże leśne',
        'is_custom' => 1,
        'price'     => 0.00,
        'quantity'  => 4.0,
        'unit'      => 'kg',
    ]
];

$orderIdOnlyCustom = $model->createOrder([
    'order_number'      => $orderNumOnlyCustom,
    'supplier_name'     => 'Dostawca Grzybów',
    'original_filename' => 'brak.xlsx',
    'export_filename'   => 'zamowienie_grzyby.xlsx',
], $onlyCustomItems);

$orderOnlyCustom = $model->getOrderById($orderIdOnlyCustom);
assertCheck((int)$orderOnlyCustom['total_items'] === 1, "Zamówienie z samymi pozycjami custom ma total_items = 1");
assertCheck((float)$orderOnlyCustom['total_amount'] === 0.0, "Zamówienie z samymi pozycjami custom ma total_amount = 0.00 zł");

// -------------------------------------------------------------------------
// 4. Test generowania Excel (.xlsx) z pozycjami spoza cennika
// -------------------------------------------------------------------------
$tmpXlsx = BASE_PATH . '/tmp/test_custom_order_export.xlsx';
if (file_exists($tmpXlsx)) @unlink($tmpXlsx);

\XlsxWriter::saveToFile($tmpXlsx, $savedItems, [
    'order_number'  => $orderNum,
    'supplier_name' => 'Hurtownia Zieleniak Test',
    'created_at'    => date('Y-m-d H:i'),
]);

assertCheck(file_exists($tmpXlsx), "Wygenerowano poprawnie plik XLSX zamówienia");

// Otwarcie pliku zip/xlsx i sprawdzenie zawartości sheet1.xml
$zip = new \ZipArchive();
$openRes = $zip->open($tmpXlsx);
assertCheck($openRes === true, "Plik XLSX jest poprawnym archiwum ZIP");

$sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
assertCheck(strpos($sheetXml, '[SPOZA CENNIKA] Koper włoski świeży') !== false, "W arkuszu Excel pozycja custom ma prefiks [SPOZA CENNIKA]");
assertCheck(strpos($sheetXml, '[SPOZA CENNIKA] Rzodkiew biała długa') !== false, "W arkuszu Excel druga pozycja custom ma prefiks [SPOZA CENNIKA]");
$zip->close();
@unlink($tmpXlsx);

// -------------------------------------------------------------------------
// 5. Test szablonu Mailer dla pozycji spoza cennika
// -------------------------------------------------------------------------
$emailHtml = \Mailer::buildOrderEmailHtml($order, $savedItems);
assertCheck(strpos($emailHtml, 'Spoza cennika') !== false || strpos($emailHtml, 'SPOZA CENNIKA') !== false, "Szablon e-mail zawiera wyróżnienie dla pozycji spoza cennika");
assertCheck(strpos($emailHtml, 'Do wyceny') !== false, "Szablon e-mail zawiera informację 'Do wyceny' dla pozycji custom");

// -------------------------------------------------------------------------
// 6. Test obecności elementów interfejsu w views/order/index.php
// -------------------------------------------------------------------------
$indexViewContent = file_get_contents(BASE_PATH . '/views/order/index.php');
assertCheck(strpos($indexViewContent, 'id="custom-product-section"') !== false, "Widok index.php zawiera sekcję #custom-product-section");
assertCheck(strpos($indexViewContent, 'Dodaj produkt spoza cennika') !== false, "Widok index.php zawiera nagłówek 'Dodaj produkt spoza cennika'");
assertCheck(strpos($indexViewContent, 'Zamówienie produktów spoza cennika nie gwarantuje ich dostawy') !== false, "Widok index.php zawiera zastrzeżenie o braku gwarancji dostawy");
assertCheck(strpos($indexViewContent, 'id="custom-prod-name"') !== false, "Widok index.php zawiera pole nazwy #custom-prod-name");
assertCheck(strpos($indexViewContent, 'id="custom-prod-qty"') !== false, "Widok index.php zawiera pole ilości #custom-prod-qty");
assertCheck(strpos($indexViewContent, 'id="custom-prod-unit"') !== false, "Widok index.php zawiera wybór jednostki #custom-prod-unit");
assertCheck(strpos($indexViewContent, 'id="btn-add-custom-product"') !== false, "Widok index.php zawiera przycisk dodawania pozycji");
assertCheck(strpos($indexViewContent, 'id="custom-products-container"') !== false, "Widok index.php zawiera kontener draftu pozycji custom");
assertCheck(strpos($indexViewContent, 'id="custom-items-table-body"') !== false, "Widok index.php zawiera ciało tabeli draftu pozycji custom");
assertCheck(strpos($indexViewContent, 'addCustomProduct') !== false, "JavaScript w index.php implementuje funkcję addCustomProduct()");
assertCheck(strpos($indexViewContent, 'removeCustomProduct') !== false, "JavaScript w index.php implementuje funkcję removeCustomProduct()");
assertCheck(strpos($indexViewContent, 'renderCustomItemsTable') !== false, "JavaScript w index.php implementuje funkcję renderCustomItemsTable()");
assertCheck(strpos($indexViewContent, 'customItems') !== false, "JavaScript w index.php zarządza tablicą customItems");

// -------------------------------------------------------------------------
// 7. Test obecności elementów w widoku szczegółów views/order/view.php
// -------------------------------------------------------------------------
$detailViewContent = file_get_contents(BASE_PATH . '/views/order/view.php');
assertCheck(strpos($detailViewContent, 'badge-custom') !== false, "Widok view.php zawiera klasę badge-custom dla pozycji spoza cennika");
assertCheck(strpos($detailViewContent, 'Spoza cennika') !== false, "Widok view.php zawiera etykietę 'Spoza cennika'");
assertCheck(strpos($detailViewContent, 'Do wyceny') !== false, "Widok view.php zawiera oznaczenie ceny 'Do wyceny'");
assertCheck(strpos($detailViewContent, 'tr-custom') !== false, "Widok view.php zawiera wyróżnienie wiersza tr-custom");

// -------------------------------------------------------------------------
// 8. Test HTTP endpointów /order/save oraz /order/loadhistory
// -------------------------------------------------------------------------
$cookieFile = tempnam(sys_get_temp_dir(), 'cook_test_order_');
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);

// Logowanie
$adminLogin = defined('APP_LOGIN') ? APP_LOGIN : 'admin';
$adminPass  = (getenv('APP_TEST_PASSWORD') ?: 'admin123');

curl_setopt($ch, CURLOPT_URL, 'http://localhost/home/login');
$bodyLoginGet = curl_exec($ch);
$loginCsrf = '';
if ($bodyLoginGet !== false) {
    preg_match('/name="(?:csrf_token|_csrf)" value="([^"]+)"/', (string)$bodyLoginGet, $mLogin);
    $loginCsrf = $mLogin[1] ?? '';

    curl_setopt($ch, CURLOPT_URL, 'http://localhost/home/login');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'login'      => $adminLogin,
        'password'   => $adminPass,
        'csrf_token' => $loginCsrf,
    ]));
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_exec($ch);
}

// Pobranie tokenu CSRF z /order/index
$orderCsrf = '';
if ($loginCsrf !== '') {
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/order/index');
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    $bodyOrderIndex = curl_exec($ch);
    if ($bodyOrderIndex !== false) {
        preg_match('/const CSRF_TOKEN = \'([^\']+)\'/', (string)$bodyOrderIndex, $mOrderCsrf);
        $orderCsrf = $mOrderCsrf[1] ?? '';
    }
}

if ($orderCsrf === '') {
    echo "  [SKIP] Pominięto test HTTP (serwer lokalny nie odpowiedział CSRF / brak aktywnego serwera www)\n";
} else {

// Zapis zamówienia z pozycją custom przez POST /order/save
$httpOrderItems = [
    [
        'name'      => 'Pieczarki brunatne',
        'is_custom' => 0,
        'price'     => 10.00,
        'quantity'  => 3.0,
        'unit'      => 'kg'
    ],
    [
        'name'      => 'Topinambur świeży',
        'is_custom' => 1,
        'price'     => 0.00,
        'quantity'  => 5.0,
        'unit'      => 'kg'
    ]
];

curl_setopt($ch, CURLOPT_URL, 'http://localhost/order/save');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Requested-With: XMLHttpRequest', 'Accept: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'supplier_name'     => 'Hurtownia E2E Test',
    'original_filename' => 'cennik_e2e.xlsx',
    'items'             => json_encode($httpOrderItems),
    'csrf_token'        => $orderCsrf,
]);
$resSaveHttp = curl_exec($ch);
$saveJson = json_decode($resSaveHttp, true);

assertCheck(!empty($saveJson['ok']), "POST /order/save z pozycją spoza cennika zwrócił ok = true");
$httpOrderId = (int)($saveJson['order_id'] ?? 0);
assertCheck($httpOrderId > 0, "Utworzono zamówienie HTTP o ID: {$httpOrderId}");

// Weryfikacja bazy danych dla zamówienia HTTP
$savedOrderItems = $model->getOrderItems($httpOrderId);
assertCheck(count($savedOrderItems) === 2, "Zapisano 2 pozycje w bazie dla zamówienia HTTP");
$topinambur = null;
foreach ($savedOrderItems as $soi) {
    if ($soi['product_name'] === 'Topinambur świeży') {
        $topinambur = $soi;
        break;
    }
}
assertCheck($topinambur !== null && (int)$topinambur['is_custom'] === 1, "Pozycja 'Topinambur świeży' ma is_custom = 1 w bazie");
assertCheck($topinambur !== null && (float)$topinambur['unit_price'] === 0.0, "Pozycja 'Topinambur świeży' ma unit_price = 0.00 w bazie");

// Test POST /order/loadhistory
curl_setopt($ch, CURLOPT_URL, 'http://localhost/order/loadhistory');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Requested-With: XMLHttpRequest', 'Accept: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'order_id'   => $httpOrderId,
    'csrf_token' => $orderCsrf,
]);
$resLoadHttp = curl_exec($ch);
$loadJson = json_decode($resLoadHttp, true);

assertCheck(!empty($loadJson['ok']), "POST /order/loadhistory zwrócił ok = true");
assertCheck(!empty($loadJson['custom_products']) && count($loadJson['custom_products']) === 1, "loadhistory zwraca tablicę custom_products z 1 pozycją");
assertCheck(($loadJson['custom_products'][0]['name'] ?? '') === 'Topinambur świeży', "Wczytana pozycja custom ma nazwę 'Topinambur świeży'");
assertCheck(($loadJson['custom_products'][0]['is_custom'] ?? 0) === 1, "Wczytana pozycja custom ma is_custom = 1");

}

curl_close($ch);
@unlink($cookieFile);

// Podsumowanie
echo "\n====================================================================\n";
echo "WYNIK TESTÓW: {$pass} zaliczonych, {$fail} niezaliczonych.\n";
echo "====================================================================\n";

if ($fail > 0) {
    exit(1);
}
