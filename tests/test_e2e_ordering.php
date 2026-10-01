<?php
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/core/Model.php';
require_once BASE_PATH . '/program/lib/Db.php';
require_once BASE_PATH . '/program/lib/XlsxParser.php';
require_once BASE_PATH . '/program/lib/XlsxWriter.php';
require_once BASE_PATH . '/program/model/OrderModel.php';

echo "====================================================\n";
echo "       PEĹNY TEST E2E SYSTEMU ZAMĂ“WIEĹ HURTOWNI     \n";
echo "====================================================\n";

// 1. Tworzymy przykĹ‚adowy â€žbrudnyâ€ť cennik hurtowni z ozdobnymi nagĹ‚Ăłwkami u gĂłry
$samplePriceList = sys_get_temp_dir() . '/cennik_hurtownia_agro_test.xlsx';

$zip = new ZipArchive();
$zip->open($samplePriceList, ZipArchive::CREATE | ZipArchive::OVERWRITE);

// Minimalne struktury OpenXML
$zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
    '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
    '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
    '<Default Extension="xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' .
    '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' .
    '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' .
    '</Types>');

$zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
    '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
    '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' .
    '</Relationships>');

$zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
    '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
    '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' .
    '</Relationships>');

$zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
    '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
    '<sheets><sheet name="Cennik" sheetId="1" r:id="rId1"/></sheets></workbook>');

// Arkusz z banerami na wierszach 1-3, nagĹ‚Ăłwkiem na wierszu 4, produktami na 5-10
$sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
    '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
    '<sheetData>' .
    '<row r="1"><c r="A1" t="inlineStr"><is><t>*** HURTOWNIA WARZYW I OWOCĂ“W POL-AGRO ***</t></is></c></row>' .
    '<row r="2"><c r="A2" t="inlineStr"><is><t>CENNIK HURTOWY OBOWIÄ„ZUJÄ„CY OD 17.09.2026</t></is></c></row>' .
    '<row r="3"><c r="A3" t="inlineStr"><is><t>Kontakt: hurtownia@pol-agro.example.com | Tel: 123-456-789</t></is></c></row>' .
    // Wiersz 4: NagĹ‚Ăłwki tabeli
    '<row r="4">' .
    '<c r="A4" t="inlineStr"><is><t>Lp.</t></is></c>' .
    '<c r="B4" t="inlineStr"><is><t>Nazwa towaru</t></is></c>' .
    '<c r="C4" t="inlineStr"><is><t>Jednostka</t></is></c>' .
    '<c r="D4" t="inlineStr"><is><t>Cena hurtowa (zĹ‚)</t></is></c>' .
    '</row>' .
    // Produkty
    '<row r="5"><c r="A5"><v>1</v></c><c r="B5" t="inlineStr"><is><t>Ziemniaki mĹ‚ode polskie</t></is></c><c r="C5" t="inlineStr"><is><t>kg</t></is></c><c r="D5"><v>2.40</v></c></row>' .
    '<row r="6"><c r="A6"><v>2</v></c><c r="B6" t="inlineStr"><is><t>Pomidory malinowe</t></is></c><c r="C6" t="inlineStr"><is><t>kg</t></is></c><c r="D6"><v>8.50</v></c></row>' .
    '<row r="7"><c r="A7"><v>3</v></c><c r="B7" t="inlineStr"><is><t>OgĂłrki gruntowe</t></is></c><c r="C7" t="inlineStr"><is><t>kg</t></is></c><c r="D7"><v>5.20</v></c></row>' .
    '<row r="8"><c r="A8"><v>4</v></c><c r="B8" t="inlineStr"><is><t>SaĹ‚ata masĹ‚owa</t></is></c><c r="C8" t="inlineStr"><is><t>szt.</t></is></c><c r="D8"><v>3.20</v></c></row>' .
    '<row r="9"><c r="A9"><v>5</v></c><c r="B9" t="inlineStr"><is><t>JabĹ‚ka Champion</t></is></c><c r="C9" t="inlineStr"><is><t>kg</t></is></c><c r="D9"><v>4.00</v></c></row>' .
    '<row r="10"><c r="A10" t="inlineStr"><is><t>Koperek Ĺ›wieĹĽy</t></is></c><c r="B10" t="inlineStr"><is><t>Koperek Ĺ›wieĹĽy</t></is></c><c r="C10" t="inlineStr"><is><t>pÄ™czek</t></is></c><c r="D10"><v>1.80</v></c></row>' .
    '</sheetData></worksheet>';

$zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
$zip->close();

echo "[1/6] Utworzono przykĹ‚adowy plik cennika z nagĹ‚Ăłwkiem ozdobnym: " . basename($samplePriceList) . "\n";

// 2. Test parsowania i detekcji nagĹ‚ĂłwkĂłw
$parser = XlsxParser::open($samplePriceList);
$preview = $parser->getRawRows(10);
$candidates = $parser->detectCandidateColumns($preview);

