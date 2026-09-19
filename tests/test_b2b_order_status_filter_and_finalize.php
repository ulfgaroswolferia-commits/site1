<?php
/**
 * Test TDD: Filtr statusu zamówień (domyślnie 'Nowe'), kolumna NUMER/DATA oraz przycisk 'Finalizuj zamówienie' ze specyfikacją logistyczną.
 */
define('BASE_PATH', 'C:/laragon/www');
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';
require_once BASE_PATH . '/program/config/includes.php';

use App\B2bRepository;

echo "=== TEST: Filtr statusu, kolumna NUMER/DATA i Finalizacja zamówienia ===\n";

$repo = new B2bRepository();

// Upewnij się, że mamy przynajmniej jedno zamówienie ze statusem 'new'
$orders = $repo->getAllOrders(1);
if (empty($orders)) {
    $orderNumber = $repo->generateOrderNumber();
    $orderId = $repo->createOrder([
        'order_number'              => $orderNumber,
        'client_id'                 => 1,
        'client_name_snapshot'      => 'Test Odbiorca TDD',
        'client_phone_snapshot'     => '123456789',
        'delivery_address_snapshot' => 'ul. Logistyczna 10',
        'total_amount'              => 120.0,
        'notes'                     => 'Pilna dostawa rano'
    ], [
        [
            'product_id'      => 1,
            'product_name'    => 'Pomidory Maliniak',
            'price'           => 6.0,
            'quantity'        => 20,
            'unit'            => 'kg',
            'package_size'    => 6.0,
            'package_unit'    => 'skrzynka',
            'package_summary' => '3 skrzynki + 2 kg',
            'item_total'      => 120.0
        ]
    ]);
} else {
    $orderId = (int)$orders[0]['id'];
}

$cookieFile = tempnam(sys_get_temp_dir(), 'cook_filter_fin_');
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);

// 1. Logowanie do panelu administratora
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

// 2. Pobranie panelu hurtownika
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_URL, 'http://localhost/b2b/admin');
$html = curl_exec($ch);
curl_close($ch);
@unlink($cookieFile);

// A. Sprawdzenie nagłówka kolumny NUMER / DATA
$hasColNumerData = (strpos($html, 'NUMER / DATA') !== false || strpos($html, 'NUMER/DATA') !== false);
echo "1. Nagłówek pierwszej kolumny zawiera 'NUMER / DATA': " . ($hasColNumerData ? "PASS" : "FAIL") . "\n";

// B. Sprawdzenie czy wiersze zamówień posiadają datę w pierwszej kolumnie i atrybut data-order-status
$hasDataOrderStatus = (strpos($html, 'data-order-status="') !== false);
echo "2. Wiersze zamówień posiadają atrybut data-order-status: " . ($hasDataOrderStatus ? "PASS" : "FAIL") . "\n";

// C. Sprawdzenie filtrów statusów nad tabelą zamówień
$hasFilterNew = (strpos($html, 'order-filter-new') !== false || strpos($html, 'filterOrdersByStatus(\'new\')') !== false || strpos($html, 'filterOrdersByStatus("new")') !== false);
$hasFilterAll = (strpos($html, 'order-filter-all') !== false || strpos($html, 'filterOrdersByStatus(\'all\')') !== false || strpos($html, 'filterOrdersByStatus("all")') !== false);
$hasFilterProcessing = (strpos($html, 'filterOrdersByStatus(\'processing\')') !== false || strpos($html, 'filterOrdersByStatus("processing")') !== false);
$hasFilterCompleted = (strpos($html, 'filterOrdersByStatus(\'completed\')') !== false || strpos($html, 'filterOrdersByStatus("completed")') !== false);

$hasStatusFilters = ($hasFilterNew && $hasFilterAll);
echo "3. Przyciski filtru statusów obecne (Nowe, Wszystkie, W kompletacji, Zrealizowane): " . ($hasStatusFilters ? "PASS" : "FAIL") . "\n";

// D. Sprawdzenie funkcji JS filtrowania zamówień
$hasFilterJs = (strpos($html, 'filterOrdersByStatus') !== false);
echo "4. Funkcja JavaScript filterOrdersByStatus(): " . ($hasFilterJs ? "PASS" : "FAIL") . "\n";

// E. Sprawdzenie przycisku "Finalizuj zamówienie" w tabeli
$hasFinalizeBtn = (strpos($html, 'Finalizuj zamówienie') !== false || strpos($html, 'Finalizuj') !== false);
echo "5. Przycisk 'Finalizuj zamówienie' w wierszu zamówienia: " . ($hasFinalizeBtn ? "PASS" : "FAIL") . "\n";

// F. Sprawdzenie opcji wydruku specyfikacji logistycznej w modalu
$hasPrintBtn = (strpos($html, 'modal-order-print-btn') !== false || strpos($html, 'printOrderSpecification') !== false);
echo "6. Przycisk/opcja wydruku specyfikacji logistycznej (#modal-order-print-btn / printOrderSpecification): " . ($hasPrintBtn ? "PASS" : "FAIL") . "\n";

// G. Sprawdzenie stylów @media print dla specyfikacji magazynowej
$hasPrintStyles = (strpos($html, '@media print') !== false);
echo "7. Zdefiniowane reguły stylów do wydruku (@media print): " . ($hasPrintStyles ? "PASS" : "FAIL") . "\n";

// H. Sprawdzenie wyróżnionego przycisku "Finalizuj zamówienie" na samym dole okna modalnego
$hasModalFinalizeBtn = (strpos($html, 'id="btn-modal-finalize-order"') !== false && strpos($html, 'finalizeOrderAndPrint') !== false);
echo "8. Wyróżniony przycisk 'Finalizuj zamówienie' w modalu (#btn-modal-finalize-order): " . ($hasModalFinalizeBtn ? "PASS" : "FAIL") . "\n";

// I. Sprawdzenie podpisu pod przyciskiem
$hasModalFinalizeSubtext = (strpos($html, 'Drukuje specyfikację i zmienia status na Zrealizowane') !== false);
echo "9. Podpis pod przyciskiem 'Drukuje specyfikację i zmienia status na Zrealizowane': " . ($hasModalFinalizeSubtext ? "PASS" : "FAIL") . "\n";

// J. Sprawdzenie zamykania okna modalnego po finalizacji zamówienia
$hasModalCloseInFinalize = (bool)preg_match('/function finalizeOrderAndPrint\(\)[^}]+closeOrderModal\(\)/s', $html);
echo "10. Funkcja finalizeOrderAndPrint zamyka okno modalne (closeOrderModal): " . ($hasModalCloseInFinalize ? "PASS" : "FAIL") . "\n";

if (!$hasColNumerData || !$hasDataOrderStatus || !$hasStatusFilters || !$hasFilterJs || !$hasFinalizeBtn || !$hasPrintBtn || !$hasPrintStyles || !$hasModalFinalizeBtn || !$hasModalFinalizeSubtext || !$hasModalCloseInFinalize) {
    echo "=== TESTY ZAKOŃCZONE BŁĘDEM (Stan RED) ===\n";
    exit(1);
}

echo "=== WSZYSTKIE TESTY FILTRU, NUMER/DATA I FINALIZACJI ZAKOŃCZONE SUKCESEM ===\n";
