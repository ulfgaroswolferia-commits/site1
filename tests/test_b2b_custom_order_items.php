<?php
/**
 * Test weryfikujący funkcjonalność:
 * "Dodaj produkt spoza cennika" w portalu klienta B2B
 * Zastrzeżenie: "Zamówienie produktów spoza cennika nie gwarantuje ich dostawy"
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';
require_once BASE_PATH . '/program/lib/ErpImporter.php';
require_once BASE_PATH . '/program/lib/ErpExporter.php';
require_once BASE_PATH . '/program/lib/XlsxWriter.php';
require_once BASE_PATH . '/program/lib/Tools.php';
require_once BASE_PATH . '/program/core/App.php';
require_once BASE_PATH . '/program/core/Controller.php';
require_once BASE_PATH . '/program/script/AppController.php';
require_once BASE_PATH . '/program/script/B2bController.php';

use App\B2bRepository;

echo "====================================================================\n";
echo "  TEST: DODAWANIE PRODUKTÓW SPOZA CENNIKA W B2B                   \n";
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

$repo = new B2bRepository();

// -------------------------------------------------------------------------
// 1. Weryfikacja bazy danych i kolumny is_custom w b2b_order_items
// -------------------------------------------------------------------------
$pdo = $repo->getPdo();
$cols = $pdo->query("PRAGMA table_info(b2b_order_items)")->fetchAll();
$colNames = array_column($cols, 'name');
assertCheck(in_array('is_custom', $colNames), "Kolumna 'is_custom' istnieje w tabeli b2b_order_items");

// -------------------------------------------------------------------------
// 2. Test walidacji prepareOrderItems z produktami spoza cennika
// -------------------------------------------------------------------------

// A. Pusta nazwa produktu spoza cennika
$threw = false;
try {
    $repo->prepareOrderItems([
        ['is_custom' => 1, 'product_name' => '', 'quantity' => 5, 'unit' => 'kg']
    ]);
} catch (\InvalidArgumentException $e) {
    $threw = true;
}
assertCheck($threw, "prepareOrderItems odrzuca pozycję spoza cennika z pustą nazwą");

// B. Błędna ilość (<= 0)
$preparedZero = $repo->prepareOrderItems([
    ['is_custom' => 1, 'product_name' => 'Koper włoski', 'quantity' => 0, 'unit' => 'kg']
]);
assertCheck(count($preparedZero) === 0, "prepareOrderItems pomija pozycję spoza cennika z ilością 0");

// C. Poprawna pozycja spoza cennika
$customItem = [
    'is_custom'       => 1,
    'product_name'    => 'Koper włoski świeży',
    'quantity'        => 3.5,
    'unit'            => 'kg',
    'package_summary' => 'Produkt spoza cennika (do potwierdzenia)'
];
$prepRes = $repo->prepareOrderItems([$customItem]);
assertCheck(count($prepRes) === 1, "prepareOrderItems przetwarza poprawnie produkt spoza cennika");
assertCheck($prepRes[0]['is_custom'] === 1, "Przygotowana pozycja ma is_custom === 1");
assertCheck($prepRes[0]['product_id'] === null, "Przygotowana pozycja spoza cennika ma product_id === null");
assertCheck($prepRes[0]['price'] === 0.0, "Przygotowana pozycja spoza cennika ma cenę 0.00 zł");
assertCheck($prepRes[0]['item_total'] === 0.0, "Przygotowana pozycja spoza cennika ma item_total 0.00 zł");
assertCheck($prepRes[0]['quantity'] === 3.5, "Przygotowana pozycja ma poprawną ilość 3.5");
assertCheck($prepRes[0]['unit'] === 'kg', "Przygotowana pozycja ma jednostkę 'kg'");

// D. Koszyk mieszany: produkt z cennika + produkt spoza cennika
$products = $repo->getActiveProducts();
if (!empty($products)) {
    $catalogProd = $products[0];
    $mixedBasket = [
        [
            'product_id' => (int)$catalogProd['id'],
            'quantity'   => 10,
        ],
        [
            'is_custom'    => 1,
            'product_name' => 'Awokado Hass dojrzewające',
            'quantity'     => 12,
            'unit'         => 'szt.'
        ]
    ];
    $prepMixed = $repo->prepareOrderItems($mixedBasket);
    assertCheck(count($prepMixed) === 2, "prepareOrderItems obsługuje koszyk mieszany (cennik + spoza cennika)");
    assertCheck($prepMixed[0]['is_custom'] === 0, "Pierwsza pozycja koszyka ma is_custom = 0");
    assertCheck($prepMixed[0]['price'] > 0, "Pierwsza pozycja ma cenę katalogową > 0");
    assertCheck($prepMixed[1]['is_custom'] === 1, "Druga pozycja koszyka ma is_custom = 1");
    assertCheck($prepMixed[1]['price'] === 0.0, "Druga pozycja ma cenę 0.00 (do ustalenia)");
}

// -------------------------------------------------------------------------
// 3. Test zapisu zamówienia do bazy z pozycjami spoza cennika
// -------------------------------------------------------------------------
$client = $repo->getClientByLogin('magda');
if (!$client) {
    $all = $repo->getAllClients();
    $client = $all[0] ?? null;
}
assertCheck($client !== null, "Znaleziono klienta do testu zapisu zamówienia");

$testOrderNumber = 'TEST/CUSTOM/' . date('Ymd_His');
$orderItemsToSave = $prepMixed ?? $prepRes;
$orderId = $repo->createOrder([
    'order_number'              => $testOrderNumber,
    'client_id'                 => (int)$client['id'],
    'client_name_snapshot'      => $client['company_name'],
    'client_phone_snapshot'     => $client['phone'] ?? '',
    'delivery_address_snapshot' => $client['delivery_address'] ?? '',
    'delivery_date'             => date('Y-m-d', strtotime('+1 day')),
    'status'                    => 'new',
    'total_amount'              => array_sum(array_column($orderItemsToSave, 'item_total')),
    'notes'                     => 'Test pozycji spoza cennika',
], $orderItemsToSave);

assertCheck($orderId > 0, "createOrder pomyślnie utworzyło zamówienie (ID: {$orderId})");

$savedItems = $repo->getOrderItems($orderId);
assertCheck(count($savedItems) === count($orderItemsToSave), "Zapisano poprawną liczbę pozycji w b2b_order_items");

$customSaved = array_values(array_filter($savedItems, fn($it) => (int)$it['is_custom'] === 1));
assertCheck(count($customSaved) >= 1, "Przynajmniej jedna pozycja w bazie ma is_custom = 1");
assertCheck($customSaved[0]['product_id'] === null || (int)$customSaved[0]['product_id'] === 0, "Pozycja spoza cennika nie ma product_id");
assertCheck(!empty($customSaved[0]['product_name']), "Pozycja spoza cennika ma zapisaną nazwę: " . $customSaved[0]['product_name']);

// -------------------------------------------------------------------------
// 4. Test generowania karty kompletacji Excel z pozycją [SPOZA CENNIKA]
// -------------------------------------------------------------------------
$xlsxContent = XlsxWriter::createPackingSheetWorkbook($savedItems, [
    'order_number' => $testOrderNumber,
    'client_name'  => $client['company_name'],
]);
$tmpXlsx = tempnam(sys_get_temp_dir(), 'test_pack_');
file_put_contents($tmpXlsx, $xlsxContent);
$zip = new ZipArchive();
$hasSpoza = false;
if ($zip->open($tmpXlsx) === true) {
    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $hasSpoza = (strpos($sheetXml, 'SPOZA CENNIKA') !== false);
    $zip->close();
}
@unlink($tmpXlsx);
assertCheck(strlen($xlsxContent) > 1000, "XlsxWriter wygenerował poprawny plik Excel (.xlsx)");
assertCheck($hasSpoza, "Wygenerowany arkusz Excel zawiera oznaczenie [SPOZA CENNIKA]");

// -------------------------------------------------------------------------
// 5. Test widoku katalogu B2B (catalog.php)
// -------------------------------------------------------------------------
$catalogPath = BASE_PATH . '/views/b2b/catalog.php';
$catalogContent = file_get_contents($catalogPath);

assertCheck(strpos($catalogContent, 'id="customProductSection"') !== false, "Widok zawiera sekcję #customProductSection");
assertCheck(strpos($catalogContent, 'Dodaj produkt spoza cennika') !== false, "Widok zawiera nagłówek 'Dodaj produkt spoza cennika'");
assertCheck(strpos($catalogContent, 'Zamówienie produktów spoza cennika nie gwarantuje ich dostawy') !== false, "Widok zawiera dokładne zastrzeżenie: 'Zamówienie produktów spoza cennika nie gwarantuje ich dostawy'");
assertCheck(strpos($catalogContent, 'id="customProdName"') !== false, "Widok zawiera pole nazwy #customProdName");
assertCheck(strpos($catalogContent, 'id="customProdQty"') !== false, "Widok zawiera pole ilości #customProdQty");
assertCheck(strpos($catalogContent, 'id="customProdUnit"') !== false, "Widok zawiera wybór jednostki #customProdUnit");
assertCheck(strpos($catalogContent, 'id="btnAddCustomProduct"') !== false, "Widok zawiera przycisk #btnAddCustomProduct");
assertCheck(strpos($catalogContent, 'id="customProductsContainer"') !== false, "Widok zawiera kontener draftu #customProductsContainer");
assertCheck(strpos($catalogContent, 'id="customItemsTableBody"') !== false, "Widok zawiera tabelę draftu #customItemsTableBody");
assertCheck(strpos($catalogContent, 'id="modalOffCatalogNotice"') !== false, "Modal zawiera powiadomienie #modalOffCatalogNotice");
assertCheck(strpos($catalogContent, 'customItems.push') !== false, "JavaScript obsługuje tablicę customItems");
assertCheck(strpos($catalogContent, 'is_custom: 1') !== false, "JavaScript przekazuje is_custom: 1 w pozycjach zamówienia");

// -------------------------------------------------------------------------
// 6. Test widoku panelu admina (admin.php) i historii (history.php)
// -------------------------------------------------------------------------
$adminContent = file_get_contents(BASE_PATH . '/views/b2b/admin.php');
assertCheck(strpos($adminContent, 'Spoza cennika') !== false, "Admin panel wyróżnia pozycje spoza cennika w modalu");
assertCheck(strpos($adminContent, '[SPOZA CENNIKA]') !== false, "Admin panel oznacza pozycje [SPOZA CENNIKA] na wydruku specyfikacji");

$historyContent = file_get_contents(BASE_PATH . '/views/b2b/history.php');
assertCheck(strpos($historyContent, 'Spoza cennika') !== false, "Historia zamówień klienta wyróżnia pozycje spoza cennika");

// -------------------------------------------------------------------------
// 7. Test HTTP: Złożenie zamówienia z pozycją spoza cennika przez POST /b2b/saveorder
// -------------------------------------------------------------------------
$cookieFile = tempnam(sys_get_temp_dir(), 'cook_custom_order_');
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);

// Autoryzacja przez token klienta
curl_setopt($ch, CURLOPT_URL, 'http://localhost/b2b?token=' . urlencode($client['auth_token']));
$catalogHtml = curl_exec($ch);
preg_match('/const CSRF_TOKEN = \'([^\']+)\';/', $catalogHtml, $mCsrf);
$csrfToken = $mCsrf[1] ?? '';

if (!empty($csrfToken)) {
    $httpOrderItems = [
        [
            'is_custom'    => 1,
            'product_name' => 'Boczniaki świeże na zapytanie',
            'quantity'     => 4.0,
            'unit'         => 'kg'
        ]
    ];

    curl_setopt($ch, CURLOPT_URL, 'http://localhost/b2b/saveorder');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'items'  => json_encode($httpOrderItems),
        'notes'  => 'Test HTTP pozycji spoza cennika',
        '_csrf'  => $csrfToken
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-Requested-With: XMLHttpRequest',
        'Accept: application/json'
    ]);

    $respJson = curl_exec($ch);
    $resp = json_decode($respJson, true);

    assertCheck($resp && !empty($resp['ok']), "POST /b2b/saveorder z pozycją spoza cennika zwrócił ok=true");
    if ($resp && !empty($resp['order_id'])) {
        $savedHttpItems = $repo->getOrderItems((int)$resp['order_id']);
        assertCheck(count($savedHttpItems) === 1, "Utworzono dokładnie 1 pozycję w zamówieniu HTTP");
        assertCheck((int)$savedHttpItems[0]['is_custom'] === 1, "Zapisana przez HTTP pozycja ma is_custom = 1");
        assertCheck($savedHttpItems[0]['product_name'] === 'Boczniaki świeże na zapytanie', "Zapisano poprawną nazwę pozycji");
    }
} else {
    echo "  [SKIP] Pominięto test HTTP (serwer lokalny nie odpowiedział CSRF)\n";
}

curl_close($ch);
@unlink($cookieFile);

echo "\n====================================================================\n";
echo "  WYNIK TESTU: PASS = {$pass}, FAIL = {$fail}\n";
echo "====================================================================\n";

if ($fail > 0) {
    exit(1);
}
exit(0);
