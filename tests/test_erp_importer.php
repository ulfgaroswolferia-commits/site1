<?php
/**
 * Test TDD: ErpImporter — parsowanie plików ERP (Subiekt, Optima, Symfonia, Wf-Mag)
 *
 * Uruchomienie (z katalogu projektu):
 *   php tests/test_erp_importer.php
 */
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/program/lib/ErpImporter.php';

echo "=== TEST: ErpImporter — parsowanie plików importu ERP ===\n\n";

$pass = 0;
$fail = 0;

function ok(string $name, bool $condition): void
{
    global $pass, $fail;
    if ($condition) {
        echo "  PASS  $name\n";
        $pass++;
    } else {
        echo "  FAIL  $name\n";
        $fail++;
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. SUBIEKT GT / NEXO (.epp — EDI++)
// ─────────────────────────────────────────────────────────────────────────────
echo "1. Subiekt GT / Nexo (.epp)\n";

// Generujemy próbkę za pomocą ErpExporter i importujemy ją z powrotem
require_once BASE_PATH . '/program/lib/ErpExporter.php';

$sampleOrders = [
    [
        'id'                        => 1,
        'order_number'              => 'B2B/2026/09/01',
        'client_name_snapshot'      => 'Sklep Testowy',
        'client_phone_snapshot'     => '500100200',
        'delivery_address_snapshot' => 'ul. Testowa 1',
        'nip'                       => '9876543210',
        'delivery_date'             => '2026-09-10',
        'created_at'                => '2026-09-09 10:00:00',
        'total_amount'              => 230.00,
        'notes'                     => '',
        'items'                     => [
            ['product_name' => 'Pomidor Malinowy', 'erp_code' => 'POM-MAL', 'quantity' => 10.0, 'unit' => 'kg', 'price' => 8.50, 'item_total' => 85.00, 'package_summary' => ''],
            ['product_name' => 'Koperek Świeży',   'erp_code' => 'KOP-SW',  'quantity' => 5.0,  'unit' => 'pęczek', 'price' => 3.00, 'item_total' => 15.00, 'package_summary' => ''],
            ['product_name' => 'Jabłko Gala',      'erp_code' => '',        'quantity' => 12.0, 'unit' => 'kg', 'price' => 4.20, 'item_total' => 50.40, 'package_summary' => ''],
        ],
    ],
];

$eppContent = ErpExporter::exportSubiektEpp($sampleOrders);
$subiektProducts = ErpImporter::parseSubiektEpp($eppContent);

ok('Znaleziono co najmniej 2 towary',  count($subiektProducts) >= 2);
ok('Zawiera towar Pomidor Malinowy',   !empty(array_filter($subiektProducts, fn($p) => str_contains($p['name'], 'Pomidor'))));
ok('Zawiera towar Koperek',            !empty(array_filter($subiektProducts, fn($p) => str_contains($p['name'], 'Koperek'))));
$pom = current(array_filter($subiektProducts, fn($p) => str_contains($p['name'], 'Pomidor')));
ok('Cena Pomidora = 8.50',             $pom && abs((float)$pom['price'] - 8.50) < 0.01);
ok('Kod ERP Pomidora = POM-MAL',       $pom && $pom['erp_code'] === 'POM-MAL');
$kop = current(array_filter($subiektProducts, fn($p) => str_contains($p['name'], 'Koperek')));
ok('Jednostka Koperka = pęczek',       $kop && $kop['unit'] === 'pęczek');

// Automatyczna detekcja formatu
$detected = ErpImporter::detectFormat($eppContent);
ok('Autodetekcja: subiekt',            $detected === 'subiekt');

// ─────────────────────────────────────────────────────────────────────────────
// 2. COMARCH ERP OPTIMA (.xml)
// ─────────────────────────────────────────────────────────────────────────────
echo "\n2. Comarch ERP Optima (.xml)\n";

$optimaXml = ErpExporter::exportComarchOptimaXml($sampleOrders);
$optimaProducts = ErpImporter::parseComarchOptimaXml($optimaXml);

ok('Znaleziono co najmniej 2 towary',  count($optimaProducts) >= 2);
$pomO = current(array_filter($optimaProducts, fn($p) => str_contains($p['name'], 'Pomidor')));
ok('Zawiera Pomidora z ceną',          $pomO && (float)$pomO['price'] > 0);
ok('Kod ERP Pomidora poprawny',        $pomO && !empty($pomO['erp_code']));

// Autodetekcja XML Optima
$detectedO = ErpImporter::detectFormat($optimaXml);
ok('Autodetekcja: optima',             $detectedO === 'optima');

// ─────────────────────────────────────────────────────────────────────────────
// 3. SYMFONIA HANDEL (.txt — HMF 3.0)
// ─────────────────────────────────────────────────────────────────────────────
echo "\n3. Symfonia Handel (.txt)\n";

$symfoniaContent = ErpExporter::exportSymfoniaTxt($sampleOrders);
$symfoniaProducts = ErpImporter::parseSymfoniaTxt($symfoniaContent);

ok('Znaleziono co najmniej 2 towary',  count($symfoniaProducts) >= 2);
$pomS = current(array_filter($symfoniaProducts, fn($p) => str_contains($p['name'], 'Pomidor')));
ok('Zawiera Pomidora',                 $pomS !== false);
ok('Cena Pomidora > 0',               $pomS && (float)$pomS['price'] > 0);

$detectedS = ErpImporter::detectFormat($symfoniaContent);
ok('Autodetekcja: symfonia',           $detectedS === 'symfonia');

// ─────────────────────────────────────────────────────────────────────────────
// 4. ASSECO WAPRO WF-MAG (.xml)
// ─────────────────────────────────────────────────────────────────────────────
echo "\n4. Asseco WAPRO Wf-Mag (.xml)\n";

$wfmagXml = ErpExporter::exportWfMagXml($sampleOrders);
$wfmagProducts = ErpImporter::parseWfMagXml($wfmagXml);

ok('Znaleziono co najmniej 2 towary',  count($wfmagProducts) >= 2);
$pomW = current(array_filter($wfmagProducts, fn($p) => str_contains($p['name'], 'Pomidor')));
ok('Zawiera Pomidora z ceną',          $pomW && (float)$pomW['price'] > 0);

$detectedW = ErpImporter::detectFormat($wfmagXml);
ok('Autodetekcja: wfmag',             $detectedW === 'wfmag');

// ─────────────────────────────────────────────────────────────────────────────
// 5. Normalizacja jednostek
// ─────────────────────────────────────────────────────────────────────────────
echo "\n5. Normalizacja jednostek\n";

ok('szt → szt.',    ErpImporter::normalizeUnit('szt') === 'szt.');
ok('SZT → szt.',    ErpImporter::normalizeUnit('SZT') === 'szt.');
ok('sztuka → szt.', ErpImporter::normalizeUnit('sztuka') === 'szt.');
ok('pęczek → pęczek', ErpImporter::normalizeUnit('pęczek') === 'pęczek');
ok('peczek → pęczek', ErpImporter::normalizeUnit('peczek') === 'pęczek');
ok('op → op.',      ErpImporter::normalizeUnit('op') === 'op.');
ok('karton → op.',  ErpImporter::normalizeUnit('karton') === 'op.');
ok('kg → kg',       ErpImporter::normalizeUnit('kg') === 'kg');
ok('nieznana → kg', ErpImporter::normalizeUnit('nieznana') === 'kg');

// ─────────────────────────────────────────────────────────────────────────────
// 6. Główny dispatch ErpImporter::import()
// ─────────────────────────────────────────────────────────────────────────────
echo "\n6. Dispatch ErpImporter::import()\n";

foreach (['subiekt', 'optima', 'symfonia', 'wfmag'] as $fmt) {
    $exportedContent = match ($fmt) {
        'subiekt' => $eppContent,
        'optima'  => $optimaXml,
        'symfonia'=> $symfoniaContent,
        'wfmag'   => $wfmagXml,
    };
    $result = ErpImporter::import($fmt, $exportedContent);
    ok("import('$fmt') zwraca tablicę produktów", is_array($result) && count($result) >= 2);
}

// Nieznany format
try {
    ErpImporter::import('nieznany', '');
    ok('Wyjątek dla nieznany format: nie rzucono', false);
} catch (InvalidArgumentException $e) {
    ok('Wyjątek InvalidArgumentException dla nieznany format', true);
}

// ─────────────────────────────────────────────────────────────────────────────
// Podsumowanie
// ─────────────────────────────────────────────────────────────────────────────
echo "\n=== Wynik: PASS=$pass, FAIL=$fail ===\n";
if ($fail > 0) {
    echo "=== TEST ZAKOŃCZONY BŁĘDEM (Stan RED) ===\n";
    exit(1);
}
echo "=== TEST ZAKOŃCZONY SUKCESEM (Stan GREEN) ===\n";
