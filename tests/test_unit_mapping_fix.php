<?php
/**
 * Test weryfikacyjny detekcji kolumny jednostki oraz poprawnego przypisywania jednostek (szt. / kg / pÄ™czek)
 */
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';
require_once BASE_PATH . '/program/lib/XlsxWriter.php';
require_once BASE_PATH . '/program/lib/XlsxParser.php';

echo "=== TEST: Detekcja kolumny jednostki i import szt./kg/pÄ™czek ===\n";

// 1. SprawdĹş plik testowy z typowym nagĹ‚Ăłwkiem: ["Lp.", "Nazwa towaru", "IloĹ›Ä‡", "Jednostka", "Cena jedn. (zĹ‚)", "WartoĹ›Ä‡ (zĹ‚)"]
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
    echo "[FAILED] BĹ‚Ä™dna autodetekcja kolumn! unitCol nie moĹĽe byÄ‡ kolumnÄ… ceny ({$cand['unitCol']}), a priceCol nie moĹĽe byÄ‡ kolumnÄ… wartoĹ›ci ({$cand['priceCol']})\n";
} else {
    echo "[OK] Autodetekcja kolumn bezbĹ‚Ä™dna!\n";
}

// 2. Ekstrakcja produktĂłw z wskazanym unitCol = 3
$extracted = $parser->extractProducts(5, 1, 4, 3);
$salata = null;
foreach ($extracted as $it) {
    if (stripos($it['name'], 'saĹ‚ata') !== false) {
        $salata = $it;
        break;
    }
}

if (!$salata) {
    echo "[FAILED] Nie odnaleziono SaĹ‚aty w wyekstrahowanych pozycjach\n";
    exit(1);
}

echo "2. SaĹ‚ata masĹ‚owa po ekstrakcji:\n";
echo "   Cena: {$salata['price']} zĹ‚ (oczekiwano 3.20 zĹ‚)\n";
echo "   Jednostka: {$salata['unit']} (oczekiwano 'szt.')\n";

$passSalata = ($salata['price'] == 3.20 && $salata['unit'] === 'szt.');
if (!$passSalata) {
    echo "[FAILED] SaĹ‚ata masĹ‚owa ma zĹ‚Ä… cenÄ™ lub jednostkÄ™!\n";
} else {
    echo "[OK] SaĹ‚ata masĹ‚owa poprawnie zachowaĹ‚a jednostkÄ™ 'szt.' i cenÄ™ 3.20 zĹ‚!\n";
}

// 3. Sprawdzenie inteligentnego fallbacku (gdy brak kolumny jednostki w pliku)
$salataAuto = XlsxParser::detectProductUnit("SaĹ‚ata masĹ‚owa");
$ogorekAuto = XlsxParser::detectProductUnit("OgĂłrek gruntowy");
$koperekAuto = XlsxParser::detectProductUnit("Koperek Ĺ›wieĹĽy");
$arbuzAuto  = XlsxParser::detectProductUnit("Arbuz hiszpaĹ„ski");

echo "3. Inteligentna detekcja jednostek (gdy brak kolumny jm w arkuszu):\n";
echo "   SaĹ‚ata: $salataAuto (oczekiwano szt.)\n";
echo "   OgĂłrek: $ogorekAuto (oczekiwano kg)\n";
echo "   Koperek: $koperekAuto (oczekiwano pÄ™czek)\n";
echo "   Arbuz: $arbuzAuto (oczekiwano szt.)\n";

$passAuto = ($salataAuto === 'szt.' && $ogorekAuto === 'kg' && $koperekAuto === 'pÄ™czek' && $arbuzAuto === 'szt.');
if (!$passAuto) {
    echo "[FAILED] BĹ‚Ä…d inteligentnej detekcji domyĹ›lnych jednostek\n";
} else {
    echo "[OK] Inteligentna detekcja dziaĹ‚a bezbĹ‚Ä™dnie!\n";
}

$allOk = $passDetect && $passSalata && $passAuto;
echo $allOk ? "=== WSZYSTKIE TESTY JEDNOSTEK PRZESZĹY ===\n" : "=== TESTY NIE PRZESZĹY ===\n";
exit($allOk ? 0 : 1);
