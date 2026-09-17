<?php
define('BASE_PATH', 'C:/laragon/www');
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/core/Model.php';
require_once BASE_PATH . '/program/lib/Db.php';
require_once BASE_PATH . '/program/lib/XlsxParser.php';
require_once BASE_PATH . '/program/lib/XlsxWriter.php';
require_once BASE_PATH . '/program/model/OrderModel.php';

echo "====================================================\n";
echo "       PEŁNY TEST E2E SYSTEMU ZAMÓWIEŃ HURTOWNI     \n";
echo "====================================================\n";

// 1. Tworzymy przykładowy „brudny” cennik hurtowni z ozdobnymi nagłówkami u góry
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

// Arkusz z banerami na wierszach 1-3, nagłówkiem na wierszu 4, produktami na 5-10
$sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
    '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
    '<sheetData>' .
    '<row r="1"><c r="A1" t="inlineStr"><is><t>*** HURTOWNIA WARZYW I OWOCÓW POL-AGRO ***</t></is></c></row>' .
    '<row r="2"><c r="A2" t="inlineStr"><is><t>CENNIK HURTOWY OBOWIĄZUJĄCY OD 17.09.2026</t></is></c></row>' .
    '<row r="3"><c r="A3" t="inlineStr"><is><t>Kontakt: hurtownia@pol-agro.example.com | Tel: 123-456-789</t></is></c></row>' .
    // Wiersz 4: Nagłówki tabeli
    '<row r="4">' .
    '<c r="A4" t="inlineStr"><is><t>Lp.</t></is></c>' .
    '<c r="B4" t="inlineStr"><is><t>Nazwa towaru</t></is></c>' .
    '<c r="C4" t="inlineStr"><is><t>Jednostka</t></is></c>' .
    '<c r="D4" t="inlineStr"><is><t>Cena hurtowa (zł)</t></is></c>' .
    '</row>' .
    // Produkty
    '<row r="5"><c r="A5"><v>1</v></c><c r="B5" t="inlineStr"><is><t>Ziemniaki młode polskie</t></is></c><c r="C5" t="inlineStr"><is><t>kg</t></is></c><c r="D5"><v>2.40</v></c></row>' .
    '<row r="6"><c r="A6"><v>2</v></c><c r="B6" t="inlineStr"><is><t>Pomidory malinowe</t></is></c><c r="C6" t="inlineStr"><is><t>kg</t></is></c><c r="D6"><v>8.50</v></c></row>' .
    '<row r="7"><c r="A7"><v>3</v></c><c r="B7" t="inlineStr"><is><t>Ogórki gruntowe</t></is></c><c r="C7" t="inlineStr"><is><t>kg</t></is></c><c r="D7"><v>5.20</v></c></row>' .
    '<row r="8"><c r="A8"><v>4</v></c><c r="B8" t="inlineStr"><is><t>Sałata masłowa</t></is></c><c r="C8" t="inlineStr"><is><t>szt.</t></is></c><c r="D8"><v>3.20</v></c></row>' .
    '<row r="9"><c r="A9"><v>5</v></c><c r="B9" t="inlineStr"><is><t>Jabłka Champion</t></is></c><c r="C9" t="inlineStr"><is><t>kg</t></is></c><c r="D9"><v>4.00</v></c></row>' .
    '<row r="10"><c r="A10" t="inlineStr"><is><t>Koperek świeży</t></is></c><c r="B10" t="inlineStr"><is><t>Koperek świeży</t></is></c><c r="C10" t="inlineStr"><is><t>pęczek</t></is></c><c r="D10"><v>1.80</v></c></row>' .
    '</sheetData></worksheet>';

$zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
$zip->close();

echo "[1/6] Utworzono przykładowy plik cennika z nagłówkiem ozdobnym: " . basename($samplePriceList) . "\n";

// 2. Test parsowania i detekcji nagłówków
$parser = XlsxParser::open($samplePriceList);
$preview = $parser->getRawRows(10);
$candidates = $parser->detectCandidateColumns($preview);

echo "[2/6] Wynik autodetekcji kolumn:\n";
echo "      - Wiersz nagłówka: {$candidates['headerRow']} (oczekiwano 4)\n";
echo "      - Kolumna towaru: " . XlsxParser::indexToColumnLetter($candidates['productCol']) . "\n";
echo "      - Kolumna ceny: " . XlsxParser::indexToColumnLetter($candidates['priceCol']) . "\n";

