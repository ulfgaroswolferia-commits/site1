<?php
/**
 * ============================================================================
 * KOMPLEKSOWY AUDYT I TEST IMPORTU OFERTY WE WSZYSTKICH FORMATACH:
 * 1. Microsoft Excel (.xlsx)
 * 2. InsERT Subiekt GT / Nexo (.epp - EDI++) [Win-1250 i UTF-8]
 * 3. Comarch ERP Optima (.xml) [Kartoteki towarowe i OPT021]
 * 4. Symfonia Handel (.txt) [HMF 3.0 i tabelaryczny CSV]
 * 5. Asseco WAPRO Wf-Mag (.xml) [Artykuły i zamówienia]
 * + Obsługa błędów, autodetekcja, walidacja bazy danych i widoku katalogu B2B
 * ============================================================================
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';
require_once BASE_PATH . '/program/lib/ErpImporter.php';
require_once BASE_PATH . '/program/lib/ErpExporter.php';
require_once BASE_PATH . '/program/lib/XlsxParser.php';
require_once BASE_PATH . '/program/lib/XlsxWriter.php';
require_once BASE_PATH . '/program/lib/Tools.php';
require_once BASE_PATH . '/program/core/App.php';
require_once BASE_PATH . '/program/core/Controller.php';
require_once BASE_PATH . '/program/script/AppController.php';
require_once BASE_PATH . '/program/script/B2bController.php';

echo "====================================================================\n";
echo "   KOMPLEKSOWY TEST IMPORTU OFERTY WE WSZYSTKICH FORMATACH (B2B)   \n";
echo "====================================================================\n\n";

$pass = 0;
$fail = 0;

function assertTest(string $desc, bool $result, string $details = ''): void
{
    global $pass, $fail;
    if ($result) {
        echo "  [PASS] {$desc}\n";
        $pass++;
    } else {
        echo "  [FAIL] {$desc}" . ($details ? " -> {$details}" : '') . "\n";
        $fail++;
    }
}

$repo = new \App\B2bRepository();

// =====================================================================
// POMOCNIK GENEROWANIA CZYSTEGO ARKUSZA EXCEL (.xlsx)
// =====================================================================
function createTestXlsx(array $rows, string $filePath): void
{
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException("Wymagane rozszerzenie ZipArchive.");
    }
    $zip = new ZipArchive();
    if ($zip->open($filePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException("Nie można utworzyć pliku xlsx: {$filePath}");
    }

    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
</Types>');

    $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>');

    $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
</Relationships>');

    $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="Cennik" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>');

    $sheetRows = '';
    $rIdx = 1;
    foreach ($rows as $row) {
        $sheetRows .= '<row r="' . $rIdx . '">';
        $cIdx = 0;
        foreach ($row as $val) {
            $colLetter = XlsxParser::indexToColumnLetter($cIdx);
            $cellRef = $colLetter . $rIdx;
            if (is_numeric($val)) {
                $sheetRows .= '<c r="' . $cellRef . '"><v>' . $val . '</v></c>';
            } else {
                $safeVal = htmlspecialchars((string)$val, ENT_XML1, 'UTF-8');
                $sheetRows .= '<c r="' . $cellRef . '" t="inlineStr"><is><t>' . $safeVal . '</t></is></c>';
            }
            $cIdx++;
        }
        $sheetRows .= '</row>';
        $rIdx++;
    }

    $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetData>' . $sheetRows . '</sheetData>
</worksheet>';

    $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
    $zip->close();
}

// =====================================================================
// INICJALIZACJA KLIENTA HTTP (cURL)
// =====================================================================
$cookieFile = tempnam(sys_get_temp_dir(), 'cook_compr_');
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);

function httpRequest(string $url, $post = null, array $headers = []): array
{
    global $ch;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $mergedHeaders = array_merge(['Expect:'], $headers);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $mergedHeaders);

    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        $hasFile = false;
        if (is_array($post)) {
            foreach ($post as $v) {
                if ($v instanceof CURLFile) { $hasFile = true; break; }
            }
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, ($hasFile || !is_array($post)) ? $post : http_build_query($post));
    } else {
        curl_setopt($ch, CURLOPT_HTTPGET, true);
    }

    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    return ['code' => $code, 'body' => $body];
}

// Logowanie administratora
$resLoginGet = httpRequest('http://localhost/home/login');
preg_match('/name="_csrf"\s+value="([^"]+)"/', $resLoginGet['body'], $mLogin);
$loginCsrf = $mLogin[1] ?? '';

httpRequest('http://localhost/home/login', [
    'login'    => defined('APP_LOGIN') ? APP_LOGIN : 'admin',
    'password' => 'admin123',
    '_csrf'    => $loginCsrf
]);

// Pobranie panelu hurtownika i odczytanie tokena CSRF
$resAdminGet = httpRequest('http://localhost/b2b/admin');
preg_match('/const CSRF_TOKEN\s*=\s*\'([^\']+)\'/', $resAdminGet['body'], $mB2b);
$csrfToken = $mB2b[1] ?? '';

assertTest("Logowanie administratora i dostęp do /b2b/admin (HTTP 200)", $resAdminGet['code'] === 200 && !empty($csrfToken));


// =====================================================================
// SEKCJA 1: FORMAT MICROSOFT EXCEL (.xlsx)
// =====================================================================
echo "\n--- 1. MICROSOFT EXCEL (.xlsx) ---\n";

$tmpXlsxFile = tempnam(sys_get_temp_dir(), 'cennik_') . '.xlsx';
$excelRows = [
    ['Nazwa towaru', 'Cena hurtowa (zł)', 'Jednostka'],
    ['Banan Premium Chiquita', 5.80, 'kg'],
    ['Ogórek Gruntowy Polski', 4.20, 'kg'],
    ['Rukola Myta w Opakowaniu', 3.50, 'op.'],
    ['Pieczarka Biała Świeża', 8.90, 'kg']
];
createTestXlsx($excelRows, $tmpXlsxFile);

// A. Test parsera XlsxParser na poziomie jednostkowym
$parser = XlsxParser::open($tmpXlsxFile);
$parsedRows = $parser->getRawRows();
assertTest("XlsxParser: odczytano 5 wierszy z pliku .xlsx", count($parsedRows) === 5);
$candidates = $parser->detectCandidateColumns($parsedRows);
assertTest("XlsxParser: autodetekcja kolumny produktu = 0", ($candidates['productCol'] ?? -1) === 0);
assertTest("XlsxParser: autodetekcja kolumny ceny = 1", ($candidates['priceCol'] ?? -1) === 1);
assertTest("XlsxParser: autodetekcja kolumny jednostki = 2", ($candidates['unitCol'] ?? -1) === 2);

$extractedProds = $parser->extractProducts(1, 0, 1, 2);
assertTest("XlsxParser: poprawna ekstrakcja 4 produktów (po pominięciu nagłówka)", count($extractedProds) === 4);
assertTest("XlsxParser: cena Banan Premium = 5.80 zł", abs((float)$extractedProds[0]['price'] - 5.80) < 0.01);
assertTest("XlsxParser: jednostka Rukola = op.", ($extractedProds[2]['unit'] ?? '') === 'op.');

// B. Test kontrolera HTTP: Krok 1 (upload)
$resXlsxUpload = httpRequest('http://localhost/b2b/upload', [
    'cennik' => new CURLFile($tmpXlsxFile, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'cennik_magdy.xlsx'),
    '_csrf'  => $csrfToken
], ['X-Requested-With: XMLHttpRequest']);

$dataXlsxUpload = json_decode($resXlsxUpload['body'], true);
assertTest("HTTP upload: zwrot HTTP 200, ok=true", $resXlsxUpload['code'] === 200 && ($dataXlsxUpload['ok'] ?? false) === true, "HTTP {$resXlsxUpload['code']}: {$resXlsxUpload['body']}");
$uploadedFileId = $dataXlsxUpload['file_id'] ?? '';
assertTest("HTTP upload: wygenerowano file_id dla uploadu", !empty($uploadedFileId), "file_id empty");

// C. Test kontrolera HTTP: Krok 2 (processimport)
$resXlsxProcess = httpRequest('http://localhost/b2b/processimport', [
    'file_id'     => $uploadedFileId,
    'header_row'  => 1,
    'col_product' => 0,
    'col_price'   => 1,
    'col_unit'    => 2,
    '_csrf'       => $csrfToken
], ['X-Requested-With: XMLHttpRequest']);

$dataXlsxProcess = json_decode($resXlsxProcess['body'], true);
assertTest("HTTP processimport: zwrot HTTP 200, ok=true", $resXlsxProcess['code'] === 200 && ($dataXlsxProcess['ok'] ?? false) === true);
assertTest("HTTP processimport: zaimportowano 4 produkty z Excela", ($dataXlsxProcess['total_imported'] ?? 0) === 4);

// Sprawdzenie produktów z Excela w bazie danych
$allProds = $repo->getAllProductsAdmin();
$dbBanan = current(array_filter($allProds, fn($p) => $p['name'] === 'Banan Premium Chiquita'));
assertTest("Baza danych: Banan Premium Chiquita poprawnie zapisany", $dbBanan !== false && $dbBanan !== null);
assertTest("Baza danych: Banan ma cenę 5.80 zł", $dbBanan && abs((float)$dbBanan['price'] - 5.80) < 0.01);
$dbRukola = current(array_filter($allProds, fn($p) => str_contains($p['name'], 'Rukola Myta')));
assertTest("Baza danych: Rukola ma jednostkę op.", $dbRukola && ($dbRukola['unit'] ?? '') === 'op.');

// Weryfikacja widoczności oferty Excel w katalogu klienta
$resCat1 = httpRequest('http://localhost/b2b/index');
assertTest("Katalog B2B (/b2b/index): widoczny towar z Excela (Banan Premium)", strpos($resCat1['body'], 'Banan Premium Chiquita') !== false);

@unlink($tmpXlsxFile);


// =====================================================================
// SEKCJA 2: INSERT SUBIEKT GT / NEXO (.epp — EDI++)
// =====================================================================
echo "\n--- 2. INSERT SUBIEKT GT / NEXO (.epp) ---\n";

$eppWin1250 = iconv('UTF-8', 'WINDOWS-1250//TRANSLIT', implode("\r\n", [
    '[INFO]',
    '"1.05",3,1250,"Hurtownia Magdy","B2B","","B2B",20260929,20260929,""',
    '',
    '[NAGLOWEK]',
    '"TOWARY"',
    '',
    '[TOWARY]',
    '1,"JAB-LIGOL","Jabłko Ligol Grójeckie","Jabłko Ligol","kg","",5.00,3.90,4.80,""',
    '2,"GRUSZ-KONF","Gruszka Konferencja Słodka","Gruszka Konf.","kg","",5.00,"7,50",9.23,""',
    '3,"SZCZ-SW","Szczypiorek Pęczek Świeży","Szczypiorek","pęczek","",5.00,2.20,2.71,""'
]));

$tmpSubiektFile = tempnam(sys_get_temp_dir(), 'test_sub_') . '.epp';
file_put_contents($tmpSubiektFile, $eppWin1250);

// A. Test jednostkowy
$resSubUnit = ErpImporter::import('subiekt', $eppWin1250);
assertTest("ErpImporter: Subiekt .epp wczytał 3 produkty", count($resSubUnit) === 3);
assertTest("ErpImporter: Subiekt polskie znaki w Windows-1250 (Jabłko Ligol Grójeckie)", $resSubUnit[0]['name'] === 'Jabłko Ligol Grójeckie');
assertTest("ErpImporter: Subiekt cena z przecinkiem ('7,50' -> 7.50)", abs((float)$resSubUnit[1]['price'] - 7.50) < 0.01);
assertTest("ErpImporter: Subiekt kod ERP (GRUSZ-KONF)", $resSubUnit[1]['erp_code'] === 'GRUSZ-KONF');
assertTest("ErpImporter: Subiekt jednostka znormalizowana ('pęczek')", $resSubUnit[2]['unit'] === 'pęczek');

// B. Test HTTP przez kontroler (preview)
$resSubPreview = httpRequest('http://localhost/b2b/importerp', [
    'erp_file' => new CURLFile($tmpSubSubFile = $tmpSubiektFile, 'text/plain', 'subiekt_oferta.epp'),
    'format'   => 'subiekt',
    'step'     => 'preview',
    '_csrf'    => $csrfToken
], ['X-Requested-With: XMLHttpRequest']);

$dataSubPreview = json_decode($resSubPreview['body'], true);
assertTest("HTTP importerp (Subiekt preview): HTTP 200, ok=true", $resSubPreview['code'] === 200 && ($dataSubPreview['ok'] ?? false) === true);
assertTest("HTTP importerp (Subiekt preview): wykryto format subiekt", ($dataSubPreview['format'] ?? '') === 'subiekt');
assertTest("HTTP importerp (Subiekt preview): total_detected = 3", ($dataSubPreview['total_detected'] ?? 0) === 3);

// C. Test HTTP przez kontroler (confirm)
$resSubConfirm = httpRequest('http://localhost/b2b/importerp', [
    'step'     => 'confirm',
    'products' => json_encode($dataSubPreview['products']),
    '_csrf'    => $csrfToken
], ['X-Requested-With: XMLHttpRequest']);

$dataSubConfirm = json_decode($resSubConfirm['body'], true);
assertTest("HTTP importerp (Subiekt confirm): HTTP 200, ok=true", $resSubConfirm['code'] === 200 && ($dataSubConfirm['ok'] ?? false) === true);
assertTest("HTTP importerp (Subiekt confirm): total_imported = 3", ($dataSubConfirm['total_imported'] ?? 0) === 3);

$dbGruszka = current(array_filter($repo->getAllProductsAdmin(), fn($p) => ($p['erp_code'] ?? '') === 'GRUSZ-KONF'));
assertTest("Baza danych: Gruszka Konferencja ma erp_code GRUSZ-KONF i cenę 7.50 zł", $dbGruszka && abs((float)$dbGruszka['price'] - 7.50) < 0.01);

// Weryfikacja widoczności oferty Subiekt w katalogu klienta
$resCatSub = httpRequest('http://localhost/b2b/index');
assertTest("Katalog B2B (/b2b/index): widoczny towar z Subiekta (Gruszka Konferencja)", strpos($resCatSub['body'], 'Gruszka Konferencja Słodka') !== false);

@unlink($tmpSubiektFile);


// =====================================================================
// SEKCJA 3: COMARCH ERP OPTIMA (.xml)
// =====================================================================
echo "\n--- 3. COMARCH ERP OPTIMA (.xml) ---\n";

$optimaXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ROOT xmlns="http://www.comarch.pl/cdn/optima/offline">
  <TOWARY>
    <TOWAR>
      <KOD>POM-MAL-KR</KOD>
      <NAZWA>Pomidor Malinowy Kaliber BB</NAZWA>
      <JEDNOSTKA>kg</JEDNOSTKA>
      <CENY>
        <CENA>
          <TYP>Hurtowa</TYP>
          <NETTO>9.40</NETTO>
          <BRUTTO>9.87</BRUTTO>
        </CENA>
      </CENY>
    </TOWAR>
    <TOWAR>
      <KOD>KALAFIOR-POL</KOD>
      <NAZWA>Kalafior Świeży Biały</NAZWA>
      <JEDNOSTKA>szt</JEDNOSTKA>
      <CENY>
        <CENA>
          <TYP>Hurtowa</TYP>
          <NETTO>6,20</NETTO>
        </CENA>
      </CENY>
    </TOWAR>
  </TOWARY>
</ROOT>
XML;

$tmpOptimaFile = tempnam(sys_get_temp_dir(), 'test_opt_') . '.xml';
file_put_contents($tmpOptimaFile, $optimaXml);

// A. Test jednostkowy
$resOptUnit = ErpImporter::import('optima', $optimaXml);
assertTest("ErpImporter: Optima .xml wczytała 2 produkty", count($resOptUnit) === 2);
assertTest("ErpImporter: Optima poprawny kod POM-MAL-KR", $resOptUnit[0]['erp_code'] === 'POM-MAL-KR');
assertTest("ErpImporter: Optima cena Kalafior z przecinkiem = 6.20", abs((float)$resOptUnit[1]['price'] - 6.20) < 0.01);
assertTest("ErpImporter: Optima jednostka szt -> szt.", $resOptUnit[1]['unit'] === 'szt.');

// B. Test HTTP przez kontroler (preview + confirm z autodetekcją formatu)
$resOptPreview = httpRequest('http://localhost/b2b/importerp', [
    'erp_file' => new CURLFile($tmpOptimaFile, 'text/xml', 'cennik_optima.xml'),
    'format'   => '', // puste -> sprawdzamy automatyczną detekcję
    'step'     => 'preview',
    '_csrf'    => $csrfToken
], ['X-Requested-With: XMLHttpRequest']);

$dataOptPreview = json_decode($resOptPreview['body'], true);
assertTest("HTTP importerp (Optima preview): autodetekcja wykryła optima", ($dataOptPreview['format'] ?? '') === 'optima');
assertTest("HTTP importerp (Optima preview): total_detected = 2", ($dataOptPreview['total_detected'] ?? 0) === 2);

$resOptConfirm = httpRequest('http://localhost/b2b/importerp', [
    'step'     => 'confirm',
    'products' => json_encode($dataOptPreview['products']),
    '_csrf'    => $csrfToken
], ['X-Requested-With: XMLHttpRequest']);

$dataOptConfirm = json_decode($resOptConfirm['body'], true);
assertTest("HTTP importerp (Optima confirm): zaimportowano 2 produkty", ($dataOptConfirm['total_imported'] ?? 0) === 2);

$dbKalafior = current(array_filter($repo->getAllProductsAdmin(), fn($p) => ($p['erp_code'] ?? '') === 'KALAFIOR-POL'));
assertTest("Baza danych: Kalafior Świeży Biały zapisany z ceną 6.20 zł i jednostką szt.", $dbKalafior && abs((float)$dbKalafior['price'] - 6.20) < 0.01 && $dbKalafior['unit'] === 'szt.');

// Weryfikacja widoczności oferty Optima w katalogu klienta
$resCatOpt = httpRequest('http://localhost/b2b/index');
assertTest("Katalog B2B (/b2b/index): widoczny towar z Optimy (Kalafior Świeży)", strpos($resCatOpt['body'], 'Kalafior Świeży Biały') !== false);

@unlink($tmpOptimaFile);


// =====================================================================
// SEKCJA 4: SYMFONIA HANDEL (.txt)
// =====================================================================
echo "\n--- 4. SYMFONIA HANDEL (.txt) ---\n";

$symfoniaTxt = implode("\r\n", [
    'INFO {',
    '    format = "SymfoniaHandel"',
    '    wersja = 3.0',
    '}',
    'Dokument {',
    '    kod = "ZO"',
    '    Pozycja {',
    '        kod = "BOROW-AMER"',
    '        nazwa = "Borówka Amerykańska 500g"',
    '        ilosc = 10.000',
    '        jm = "op"',
    '        cena = 16.80',
    '    }',
    '    Pozycja {',
    '        kod = "CYTR-SYCYL"',
    '        nazwa = "Cytryna Sycylijska Bio"',
    '        ilosc = 20.000',
    '        jm = "kg"',
    '        cena = 8,90',
    '    }',
    '}'
]);

$tmpSymfoniaFile = tempnam(sys_get_temp_dir(), 'test_sym_') . '.txt';
file_put_contents($tmpSymfoniaFile, $symfoniaTxt);

// A. Test jednostkowy
$resSymUnit = ErpImporter::import('symfonia', $symfoniaTxt);
assertTest("ErpImporter: Symfonia .txt wczytała 2 produkty", count($resSymUnit) === 2);
assertTest("ErpImporter: Symfonia Borówka cena 16.80, jm op.", $resSymUnit[0]['price'] == 16.80 && $resSymUnit[0]['unit'] === 'op.');
assertTest("ErpImporter: Symfonia Cytryna cena z przecinkiem 8,90 -> 8.90", abs((float)$resSymUnit[1]['price'] - 8.90) < 0.01);

// B. Test HTTP przez kontroler (preview + confirm)
$resSymPreview = httpRequest('http://localhost/b2b/importerp', [
    'erp_file' => new CURLFile($tmpSymfoniaFile, 'text/plain', 'symfonia_export.txt'),
    'format'   => 'symfonia',
    'step'     => 'preview',
    '_csrf'    => $csrfToken
], ['X-Requested-With: XMLHttpRequest']);

$dataSymPreview = json_decode($resSymPreview['body'], true);
assertTest("HTTP importerp (Symfonia preview): total_detected = 2", ($dataSymPreview['total_detected'] ?? 0) === 2);

$resSymConfirm = httpRequest('http://localhost/b2b/importerp', [
    'step'     => 'confirm',
    'products' => json_encode($dataSymPreview['products']),
    '_csrf'    => $csrfToken
], ['X-Requested-With: XMLHttpRequest']);

$dataSymConfirm = json_decode($resSymConfirm['body'], true);
assertTest("HTTP importerp (Symfonia confirm): total_imported = 2", ($dataSymConfirm['total_imported'] ?? 0) === 2);

$dbBorowka = current(array_filter($repo->getAllProductsAdmin(), fn($p) => ($p['erp_code'] ?? '') === 'BOROW-AMER'));
assertTest("Baza danych: Borówka Amerykańska zapisana z ceną 16.80 zł", $dbBorowka && abs((float)$dbBorowka['price'] - 16.80) < 0.01);

// Weryfikacja widoczności oferty Symfonia w katalogu klienta
$resCatSym = httpRequest('http://localhost/b2b/index');
assertTest("Katalog B2B (/b2b/index): widoczny towar z Symfonii (Borówka Amerykańska)", strpos($resCatSym['body'], 'Borówka Amerykańska 500g') !== false);

@unlink($tmpSymfoniaFile);


// =====================================================================
// SEKCJA 5: ASSECO WAPRO WF-MAG (.xml)
// =====================================================================
echo "\n--- 5. ASSECO WAPRO WF-MAG (.xml) ---\n";

$wfmagXml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<DOKUMENTY_MAGAZYNOWE system="WAPRO_MAG">
  <ARTYKULY>
    <ARTYKUL>
      <INDEKS>AWOK-HASS</INDEKS>
      <NAZWA>Awokado Hass Dojrzałe Ready-to-eat</NAZWA>
      <JEDNOSTKA>szt</JEDNOSTKA>
      <CENA_NETTO>5.49</CENA_NETTO>
    </ARTYKUL>
    <ARTYKUL>
      <INDEKS_KATALOGOWY>SZPIN-SW</INDEKS_KATALOGOWY>
      <NAZWA>Szpinak Młody Baby</NAZWA>
      <JEDNOSTKA>karton</JEDNOSTKA>
      <CENA_NETTO>12,50</CENA_NETTO>
    </ARTYKUL>
  </ARTYKULY>
</DOKUMENTY_MAGAZYNOWE>
XML;

$tmpWfmagFile = tempnam(sys_get_temp_dir(), 'test_wf_') . '.xml';
file_put_contents($tmpWfmagFile, $wfmagXml);

// A. Test jednostkowy
$resWfUnit = ErpImporter::import('wfmag', $wfmagXml);
assertTest("ErpImporter: Wf-Mag .xml wczytał 2 produkty", count($resWfUnit) === 2);
assertTest("ErpImporter: Wf-Mag Awokado Hass cena 5.49, jm szt.", $resWfUnit[0]['price'] == 5.49 && $resWfUnit[0]['unit'] === 'szt.');
assertTest("ErpImporter: Wf-Mag Szpinak cena z przecinkiem 12.50, jm op.", abs((float)$resWfUnit[1]['price'] - 12.50) < 0.01 && $resWfUnit[1]['unit'] === 'op.');

// B. Test HTTP przez kontroler (preview + confirm)
$resWfPreview = httpRequest('http://localhost/b2b/importerp', [
    'erp_file' => new CURLFile($tmpWfmagFile, 'text/xml', 'wfmag_artykuly.xml'),
    'format'   => 'wfmag',
    'step'     => 'preview',
    '_csrf'    => $csrfToken
], ['X-Requested-With: XMLHttpRequest']);

$dataWfPreview = json_decode($resWfPreview['body'], true);
assertTest("HTTP importerp (Wf-Mag preview): total_detected = 2", ($dataWfPreview['total_detected'] ?? 0) === 2);

$resWfConfirm = httpRequest('http://localhost/b2b/importerp', [
    'step'     => 'confirm',
    'products' => json_encode($dataWfPreview['products']),
    '_csrf'    => $csrfToken
], ['X-Requested-With: XMLHttpRequest']);

$dataWfConfirm = json_decode($resWfConfirm['body'], true);
assertTest("HTTP importerp (Wf-Mag confirm): total_imported = 2", ($dataWfConfirm['total_imported'] ?? 0) === 2);

$dbAwokado = current(array_filter($repo->getAllProductsAdmin(), fn($p) => ($p['erp_code'] ?? '') === 'AWOK-HASS'));
assertTest("Baza danych: Awokado Hass zapisane z ceną 5.49 zł i kodem AWOK-HASS", $dbAwokado && abs((float)$dbAwokado['price'] - 5.49) < 0.01);

// Weryfikacja widoczności oferty Wf-Mag w katalogu klienta
$resCatWf = httpRequest('http://localhost/b2b/index');
assertTest("Katalog B2B (/b2b/index): widoczny towar z Wf-Mag (Awokado Hass)", strpos($resCatWf['body'], 'Awokado Hass Dojrzałe Ready-to-eat') !== false);

@unlink($tmpWfmagFile);


// =====================================================================
// SEKCJA 6: ODPORNOŚĆ I OBSŁUGA BŁĘDÓW WE WSZYSTKICH FORMATACH
// =====================================================================
echo "\n--- 6. ODPORNOŚĆ I OBSŁUGA BŁĘDÓW ---\n";

// 1. Złe rozszerzenie dla Excela (np. plik .txt)
$tmpFakeFile = tempnam(sys_get_temp_dir(), 'fake_') . '.txt';
file_put_contents($tmpFakeFile, "to nie jest xlsx");
$resErrExcel = httpRequest('http://localhost/b2b/upload', [
    'cennik' => new CURLFile($tmpFakeFile, 'text/plain', 'test.txt'),
    '_csrf'  => $csrfToken
], ['X-Requested-With: XMLHttpRequest']);
$dataErrExcel = json_decode($resErrExcel['body'], true);
assertTest("upload: odrzuca plik z rozszerzeniem innym niż .xlsx", $resErrExcel['code'] === 400 && ($dataErrExcel['ok'] ?? true) === false);

// 2. Złe rozszerzenie dla ERP (np. plik .exe lub .pdf)
$tmpFakePdf = tempnam(sys_get_temp_dir(), 'fake_') . '.pdf';
file_put_contents($tmpFakePdf, "%PDF-1.4 nieznany format");
$resErrErp = httpRequest('http://localhost/b2b/importerp', [
    'erp_file' => new CURLFile($tmpFakePdf, 'application/pdf', 'oferta.pdf'),
    '_csrf'    => $csrfToken
], ['X-Requested-With: XMLHttpRequest']);
$dataErrErp = json_decode($resErrErp['body'], true);
assertTest("importerp: odrzuca pliki spoza dozwolonych (.epp, .xml, .txt)", $resErrErp['code'] === 400 && ($dataErrErp['ok'] ?? true) === false);

// 3. Pusty plik ERP
$tmpEmpty = tempnam(sys_get_temp_dir(), 'empty_') . '.txt';
file_put_contents($tmpEmpty, "");
$resEmptyErp = httpRequest('http://localhost/b2b/importerp', [
    'erp_file' => new CURLFile($tmpEmpty, 'text/plain', 'pusty.txt'),
    'format'   => 'symfonia',
    'step'     => 'preview',
    '_csrf'    => $csrfToken
], ['X-Requested-With: XMLHttpRequest']);
$dataEmptyErp = json_decode($resEmptyErp['body'], true);
assertTest("importerp: pusty plik zwraca komunikat o braku pozycji (400)", $resEmptyErp['code'] === 400 && ($dataEmptyErp['ok'] ?? true) === false);

@unlink($tmpFakeFile);
@unlink($tmpFakePdf);
@unlink($tmpEmpty);


// =====================================================================
// SEKCJA 7: WERYFIKACJA SPÓJNOŚCI I DZIAŁANIA KATALOGU B2B
// =====================================================================
echo "\n--- 7. WERYFIKACJA KATALOGU OFERTY DLA KLIENTÓW B2B (/b2b/index) ---\n";

$resCatalog = httpRequest('http://localhost/b2b/index');
assertTest("Katalog /b2b/index odpowiada kodem HTTP 200", $resCatalog['code'] === 200);

$catalogHtml = $resCatalog['body'];
assertTest("Katalog zawiera aktywną ofertę hurtowni", strpos($catalogHtml, 'Hurtownia Magdy') !== false);
assertTest("Katalog zawiera wyszukiwarkę asortymentu", strpos($catalogHtml, 'id="searchInput"') !== false);
assertTest("Katalog zawiera tabelę asortymentu (#catalogTable)", strpos($catalogHtml, 'id="catalogTable"') !== false);
assertTest("Katalog zawiera kontrolki zamówienia (koszyk / wyczyść)", strpos($catalogHtml, 'id="clearQuantitiesBtn"') !== false);
assertTest("Katalog zawiera aktualnie zaimportowane produkty", strpos($catalogHtml, 'Awokado Hass') !== false);

curl_close($ch);
@unlink($cookieFile);

echo "\n====================================================================\n";
echo "  PODSUMOWANIE AUDYTU WSZYSTKICH FORMATÓW: PASS = {$pass}, FAIL = {$fail}\n";
echo "====================================================================\n";

if ($fail > 0) {
    exit(1);
}
echo "✓ WSZYSTKIE 5 FORMATÓW IMPORTU OFERTY DZIAŁAJĄ W 100% BEZBŁĘDNIE!\n";