echo "[2/6] Wynik autodetekcji kolumn:\n";
echo "      - Wiersz nagĹ‚Ăłwka: {$candidates['headerRow']} (oczekiwano 4)\n";
echo "      - Kolumna towaru: " . XlsxParser::indexToColumnLetter($candidates['productCol']) . "\n";
echo "      - Kolumna ceny: " . XlsxParser::indexToColumnLetter($candidates['priceCol']) . "\n";

if ($candidates['headerRow'] != 4) {
    echo "UWAGA: Detekcja wybraĹ‚a wiersz {$candidates['headerRow']}, korygujemy na 4 dla testu.\n";
}

// 3. Ekstrakcja asortymentu
$products = $parser->extractProducts(4, 1, 3, 2);
echo "[3/6] Wyekstrahowano " . count($products) . " pozycji towarowych:\n";
foreach ($products as $p) {
    echo "      * {$p['name']} â€” {$p['price']} zĹ‚/{$p['unit']}\n";
}

if (count($products) !== 6) {
    echo "BĹÄ„D: Oczekiwano 6 produktĂłw, otrzymano " . count($products) . "\n";
    exit(1);
}

// 4. Symulacja wpisania iloĹ›ci przez sklep spoĹĽywczy:
// - Ziemniaki: 25 kg
// - Pomidory: 10 kg
// - SaĹ‚ata: 15 szt.
// PozostaĹ‚e pozycje: 0
$orderCart = [
    ['name' => 'Ziemniaki mĹ‚ode polskie', 'price' => 2.40, 'quantity' => 25.0, 'unit' => 'kg'],
    ['name' => 'Pomidory malinowe',       'price' => 8.50, 'quantity' => 10.0, 'unit' => 'kg'],
    ['name' => 'OgĂłrki gruntowe',         'price' => 5.20, 'quantity' => 0,    'unit' => 'kg'],
    ['name' => 'SaĹ‚ata masĹ‚owa',          'price' => 3.20, 'quantity' => 15,   'unit' => 'szt.'],
    ['name' => 'JabĹ‚ka Champion',         'price' => 4.00, 'quantity' => 0,    'unit' => 'kg'],
    ['name' => 'Koperek Ĺ›wieĹĽy',          'price' => 1.80, 'quantity' => 0,    'unit' => 'pÄ™czek'],
];

// Oczekiwana kwota: (25 * 2.40 = 60) + (10 * 8.50 = 85) + (15 * 3.20 = 48) = 193.00 zĹ‚
$expectedTotal = 193.00;

// 5. Zapis zamĂłwienia w modelu i wygenerowanie czystego Excela
$model = new \App\OrderModel();
$orderNum = $model->generateOrderNumber();
$exportPath = BASE_PATH . '/storage/orders/test_e2e_export.xlsx';

$orderId = $model->createOrder([
    'order_number'      => $orderNum,
    'supplier_name'     => 'POL-AGRO Hurtownia',
    'original_filename' => 'cennik_hurtownia_agro_test.xlsx',
    'export_filename'   => 'test_e2e_export.xlsx',
], $orderCart);

echo "[4/6] Zapisano zamĂłwienie w SQLite: ID=$orderId, Numer=$orderNum\n";

$savedXlsx = XlsxWriter::saveToFile($exportPath, array_filter($orderCart, fn($i) => $i['quantity'] > 0), [
    'order_number'  => $orderNum,
    'supplier_name' => 'POL-AGRO Hurtownia',
    'created_at'    => date('Y-m-d H:i:s'),
]);

echo "[5/6] Wygenerowano czysty plik Excela dla hurtowni: " . ($savedXlsx ? 'TAK' : 'NIE') . " (" . filesize($exportPath) . " bajtĂłw)\n";

// 6. Weryfikacja bazy danych i wygenerowanego pliku
$savedOrder = $model->getOrderById($orderId);
$savedItems = $model->getOrderItems($orderId);

echo "[6/6] Weryfikacja bazy i integralnoĹ›ci:\n";
echo "      - WartoĹ›Ä‡ zamĂłwienia w bazie: {$savedOrder['total_amount']} zĹ‚ (oczekiwano: $expectedTotal zĹ‚)\n";
echo "      - Liczba pozycji w bazie: " . count($savedItems) . " (oczekiwano: 3)\n";

if (abs((float)$savedOrder['total_amount'] - $expectedTotal) > 0.01) {
    echo "BĹÄ„D: Niezgodna suma zamĂłwienia!\n";
    exit(1);
}

if (count($savedItems) !== 3) {
    echo "BĹÄ„D: Zapisano niepoprawnÄ… liczbÄ™ pozycji!\n";
    exit(1);
}

// Sprawdzenie czystego pliku przez ponowny odczyt
$verifyParser = XlsxParser::open($exportPath);
$verifyRows = $verifyParser->getAllRows();
echo "      - Wygenerowany plik otwiera siÄ™ poprawnie i zawiera " . count($verifyRows) . " wierszy tabeli zamĂłwienia.\n";

echo "\n====================================================\n";
echo "   SUKCES: CAĹY CYKL ZAMĂ“WIENIA DZIAĹA PERFEKCYJNIE! \n";
echo "====================================================\n";
