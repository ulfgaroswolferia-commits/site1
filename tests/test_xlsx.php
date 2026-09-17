<?php
define('BASE_PATH', 'C:/laragon/www');
require_once BASE_PATH . '/program/lib/XlsxParser.php';
require_once BASE_PATH . '/program/lib/XlsxWriter.php';

echo "=== TEST XLSX WRITER & PARSER ===\n";

$testItems = [
    ['name' => 'Pomidory malinowe', 'price' => 8.50, 'quantity' => 15.5, 'unit' => 'kg'],
    ['name' => 'Ziemniaki młode',   'price' => 2.20, 'quantity' => 50.0, 'unit' => 'kg'],
    ['name' => 'Sałata masłowa',    'price' => 3.00, 'quantity' => 20,   'unit' => 'szt.'],
];

$testFile = sys_get_temp_dir() . '/test_order_out.xlsx';
if (file_exists($testFile)) {
    unlink($testFile);
}

// 1. Zapis czystego pliku
$saved = XlsxWriter::saveToFile($testFile, $testItems, [
    'order_number'  => 'ZAM/TEST/001',
    'supplier_name' => 'Hurtownia Owoc-Warzyw',
    'created_at'    => date('Y-m-d H:i'),
]);

if (!$saved || !file_exists($testFile)) {
    echo "ERROR: Nie udało się zapisać pliku XLSX!\n";
    exit(1);
}
echo "[OK] Plik XLSX wygenerowany pomyślnie (" . filesize($testFile) . " bajtów).\n";

// 2. Odczyt przez XlsxParser
$parser = XlsxParser::open($testFile);
$rows = $parser->getRawRows(15);
echo "[OK] Liczba sparsowanych wierszy: " . count($rows) . "\n";

$candidates = $parser->detectCandidateColumns($rows);
echo "[OK] Wykryty nagłówek na wierszu: " . ($candidates['headerRow'] ?? 'brak') . "\n";
echo "[OK] Kolumna towaru: " . $candidates['productCol'] . ", ceny: " . $candidates['priceCol'] . "\n";

$products = $parser->extractProducts($candidates['headerRow'], $candidates['productCol'], $candidates['priceCol'], $candidates['unitCol']);
echo "[OK] Liczba wyciągniętych produktów: " . count($products) . "\n";

foreach ($products as $p) {
    echo "  - {$p['name']}: {$p['price']} zł ({$p['unit']})\n";
}

if (count($products) < 3) {
    echo "ERROR: Za mało produktów wyekstrahowanych!\n";
    exit(1);
}

echo "=== ALL XLSX TESTS PASSED! ===\n";