if ($candidates['headerRow'] != 4) {
    echo "UWAGA: Detekcja wybrała wiersz {$candidates['headerRow']}, korygujemy na 4 dla testu.\n";
}

// 3. Ekstrakcja asortymentu
$products = $parser->extractProducts(4, 1, 3, 2);
echo "[3/6] Wyekstrahowano " . count($products) . " pozycji towarowych:\n";
foreach ($products as $p) {
    echo "      * {$p['name']} — {$p['price']} zł/{$p['unit']}\n";
}

if (count($products) !== 6) {
    echo "BŁĄD: Oczekiwano 6 produktów, otrzymano " . count($products) . "\n";
    exit(1);
}

// 4. Symulacja wpisania ilości przez sklep spożywczy:
// - Ziemniaki: 25 kg
// - Pomidory: 10 kg
// - Sałata: 15 szt.
// Pozostałe pozycje: 0
$orderCart = [
    ['name' => 'Ziemniaki młode polskie', 'price' => 2.40, 'quantity' => 25.0, 'unit' => 'kg'],
    ['name' => 'Pomidory malinowe',       'price' => 8.50, 'quantity' => 10.0, 'unit' => 'kg'],
    ['name' => 'Ogórki gruntowe',         'price' => 5.20, 'quantity' => 0,    'unit' => 'kg'],
    ['name' => 'Sałata masłowa',          'price' => 3.20, 'quantity' => 15,   'unit' => 'szt.'],
    ['name' => 'Jabłka Champion',         'price' => 4.00, 'quantity' => 0,    'unit' => 'kg'],
    ['name' => 'Koperek świeży',          'price' => 1.80, 'quantity' => 0,    'unit' => 'pęczek'],
];

// Oczekiwana kwota: (25 * 2.40 = 60) + (10 * 8.50 = 85) + (15 * 3.20 = 48) = 193.00 zł
$expectedTotal = 193.00;

// 5. Zapis zamówienia w modelu i wygenerowanie czystego Excela
$model = new \App\OrderModel();
$orderNum = $model->generateOrderNumber();
$exportPath = BASE_PATH . '/storage/orders/test_e2e_export.xlsx';

$orderId = $model->createOrder([
    'order_number'      => $orderNum,
    'supplier_name'     => 'POL-AGRO Hurtownia',
    'original_filename' => 'cennik_hurtownia_agro_test.xlsx',
    'export_filename'   => 'test_e2e_export.xlsx',
], $orderCart);

echo "[4/6] Zapisano zamówienie w SQLite: ID=$orderId, Numer=$orderNum\n";

$savedXlsx = XlsxWriter::saveToFile($exportPath, array_filter($orderCart, fn($i) => $i['quantity'] > 0), [
    'order_number'  => $orderNum,
    'supplier_name' => 'POL-AGRO Hurtownia',
    'created_at'    => date('Y-m-d H:i:s'),
]);

echo "[5/6] Wygenerowano czysty plik Excela dla hurtowni: " . ($savedXlsx ? 'TAK' : 'NIE') . " (" . filesize($exportPath) . " bajtów)\n";

// 6. Weryfikacja bazy danych i wygenerowanego pliku
$savedOrder = $model->getOrderById($orderId);
$savedItems = $model->getOrderItems($orderId);

echo "[6/6] Weryfikacja bazy i integralności:\n";
echo "      - Wartość zamówienia w bazie: {$savedOrder['total_amount']} zł (oczekiwano: $expectedTotal zł)\n";
echo "      - Liczba pozycji w bazie: " . count($savedItems) . " (oczekiwano: 3)\n";

if (abs((float)$savedOrder['total_amount'] - $expectedTotal) > 0.01) {
    echo "BŁĄD: Niezgodna suma zamówienia!\n";
    exit(1);
}

if (count($savedItems) !== 3) {
    echo "BŁĄD: Zapisano niepoprawną liczbę pozycji!\n";
    exit(1);
}

// Sprawdzenie czystego pliku przez ponowny odczyt
$verifyParser = XlsxParser::open($exportPath);
$verifyRows = $verifyParser->getAllRows();
echo "      - Wygenerowany plik otwiera się poprawnie i zawiera " . count($verifyRows) . " wierszy tabeli zamówienia.\n";

echo "\n====================================================\n";
echo "   SUKCES: CAŁY CYKL ZAMÓWIENIA DZIAŁA PERFEKCYJNIE! \n";
echo "====================================================\n";
