<?php
/**
 * Test weryfikacyjny detekcji kolumny jednostki oraz poprawnego przypisywania jednostek (szt. / kg / pęczek)
 */
define('BASE_PATH', 'C:/laragon/www');
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';
require_once BASE_PATH . '/program/lib/XlsxWriter.php';
require_once BASE_PATH . '/program/lib/XlsxParser.php';

echo "=== TEST: Detekcja kolumny jednostki i import szt./kg/pęczek ===\n";

// 1. Sprawdź plik testowy z typowym nagłówkiem: ["Lp.", "Nazwa towaru", "Ilość", "Jednostka", "Cena jedn. (zł)", "Wartość (zł)"]
$testFile = BASE_PATH . '/tmp/cennik_20260918_203522_660ee59d.xlsx';
if (!file_exists($testFile)) {
    echo "[SKIP] Brak pliku referencyjnego $testFile\n";
    exit(0);
}

$parser = XlsxParser::open($testFile);
$rows = $parser->getRawRows(15);
$cand = $parser->detectCandidateColumns($rows);

echo "1. Wykryci kandydaci kolumn:\n";
echo "   HeaderRow: " . ($cand['headerRow'] ?? 'null') . " (oczekiwano 5)\n";
echo "   ProductCol: " . ($cand['productCol'] ?? 'null') . " (oczekiwano 1)\n";
echo "   UnitCol: " . ($cand['unitCol'] ?? 'null') . " (oczekiwano 3 dla 'Jednostka')\n";
echo "   PriceCol: " . ($cand['priceCol'] ?? 'null') . " (oczekiwano 4 dla 'Cena jedn.')\n";

$passDetect = ($cand['headerRow'] == 5 && $cand['productCol'] == 1 && $cand['unitCol'] == 3 && $cand['priceCol'] == 4);
if (!$passDetect) {
    echo "[FAILED] Błędna autodetekcja kolumn! unitCol nie może być kolumną ceny ({$cand['unitCol']}), a priceCol nie może być kolumną wartości ({$cand['priceCol']})\n";
} else {
    echo "[OK] Autodetekcja kolumn bezbłędna!\n";
}

// 2. Ekstrakcja produktów z wskazanym unitCol = 3
$extracted = $parser->extractProducts(5, 1, 4, 3);
$salata = null;
foreach ($extracted as $it) {
    if (stripos($it['name'], 'sałata') !== false) {
        $salata = $it;
        break;
    }
}

if (!$salata) {
    echo "[FAILED] Nie odnaleziono Sałaty w wyekstrahowanych pozycjach\n";
    exit(1);
}

echo "2. Sałata masłowa po ekstrakcji:\n";
echo "   Cena: {$salata['price']} zł (oczekiwano 3.20 zł)\n";
echo "   Jednostka: {$salata['unit']} (oczekiwano 'szt.')\n";

$passSalata = ($salata['price'] == 3.20 && $salata['unit'] === 'szt.');
if (!$passSalata) {
    echo "[FAILED] Sałata masłowa ma złą cenę lub jednostkę!\n";
} else {
    echo "[OK] Sałata masłowa poprawnie zachowała jednostkę 'szt.' i cenę 3.20 zł!\n";
}

// 3. Sprawdzenie inteligentnego fallbacku (gdy brak kolumny jednostki w pliku)
$salataAuto = XlsxParser::detectProductUnit("Sałata masłowa");
$ogorekAuto = XlsxParser::detectProductUnit("Ogórek gruntowy");
$koperekAuto = XlsxParser::detectProductUnit("Koperek świeży");
$arbuzAuto  = XlsxParser::detectProductUnit("Arbuz hiszpański");

echo "3. Inteligentna detekcja jednostek (gdy brak kolumny jm w arkuszu):\n";
echo "   Sałata: $salataAuto (oczekiwano szt.)\n";
echo "   Ogórek: $ogorekAuto (oczekiwano kg)\n";
echo "   Koperek: $koperekAuto (oczekiwano pęczek)\n";
echo "   Arbuz: $arbuzAuto (oczekiwano szt.)\n";

$passAuto = ($salataAuto === 'szt.' && $ogorekAuto === 'kg' && $koperekAuto === 'pęczek' && $arbuzAuto === 'szt.');
if (!$passAuto) {
    echo "[FAILED] Błąd inteligentnej detekcji domyślnych jednostek\n";
} else {
    echo "[OK] Inteligentna detekcja działa bezbłędnie!\n";
}

$allOk = $passDetect && $passSalata && $passAuto;
echo $allOk ? "=== WSZYSTKIE TESTY JEDNOSTEK PRZESZŁY ===\n" : "=== TESTY NIE PRZESZŁY ===\n";
exit($allOk ? 0 : 1);
