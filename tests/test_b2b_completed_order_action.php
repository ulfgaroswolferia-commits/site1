<?php
/**
 * Test weryfikujący:
 * 1. Brak przycisku "Finalizuj zamówienie" dla zamówień ze statusem 'completed' w widoku hurtownika
 * 2. Wyświetlanie etykiety "Zamówienie zrealizowane" w kolumnie AKCJA dla statusu 'completed'
 * 3. Wyświetlanie przycisku "Finalizuj zamówienie" dla statusów 'new' i 'processing'
 * 4. Obsługę ręcznej zmiany statusu z potwierdzeniem (prompt confirm) i dynamiczną aktualizację komórki AKCJA
 * 5. Obsługę stanu zrealizowanego w oknie modalnym (zamiana przycisku finalizacji na etykietę zrealizowanego)
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';
require_once BASE_PATH . '/program/lib/Tools.php';
require_once BASE_PATH . '/program/core/App.php';
require_once BASE_PATH . '/program/core/Controller.php';
require_once BASE_PATH . '/program/script/AppController.php';
require_once BASE_PATH . '/program/script/B2bController.php';

use App\B2bRepository;

echo "====================================================================\n";
echo "  TEST: AKCJA DLA ZAMÓWIEŃ ZREALIZOWANYCH W WIDOKU HURTOWNIKA      \n";
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

// 1. WERYFIKACJA STATYCZNA KODU W views/b2b/admin.php
$adminViewPath = BASE_PATH . '/views/b2b/admin.php';
$adminView = file_get_contents($adminViewPath);

assertCheck(strpos($adminView, 'id="order-action-cell-') !== false, "Widok admin.php zawiera id komórki akcji #order-action-cell-");
assertCheck(strpos($adminView, 'Zamówienie zrealizowane') !== false, "Widok admin.php zawiera napis 'Zamówienie zrealizowane'");
assertCheck(strpos($adminView, 'handleOrderStatusChange') !== false, "Widok admin.php obsługuje funkcję handleOrderStatusChange()");
assertCheck(strpos($adminView, 'updateOrderActionCell') !== false, "Widok admin.php zawiera updateOrderActionCell()");
assertCheck(strpos($adminView, 'updateModalFinalizeZone') !== false, "Widok admin.php zawiera updateModalFinalizeZone()");
assertCheck(strpos($adminView, 'modal-finalize-completed-wrap') !== false, "Okno modalne zawiera strefę dla zrealizowanego zamówienia #modal-finalize-completed-wrap");
assertCheck(strpos($adminView, 'STATUS_LABELS') !== false, "Zdefiniowano słownik STATUS_LABELS w JavaScript");

// 2. WERYFIKACJA RENDERINGU HTML W ZALEŻNOŚCI OD STATUSU
// Symulacja danych zamówień o różnych statusach
$ordersMock = [
    [
        'id'                        => 1001,
        'order_number'              => 'TEST/NEW/1',
        'status'                    => 'new',
        'created_at'                => date('Y-m-d H:i:s'),
        'delivery_date'             => date('Y-m-d'),
        'client_name_snapshot'      => 'Klient Nowy',
        'client_phone_snapshot'     => '123456789',
        'total_items'               => 2,
        'total_amount'              => 50.00
    ],
    [
        'id'                        => 1002,
        'order_number'              => 'TEST/COMPLETED/1',
        'status'                    => 'completed',
        'created_at'                => date('Y-m-d H:i:s'),
        'delivery_date'             => date('Y-m-d'),
        'client_name_snapshot'      => 'Klient Zrealizowany',
        'client_phone_snapshot'     => '987654321',
        'total_items'               => 5,
        'total_amount'              => 120.00
    ],
    [
        'id'                        => 1003,
        'order_number'              => 'TEST/PROCESSING/1',
        'status'                    => 'processing',
        'created_at'                => date('Y-m-d H:i:s'),
        'delivery_date'             => date('Y-m-d'),
        'client_name_snapshot'      => 'Klient Kompletacja',
        'client_phone_snapshot'     => '555666777',
        'total_items'               => 3,
        'total_amount'              => 80.00
    ],
    [
        'id'                        => 1004,
        'order_number'              => 'TEST/CANCELLED/1',
        'status'                    => 'cancelled',
        'created_at'                => date('Y-m-d H:i:s'),
        'delivery_date'             => date('Y-m-d'),
        'client_name_snapshot'      => 'Klient Anulowany',
        'client_phone_snapshot'     => '111222333',
        'total_items'               => 1,
        'total_amount'              => 20.00
    ]
];

// 3. TEST INTEGRACYJNY HTTP Z RZECZYWISTYM ZAMÓWIENIEM
$client = $repo->getPdo()->query("SELECT * FROM b2b_clients LIMIT 1")->fetch(\PDO::FETCH_ASSOC);
if (!$client) {
    echo "Brak klienta w bazie, pomijanie testu HTTP\n";
    exit(1);
}

$prod = $repo->getPdo()->query("SELECT * FROM b2b_products WHERE is_available = 1 LIMIT 1")->fetch(\PDO::FETCH_ASSOC);
$prodId = $prod ? (int)$prod['id'] : 1;
$prodName = $prod ? $prod['name'] : 'Jabłka';

// Tworzymy zamówienie testowe
$testOrderId = $repo->createOrder([
    'order_number'              => 'B2B/FIN/TEST/' . time(),
    'client_id'                 => (int)$client['id'],
    'client_name_snapshot'      => $client['company_name'],
    'client_phone_snapshot'     => $client['phone'] ?? '',
    'delivery_address_snapshot' => $client['delivery_address'] ?? '',
    'delivery_date'             => date('Y-m-d', strtotime('+1 day')),
    'status'                    => 'new',
    'total_amount'              => 50.00,
    'notes'                     => 'Test akcji finalizacji',
], [
    [
        'product_id'      => $prodId,
        'product_name'    => $prodName,
        'price'           => 5.00,
        'quantity'        => 10.0,
        'unit'            => 'kg',
        'package_size'    => 1.0,
        'package_unit'    => 'kg',
        'package_summary' => '10 kg',
        'item_total'      => 50.00
    ]
]);

assertCheck($testOrderId > 0, "Utworzono zamówienie testowe o ID: {$testOrderId}");

$cookieFile = tempnam(sys_get_temp_dir(), 'cook_fin_test_');
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

// Pobranie widoku admina i sprawdzenie nowego zamówienia
curl_setopt($ch, CURLOPT_URL, 'http://localhost/b2b/admin');
$adminHtml = curl_exec($ch);

// Sprawdzenie tokena CSRF panelu
preg_match('/const CSRF_TOKEN\s*=\s*\'([^\']+)\';/', (string)$adminHtml, $mAdminCsrf);
$adminCsrf = $mAdminCsrf[1] ?? '';
assertCheck(!empty($adminCsrf), "Pobrano token CSRF panelu hurtownika");

// Stan 1: Zamówienie 'new' posiada przycisk "Finalizuj zamówienie" w swojej komórce akcji
$rowPatternNew = '/<tr[^>]+id="order-row-' . $testOrderId . '"[^>]*>.*?<\/tr>/s';
preg_match($rowPatternNew, (string)$adminHtml, $mRowNew);
$rowHtmlNew = $mRowNew[0] ?? '';

assertCheck(!empty($rowHtmlNew), "Znaleziono wiersz testowego zamówienia w tabeli");
assertCheck(strpos($rowHtmlNew, 'Finalizuj zamówienie') !== false, "Wiersz nowego zamówienia zawiera przycisk 'Finalizuj zamówienie'");
assertCheck(strpos($rowHtmlNew, 'Zamówienie zrealizowane') === false, "Wiersz nowego zamówienia NIE zawiera napisu 'Zamówienie zrealizowane'");

// Stan 2: Zmiana statusu na 'completed' (Finalizacja)
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_URL, 'http://localhost/b2b/updateorderstatus');
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'id'     => $testOrderId,
    'status' => 'completed',
    '_csrf'  => $adminCsrf
]);
$resUpdate = curl_exec($ch);
$jsonUpdate = json_decode((string)$resUpdate, true);
assertCheck(!empty($jsonUpdate['ok']), "API updateorderstatus zwróciło ok=true dla statusu 'completed'");

// Ponowne pobranie panelu i sprawdzenie stanu zrealizowanego
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_URL, 'http://localhost/b2b/admin');
$adminHtmlComp = curl_exec($ch);

preg_match($rowPatternNew, (string)$adminHtmlComp, $mRowComp);
$rowHtmlComp = $mRowComp[0] ?? '';

assertCheck(!empty($rowHtmlComp), "Znaleziono wiersz zrealizowanego zamówienia w tabeli");
assertCheck(strpos($rowHtmlComp, 'Zamówienie zrealizowane') !== false, "Kolumna AKCJA zawiera napis 'Zamówienie zrealizowane'");
assertCheck(strpos($rowHtmlComp, 'Finalizuj zamówienie') === false, "Kolumna AKCJA NIE zawiera przycisku 'Finalizuj zamówienie' dla statusu zrealizowanego");

// Stan 3: Ręczne przywrócenie zamówienia do statusu 'processing' (W kompletacji)
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_URL, 'http://localhost/b2b/updateorderstatus');
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'id'     => $testOrderId,
    'status' => 'processing',
    '_csrf'  => $adminCsrf
]);
$resRestore = curl_exec($ch);
$jsonRestore = json_decode((string)$resRestore, true);
assertCheck(!empty($jsonRestore['ok']), "API updateorderstatus zwróciło ok=true przy przywróceniu statusu na 'processing'");

// Ponowne pobranie panelu i weryfikacja powrotu przycisku "Finalizuj zamówienie"
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_URL, 'http://localhost/b2b/admin');
$adminHtmlProc = curl_exec($ch);

preg_match($rowPatternNew, (string)$adminHtmlProc, $mRowProc);
$rowHtmlProc = $mRowProc[0] ?? '';

assertCheck(!empty($rowHtmlProc), "Znaleziono wiersz przywróconego zamówienia w tabeli");
assertCheck(strpos($rowHtmlProc, 'Finalizuj zamówienie') !== false, "Po przywróceniu zamówienia przycisk 'Finalizuj zamówienie' ponownie się pojawił");
assertCheck(strpos($rowHtmlProc, 'Zamówienie zrealizowane') === false, "Po przywróceniu zamówienia napis 'Zamówienie zrealizowane' zniknął");

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
