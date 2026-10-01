<?php
/**
 * Test weryfikujący zachowanie produktów spoza cennika przy eksporcie/imporcie do systemów ERP:
 * - Subiekt GT / Nexo (.epp / EDI++)
 * - Comarch ERP Optima (.xml)
 * - Symfonia Handel (.txt / HMF 3.0)
 * - Asseco WAPRO Wf-Mag (.xml)
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';
require_once BASE_PATH . '/program/lib/ErpImporter.php';
require_once BASE_PATH . '/program/lib/ErpExporter.php';
require_once BASE_PATH . '/program/lib/Tools.php';
require_once BASE_PATH . '/program/core/App.php';
require_once BASE_PATH . '/program/core/Controller.php';
require_once BASE_PATH . '/program/script/AppController.php';
require_once BASE_PATH . '/program/script/B2bController.php';

use App\B2bRepository;

echo "====================================================================\n";
echo "  TEST: OBSŁUGA POZYCJI SPOZA CENNIKA W EKSPORCIE/IMPORCIE ERP     \n";
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
// 1. Przygotowanie zamówienia mieszanego (katalog + pozycje spoza cennika)
// -------------------------------------------------------------------------
$client = $repo->getClientByLogin('magda');
if (!$client) {
    $all = $repo->getAllClients();
    $client = $all[0] ?? null;
}
assertCheck($client !== null, "Znaleziono klienta do testu ERP");

$testOrderNumber = 'ZK/ERP/' . date('Ymd_His');

$orderItems = [
    [
        'product_id'      => 101,
        'is_custom'       => 0,
        'product_name'    => 'Marchewka standardowa',
        'erp_code'        => 'MARCH-STD',
        'price'           => 3.50,
        'quantity'        => 20.0,
        'unit'            => 'kg',
        'package_size'    => 10.0,
        'package_unit'    => 'worek',
        'package_summary' => '2 worki',
        'item_total'      => 70.00
    ],
    [
        'product_id'      => null,
        'is_custom'       => 1,
        'product_name'    => 'Koper włoski świeży',
        'price'           => 0.00,
        'quantity'        => 5.0,
        'unit'            => 'kg',
        'package_size'    => 1.0,
        'package_unit'    => 'kg',
        'package_summary' => 'Produkt spoza cennika (do potwierdzenia)',
        'item_total'      => 0.00
    ],
    [
        'product_id'      => null,
        'is_custom'       => 1,
        'product_name'    => 'Awokado Hass dojrzewające (Bio)',
        'price'           => 0.00,
        'quantity'        => 12.0,
        'unit'            => 'szt.',
        'package_size'    => 1.0,
        'package_unit'    => 'szt.',
        'package_summary' => 'Produkt spoza cennika (do potwierdzenia)',
        'item_total'      => 0.00
    ]
];

$order = [
    'id'                        => 9999,
    'order_number'              => $testOrderNumber,
    'client_id'                 => (int)$client['id'],
    'client_name_snapshot'      => $client['company_name'],
    'client_phone_snapshot'     => $client['phone'] ?? '600100200',
    'delivery_address_snapshot' => $client['delivery_address'] ?? 'ul. Magazynowa 1, Warszawa',
    'delivery_date'             => date('Y-m-d', strtotime('+1 day')),
    'created_at'                => date('Y-m-d H:i:s'),
    'status'                    => 'new',
    'total_amount'              => 70.00,
    'notes'                     => 'Proszę o dostawę przed 7:00 rano',
    'items'                     => $orderItems,
];

// -------------------------------------------------------------------------
// 2. WERYFIKACJA SUBIEKT GT / NEXO (.epp)
// -------------------------------------------------------------------------
echo "\n--- 1. SUBIEKT GT / NEXO (EPP / EDI++) ---\n";
$subiektRes = ErpExporter::export('subiekt', [$order]);
$epp = iconv('WINDOWS-1250', 'UTF-8//IGNORE', $subiektRes['content']);

assertCheck(strpos($epp, '[INFO]') !== false, "Subiekt: obecna sekcja [INFO]");
assertCheck(strpos($epp, '[TOWARY]') !== false, "Subiekt: obecna sekcja [TOWARY]");
assertCheck(strpos($epp, 'MARCH-STD') !== false, "Subiekt: produkt katalogowy zachowuje swój kod ERP");
assertCheck(strpos($epp, 'SPOZA-KOPER-WLOS') !== false, "Subiekt: produkt spoza cennika ma kod z przedrostkiem SPOZA-");
assertCheck(strpos($epp, 'SPOZA-AWOKADO-HASS') !== false, "Subiekt: drugi produkt spoza cennika ma unikalny kod SPOZA-");
assertCheck(strpos($epp, 'Koper włoski świeży [SPOZA CENNIKA]') !== false, "Subiekt: nazwa zawiera [SPOZA CENNIKA]");
assertCheck(strpos($epp, '[DOKUMENT]') !== false, "Subiekt: obecna sekcja [DOKUMENT]");
assertCheck(strpos($epp, 'UWAGA: Zamówienie zawiera pozycje spoza cennika') !== false, "Subiekt: nagłówek dokumentu zawiera ostrzeżenie w uwagach");
assertCheck(strpos($epp, '[ZAWARTOSC]') !== false, "Subiekt: obecna sekcja [ZAWARTOSC]");
assertCheck(strpos($epp, '0.00,0.00,0.00,5.00') !== false, "Subiekt: pozycje spoza cennika mają cenę 0.00 i poprawną stawkę VAT");

// Sprawdzenie limitów Subiekt GT (tw_Symbol <= 20, tw_Nazwa <= 50)
$lines = explode("\n", $epp);
$inTowary = false;
$symbolMaxLenOk = true;
$nameMaxLenOk = true;
foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '[TOWARY]') { $inTowary = true; continue; }
    if ($inTowary && $line !== '' && $line[0] === '[') { $inTowary = false; }
    if ($inTowary && $line !== '') {
        $parts = str_getcsv($line, ',', '"');
        if (count($parts) >= 3) {
            $sym = $parts[1] ?? '';
            $nam = $parts[2] ?? '';
            if (mb_strlen($sym, 'UTF-8') > 20) $symbolMaxLenOk = false;
            if (mb_strlen($nam, 'UTF-8') > 50) $nameMaxLenOk = false;
        }
    }
}
assertCheck($symbolMaxLenOk, "Subiekt: wszystkie kody towarów mieszczą się w limicie 20 znaków (tw_Symbol)");
assertCheck($nameMaxLenOk, "Subiekt: wszystkie nazwy towarów mieszczą się w limicie 50 znaków (tw_Nazwa)");

// -------------------------------------------------------------------------
// 3. WERYFIKACJA COMARCH ERP OPTIMA (.xml)
// -------------------------------------------------------------------------
echo "\n--- 2. COMARCH ERP OPTIMA (XML OPT021) ---\n";
$optimaRes = ErpExporter::export('optima', [$order]);
$optimaXml = simplexml_load_string($optimaRes['content']);
assertCheck($optimaXml !== false, "Optima: poprawny XML zgodny z parserem SimpleXML");

$ro = $optimaXml->DOKUMENTY->ZAMOWIENIA_OD_ODBIORCY->ZAMOWIENIE;
$roOpis = (string)$ro->NAGLOWEK->OPIS;
assertCheck(strpos($roOpis, 'UWAGA: Zamówienie zawiera pozycje spoza cennika') !== false, "Optima: nagłówek RO zawiera uwagę o pozycjach spoza cennika");

$pozycjeOptima = $ro->POZYCJE->POZYCJA;
assertCheck(count($pozycjeOptima) === 3, "Optima: wyeksportowano dokładnie 3 pozycje");

$customOptimaFound = false;
foreach ($pozycjeOptima as $poz) {
    if (strpos((string)$poz->TOWAR_KOD, 'SPOZA-') === 0) {
        $customOptimaFound = true;
        assertCheck((string)$poz->CENA_NETTO === '0.00', "Optima: cena netto pozycji spoza cennika wynosi 0.00");
        assertCheck((string)$poz->WARTOSC_NETTO === '0.00', "Optima: wartość netto wynosi 0.00");
        assertCheck(strpos((string)$poz->TOWAR_NAZWA, '[SPOZA CENNIKA]') !== false, "Optima: nazwa towaru zawiera [SPOZA CENNIKA]");
        assertCheck(strpos((string)$poz->OPIS, 'spoza cennika') !== false, "Optima: pole OPIS pozycji zawiera informację o wycenie");
    }
}
assertCheck($customOptimaFound, "Optima: znaleziono pozycje z kodem SPOZA-");

// -------------------------------------------------------------------------
// 4. WERYFIKACJA SYMFONIA HANDEL (.txt / HMF 3.0)
// -------------------------------------------------------------------------
echo "\n--- 3. SYMFONIA HANDEL (HMF 3.0) ---\n";
$symfoniaRes = ErpExporter::export('symfonia', [$order]);
$symTxt = iconv('WINDOWS-1250', 'UTF-8//IGNORE', $symfoniaRes['content']);

assertCheck(strpos($symTxt, 'format = "SymfoniaHandel"') !== false, "Symfonia: poprawny nagłówek HMF 3.0");
assertCheck(strpos($symTxt, 'UWAGA: Zamówienie zawiera pozycje spoza cennika') !== false, "Symfonia: nagłówek dokumentu zawiera uwagę o pozycjach spoza cennika");
assertCheck(strpos($symTxt, 'kod = "SPOZA-KOPER-WLOS') !== false, "Symfonia: pozycja ma kod SPOZA-");
assertCheck(strpos($symTxt, 'nazwa = "Koper włoski świeży [SPOZA CENNIKA]"') !== false, "Symfonia: nazwa zawiera [SPOZA CENNIKA]");
assertCheck(strpos($symTxt, 'cena = 0.00') !== false, "Symfonia: cena = 0.00");
assertCheck(strpos($symTxt, 'wartosc = 0.00') !== false, "Symfonia: wartosc = 0.00");
assertCheck(strpos($symTxt, 'spoza cennika') !== false, "Symfonia: opis pozycji zawiera adnotację");

// -------------------------------------------------------------------------
// 5. WERYFIKACJA ASSECO WAPRO WF-MAG (.xml)
// -------------------------------------------------------------------------
echo "\n--- 4. ASSECO WAPRO WF-MAG (XML) ---\n";
$wfmagRes = ErpExporter::export('wfmag', [$order]);
$wfXml = simplexml_load_string($wfmagRes['content']);
assertCheck($wfXml !== false, "Wf-Mag: poprawny XML zgodny ze specyfikacją");

$zoWf = $wfXml->ZAMOWIENIE_ODBIORCY;
$uwagiWf = (string)$zoWf->UWAGI;
assertCheck(strpos($uwagiWf, 'UWAGA: Zamówienie zawiera pozycje spoza cennika') !== false, "Wf-Mag: nagłówek ZO zawiera uwagę w polu UWAGI");

$pozycjeWf = $zoWf->POZYCJE->POZYCJA;
assertCheck(count($pozycjeWf) === 3, "Wf-Mag: wyeksportowano dokładnie 3 pozycje");

$customWfFound = false;
foreach ($pozycjeWf as $poz) {
    if (strpos((string)$poz->INDEKS, 'SPOZA-') === 0) {
        $customWfFound = true;
        assertCheck((string)$poz->CENA_NETTO === '0.00', "Wf-Mag: CENA_NETTO = 0.00");
        assertCheck((string)$poz->WARTOSC_NETTO === '0.00', "Wf-Mag: WARTOSC_NETTO = 0.00");
        assertCheck(strpos((string)$poz->NAZWA, '[SPOZA CENNIKA]') !== false, "Wf-Mag: NAZWA zawiera [SPOZA CENNIKA]");
        assertCheck(strpos((string)$poz->UWAGI, 'spoza cennika') !== false, "Wf-Mag: UWAGI pozycji zawierają adnotację");
    }
}
assertCheck($customWfFound, "Wf-Mag: znaleziono pozycje z indeksem SPOZA-");

// -------------------------------------------------------------------------
// 6. Test integracyjny HTTP przez endpoint GET /b2b/exporterp?id=...&format=...
// -------------------------------------------------------------------------
echo "\n--- 5. INTEGRACJA HTTP B2B CONTROLLER ---\n";

// Zapisujemy zamówienie w bazie, aby przetestować rzeczywisty endpoint
$realOrderId = $repo->createOrder([
    'order_number'              => 'B2B/ERP/TEST/' . time() . '/' . bin2hex(random_bytes(3)),
    'client_id'                 => (int)$client['id'],
    'client_name_snapshot'      => $client['company_name'],
    'client_phone_snapshot'     => $client['phone'] ?? '',
    'delivery_address_snapshot' => $client['delivery_address'] ?? '',
    'delivery_date'             => date('Y-m-d', strtotime('+1 day')),
    'status'                    => 'new',
    'total_amount'              => 70.00,
    'notes'                     => 'Test HTTP pozycji ERP',
], $orderItems);

assertCheck($realOrderId > 0, "Utworzono zamówienie w bazie dla testu HTTP (ID: {$realOrderId})");

$cookieFile = tempnam(sys_get_temp_dir(), 'cook_erp_');
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
    'login' => defined('APP_LOGIN') ? APP_LOGIN : 'admin',
    'password' => (getenv('APP_TEST_PASSWORD') ?: 'admin123'),
    '_csrf' => $loginCsrf
]));
curl_exec($ch);
curl_setopt($ch, CURLOPT_POST, false);

// Test formatów przez curl
foreach (['subiekt', 'optima', 'symfonia', 'wfmag'] as $fmt) {
    curl_setopt($ch, CURLOPT_URL, "http://localhost/b2b/exporterp?id={$realOrderId}&format={$fmt}");
    $content = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    assertCheck($httpCode === 200, "HTTP GET /b2b/exporterp format={$fmt} zwrócił status 200");
    assertCheck(strpos((string)$content, 'SPOZA-') !== false, "HTTP GET format={$fmt} zawiera kod pozycji SPOZA-");
}

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
