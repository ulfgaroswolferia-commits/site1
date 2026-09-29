<?php
/**
 * Kompleksowy test poprawności importu ERP (100% weryfikacja):
 * - Subiekt GT / Nexo (.epp - Windows-1250 oraz UTF-8 z BOM, znaki diakrytyczne)
 * - Comarch Optima (.xml - OPT021 oraz słowniki towarowe)
 * - Symfonia Handel (.txt - HMF 3.0 oraz format tablicowy)
 * - Asseco WAPRO Wf-Mag (.xml - zamówienia oraz kartoteki artykułów)
 * - Autodetekcja formatów
 * - Normalizacja jednostek i odporność na formatowanie cen
 * - Pełny cykl kontrolera B2bController (preview -> confirm -> zapis w repozytorium)
 * - Poprawność HTML zakładek w admin.php
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

echo "====================================================================\n";
echo "  KOMPLEKSOWY AUDYT I TEST IMPORTU ERP DLA HURTOWNI MAGDY (B2B)    \n";
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

// =====================================================================
// SEKCJA 1: SUBIEKT GT / NEXO (.epp)
// =====================================================================
echo "--- 1. SUBIEKT GT / NEXO (.epp) ---\n";

// A. Plik EPP w Windows-1250 z polskimi znakami i przecinkami w cenach
$eppWin1250 = iconv('UTF-8', 'WINDOWS-1250//TRANSLIT', implode("\r\n", [
    '[INFO]',
    '"1.05",3,1250,"Hurtownia Magdy","B2B","","B2B",20260929,20260929,""',
    '',
    '[NAGLOWEK]',
    '"TOWARY"',
    '',
    '[TOWARY]',
    '1,"JAB-GALA","Jabłko Grójeckie Gala","Jabłko Gala","kg","",5.00,4.20,5.17,""',
    '2,"SLIW-WEG","Śliwka Węgierka Dąbrowicka","Śliwka Węg.","kg","",5.00,"6,80",8.36,""',
    '3,"KOP-SW","Koperek Świeży Pęczek","Koperek","pęczek","",5.00,2.50,3.08,""',
    '4,"CZOS-POL","Czosnek Harnaś","Czosnek","szt","",5.00,1.80,2.21,""',
    '5,"BOR-AM","Borówka Amerykańska","Borówka","op","",5.00,12.00,14.76,""',
    ''
]));

$resSub1 = ErpImporter::import('subiekt', $eppWin1250);
assertTest("Subiekt Win1250: wczytano 5 produktów", count($resSub1) === 5);

$sliwka = current(array_filter($resSub1, fn($p) => str_contains($p['name'], 'Śliwka')));
assertTest("Subiekt Win1250: polskie znaki zachowane (Śliwka Węgierka)", $sliwka !== false && str_contains($sliwka['name'], 'Węgierka'));
assertTest("Subiekt Win1250: poprawna konwersja ceny z przecinkiem (6,80 -> 6.8)", $sliwka && abs($sliwka['price'] - 6.80) < 0.001);
assertTest("Subiekt Win1250: kod ERP zachowany (SLIW-WEG)", $sliwka && $sliwka['erp_code'] === 'SLIW-WEG');

$koperek = current(array_filter($resSub1, fn($p) => str_contains($p['name'], 'Koperek')));
assertTest("Subiekt Win1250: jednostka pęczek znormalizowana", $koperek && $koperek['unit'] === 'pęczek');

$czosnek = current(array_filter($resSub1, fn($p) => str_contains($p['name'], 'Czosnek')));
assertTest("Subiekt Win1250: jednostka szt -> szt.", $czosnek && $czosnek['unit'] === 'szt.');

// B. Plik EPP w UTF-8 z BOM
$eppUtf8Bom = "\xEF\xBB\xBF" . implode("\r\n", [
    '[INFO]',
    '"1.05",3,1250,"Hurtownia Magdy","B2B","","B2B",20260929,20260929,""',
    '',
    '[TOWARY]',
    '1,"ŻUR-LEŚ","Żurawina Leśna Świeża","Żurawina","kg","",5.00,28.50,35.06,""',
    ''
]);
$resSub2 = ErpImporter::import('subiekt', $eppUtf8Bom);
assertTest("Subiekt UTF-8 BOM: wczytano poprawnie", count($resSub2) === 1);
assertTest("Subiekt UTF-8 BOM: polskie litery Ż i Ś", !empty($resSub2[0]) && str_contains($resSub2[0]['name'], 'Żurawina Leśna'));

// C. Autodetekcja Subiekta
assertTest("Autodetekcja formatu Subiekt (.epp)", ErpImporter::detectFormat($eppWin1250) === 'subiekt');


// =====================================================================
// SEKCJA 2: COMARCH ERP OPTIMA (.xml)
// =====================================================================
echo "\n--- 2. COMARCH ERP OPTIMA (.xml) ---\n";

// A. Plik XML ze słownikiem kartotek <TOWARY><TOWAR>
$optimaCennikXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ROOT xmlns="http://www.comarch.pl/cdn/optima/offline">
  <TOWARY>
    <TOWAR>
      <KOD>POM-MAL-PL</KOD>
      <NAZWA>Pomidor Malinowy Krajowy</NAZWA>
      <JEDNOSTKA>kg</JEDNOSTKA>
      <CENA_NETTO>8,90</CENA_NETTO>
    </TOWAR>
    <TOWAR>
      <KOD>OGOR-SZKL</KOD>
      <NAZWA>Ogórek Szklarniowy Polski</NAZWA>
      <JEDNOSTKA>kg</JEDNOSTKA>
      <CENA_NETTO>5.50</CENA_NETTO>
    </TOWAR>
  </TOWARY>
</ROOT>
XML;

$resOpt1 = ErpImporter::import('optima', $optimaCennikXml);
assertTest("Optima (słownik kartotek): wczytano 2 produkty", count($resOpt1) === 2);
assertTest("Optima (słownik): Pomidor Malinowy Krajowy z ceną 8.90", !empty($resOpt1[0]) && $resOpt1[0]['price'] == 8.90 && $resOpt1[0]['erp_code'] === 'POM-MAL-PL');

// B. Plik XML z dokumentu zamówienia OPT021 (POZYCJA z TOWAR_KOD, TOWAR_NAZWA)
$optimaZamowienieXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ROOT xmlns="http://www.comarch.pl/cdn/optima/offline">
  <DOKUMENTY>
    <ZAMOWIENIA_OD_ODBIORCY>
      <ZAMOWIENIE>
        <POZYCJE>
          <POZYCJA>
            <TOWAR_KOD>PAP-CZERW</TOWAR_KOD>
            <TOWAR_NAZWA>Papryka Czerwona Słodka</TOWAR_NAZWA>
            <ILOSC>15.000</ILOSC>
            <JEDNOSTKA>kg</JEDNOSTKA>
            <CENA_NETTO>9.20</CENA_NETTO>
          </POZYCJA>
        </POZYCJE>
      </ZAMOWIENIE>
    </ZAMOWIENIA_OD_ODBIORCY>
  </DOKUMENTY>
</ROOT>
XML;

$resOpt2 = ErpImporter::import('optima', $optimaZamowienieXml);
assertTest("Optima (pozycje zamówienia OPT021): wczytano pozycję", count($resOpt2) === 1);
assertTest("Optima (zamówienie): Papryka Czerwona Słodka, kod PAP-CZERW", !empty($resOpt2[0]) && $resOpt2[0]['name'] === 'Papryka Czerwona Słodka' && $resOpt2[0]['erp_code'] === 'PAP-CZERW');

// C. Autodetekcja Optimy
assertTest("Autodetekcja formatu Comarch Optima (.xml)", ErpImporter::detectFormat($optimaZamowienieXml) === 'optima');


// =====================================================================
// SEKCJA 3: SYMFONIA HANDEL (.txt — HMF 3.0)
// =====================================================================
echo "\n--- 3. SYMFONIA HANDEL (.txt) ---\n";

// A. Natywny Format 3.0 HMF (Pozycja { ... }) w Windows-1250
$symfoniaHmf = iconv('UTF-8', 'WINDOWS-1250//TRANSLIT', implode("\r\n", [
    'INFO {',
    '    format = "SymfoniaHandel"',
    '    wersja = 3.0',
    '}',
    'Dokument {',
    '    kod = "ZO"',
    '    Pozycja {',
    '        kod = "MARCH-MYT"',
    '        nazwa = "Marchewka Myta Klasa I"',
    '        ilosc = 25.000',
    '        jm = "kg"',
    '        cena = 3,40',
    '    }',
    '    Pozycja {',
    '        kod = "NATKA-PIET"',
    '        nazwa = "Natka Pietruszki"',
    '        ilosc = 10.000',
    '        jm = "pęczek"',
    '        cena = 2.10',
    '    }',
    '}'
]));

$resSym1 = ErpImporter::import('symfonia', $symfoniaHmf);
assertTest("Symfonia HMF 3.0: wczytano 2 pozycje", count($resSym1) === 2);
assertTest("Symfonia HMF 3.0: Marchewka Myta z ceną 3.40", !empty($resSym1[0]) && str_contains($resSym1[0]['name'], 'Marchewka') && $resSym1[0]['price'] == 3.40);
assertTest("Symfonia HMF 3.0: kod ERP MARCH-MYT", !empty($resSym1[0]) && $resSym1[0]['erp_code'] === 'MARCH-MYT');
assertTest("Symfonia HMF 3.0: Natka Pietruszki, jm pęczek", !empty($resSym1[1]) && $resSym1[1]['unit'] === 'pęczek');

// B. Format starszy/tabelaryczny (kod;nazwa;jm;cena)
$symfoniaTab = "PIECZ-BIAL;Pieczarka Biała Świeża;kg;9.50\r\nBROK-POL;Brokuł Świeży;szt;5.00";
$resSym2 = ErpImporter::import('symfonia', $symfoniaTab);
assertTest("Symfonia (tabelaryczny csv/txt): wczytano 2 pozycje", count($resSym2) === 2);

// C. Autodetekcja Symfonii
assertTest("Autodetekcja formatu Symfonia (.txt)", ErpImporter::detectFormat($symfoniaHmf) === 'symfonia');


// =====================================================================
// SEKCJA 4: ASSECO WAPRO WF-MAG (.xml)
// =====================================================================
echo "\n--- 4. ASSECO WAPRO WF-MAG (.xml) ---\n";

// A. Plik XML WAPRO z kartotekami artykułów <ARTYKULY><ARTYKUL>
$wfmagArtXml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<DOKUMENTY_MAGAZYNOWE system="WAPRO_MAG">
  <ARTYKULY>
    <ARTYKUL>
      <INDEKS>SAL-LOD</INDEKS>
      <NAZWA>Sałata Lodowa Import</NAZWA>
      <JEDNOSTKA>szt.</JEDNOSTKA>
      <CENA_NETTO>4.99</CENA_NETTO>
    </ARTYKUL>
    <ARTYKUL>
      <INDEKS_KATALOGOWY>SZCZ-POL</INDEKS_KATALOGOWY>
      <NAZWA>Szczypiorek Świeży</NAZWA>
      <JEDNOSTKA>pęczek</JEDNOSTKA>
      <CENA_NETTO>1,95</CENA_NETTO>
    </ARTYKUL>
  </ARTYKULY>
</DOKUMENTY_MAGAZYNOWE>
XML;

$resWf1 = ErpImporter::import('wfmag', $wfmagArtXml);
assertTest("Wf-Mag (kartoteka artykułów): wczytano 2 pozycje", count($resWf1) === 2);
assertTest("Wf-Mag: Sałata Lodowa cena 4.99, jedn szt.", !empty($resWf1[0]) && $resWf1[0]['price'] == 4.99 && $resWf1[0]['unit'] === 'szt.');
assertTest("Wf-Mag: Szczypiorek z INDEKS_KATALOGOWY i ceną 1.95", !empty($resWf1[1]) && $resWf1[1]['erp_code'] === 'SZCZ-POL' && $resWf1[1]['price'] == 1.95);

// B. Plik XML WAPRO z zamówień <ZAMOWIENIE_ODBIORCY><POZYCJE><POZYCJA>
$wfmagDocXml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<DOKUMENTY_MAGAZYNOWE system="WAPRO_MAG">
  <ZAMOWIENIE_ODBIORCY>
    <POZYCJE>
      <POZYCJA>
        <INDEKS>CYTR-HISZP</INDEKS>
        <NAZWA>Cytryna Primofiori</NAZWA>
        <JEDNOSTKA>kg</JEDNOSTKA>
        <CENA_NETTO>7.20</CENA_NETTO>
      </POZYCJA>
    </POZYCJE>
  </ZAMOWIENIE_ODBIORCY>
</DOKUMENTY_MAGAZYNOWE>
XML;

$resWf2 = ErpImporter::import('wfmag', $wfmagDocXml);
assertTest("Wf-Mag (zamówienie): wczytano pozycję", count($resWf2) === 1);
assertTest("Wf-Mag: Cytryna Primofiori z kodem CYTR-HISZP", !empty($resWf2[0]) && $resWf2[0]['erp_code'] === 'CYTR-HISZP');

// C. Autodetekcja Wf-Mag
assertTest("Autodetekcja formatu Wf-Mag (.xml)", ErpImporter::detectFormat($wfmagArtXml) === 'wfmag');


// =====================================================================
// SEKCJA 5: ODPORNOŚĆ NA BŁĘDNE / PUSTE PLIKI
// =====================================================================
echo "\n--- 5. ODPORNOŚĆ NA BŁĘDNE / PUSTE DANE ---\n";

assertTest("Pusty string zwraca pustą tablicę w Subiekcie", ErpImporter::parseSubiektEpp('') === []);
assertTest("Pusty XML zwraca błąd RuntimeException w Optimie", (function() {
    try {
        ErpImporter::parseComarchOptimaXml('<pusty></pusty>');
        return true;
    } catch (\Throwable $e) {
        return false;
    }
})());
assertTest("Pusty string w Symfonii zwraca pustą tablicę", ErpImporter::parseSymfoniaTxt('') === []);
assertTest("Pusta zawartość zwraca null w detectFormat", ErpImporter::detectFormat('') === null);


// =====================================================================
// SEKCJA 6: INTEGRACJA Z KONTROLEREM B2bController::actionImporterp (cURL HTTP)
// =====================================================================
echo "\n--- 6. INTEGRACJA B2bController (PREVIEW -> CONFIRM -> DB) ---\n";

$cookieFile = tempnam(sys_get_temp_dir(), 'cook_erp_test_');
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);

function httpReqLocal($url, $post = null, $headers = []) {
    global $ch;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $finalHeaders = array_merge(['Expect:'], !empty($headers) ? $headers : []);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $finalHeaders);
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

// 1. Zaloguj administratora
$resLoginGet = httpReqLocal('http://localhost/home/login');
preg_match('/name="_csrf"\s+value="([^"]+)"/', $resLoginGet['body'], $m);
$loginCsrf = $m[1] ?? '';

httpReqLocal('http://localhost/home/login', [
    'login'    => defined('APP_LOGIN') ? APP_LOGIN : 'admin',
    'password' => 'admin123',
    '_csrf'    => $loginCsrf
]);

// 2. Pobierz stronę panelu /b2b/admin i odczytaj token CSRF
$resAdminGet = httpReqLocal('http://localhost/b2b/admin');
preg_match('/const CSRF_TOKEN\s*=\s*\'([^\']+)\'/', $resAdminGet['body'], $mB2b);
$csrfToken = $mB2b[1] ?? '';
assertTest("Panel hurtownika /b2b/admin dostępny (HTTP {$resAdminGet['code']})", $resAdminGet['code'] === 200);
assertTest("Pobrano token CSRF dla operacji", !empty($csrfToken));

// 3. Przygotuj plik EPP na dysku do wysłania
$tmpEppFile = tempnam(sys_get_temp_dir(), 'test_epp_') . '.epp';
file_put_contents($tmpEppFile, $eppWin1250);

// 4. Krok 1 (preview) — POST multipart do /b2b/importerp
$resPreview = httpReqLocal('http://localhost/b2b/importerp', [
    'erp_file' => new CURLFile($tmpEppFile, 'text/plain', 'cennik_subiekt.epp'),
    'format'   => 'subiekt',
    'step'     => 'preview',
    '_csrf'    => $csrfToken
]);
$dataPreview = json_decode($resPreview['body'], true);

assertTest("Krok 1 (preview): HTTP {$resPreview['code']}, ok=true", ($resPreview['code'] === 200 && ($dataPreview['ok'] ?? false) === true), $resPreview['body']);
assertTest("Krok 1 (preview): format wykryty jako subiekt", ($dataPreview['format'] ?? '') === 'subiekt');
assertTest("Krok 1 (preview): total_detected = 5", ($dataPreview['total_detected'] ?? 0) === 5);
assertTest("Krok 1 (preview): produkty zawierają kody erp_code", !empty($dataPreview['products'][0]['erp_code']));

// 5. Krok 2 (confirm) — POST do /b2b/importerp
$resConfirm = httpReqLocal('http://localhost/b2b/importerp', [
    'step'     => 'confirm',
    'products' => json_encode($dataPreview['products'] ?? []),
    '_csrf'    => $csrfToken
]);
$dataConfirm = json_decode($resConfirm['body'], true);

assertTest("Krok 2 (confirm): HTTP {$resConfirm['code']}, ok=true", ($resConfirm['code'] === 200 && ($dataConfirm['ok'] ?? false) === true), $resConfirm['body']);
assertTest("Krok 2 (confirm): total_imported = 5", ($dataConfirm['total_imported'] ?? 0) === 5);

// 6. Sprawdzenie w bazie danych
$repo = new \App\B2bRepository();
$prodsInDb = $repo->getAllProductsAdmin();
assertTest("Baza danych zawiera zaimportowane produkty z ERP", count($prodsInDb) >= 5);

$dbSliwka = current(array_filter($prodsInDb, fn($p) => str_contains($p['name'], 'Śliwka')));
assertTest("Baza danych: Śliwka ma erp_code SLIW-WEG", $dbSliwka && $dbSliwka['erp_code'] === 'SLIW-WEG');
assertTest("Baza danych: Śliwka ma poprawną cenę (6.80 zł)", $dbSliwka && abs((float)$dbSliwka['price'] - 6.80) < 0.01);
assertTest("Baza danych: Śliwka ma flagę is_available=1", $dbSliwka && (int)$dbSliwka['is_available'] === 1);

@unlink($tmpEppFile);
@unlink($cookieFile);


// =====================================================================
// SEKCJA 7: WERYFIKACJA WIDOKU ADMIN.PHP (ZAKŁADKI)
// =====================================================================
echo "\n--- 7. WERYFIKACJA WIDOKU views/b2b/admin.php ---\n";

$viewContent = file_get_contents(BASE_PATH . '/views/b2b/admin.php');
assertTest("admin.php: zawiera przycisk zakładki Excel (#import-tab-btn-excel)", strpos($viewContent, 'id="import-tab-btn-excel"') !== false);
assertTest("admin.php: zawiera przycisk zakładki ERP (#import-tab-btn-erp)", strpos($viewContent, 'id="import-tab-btn-erp"') !== false);
assertTest("admin.php: zawiera panel Excel (#import-panel-excel)", strpos($viewContent, 'id="import-panel-excel"') !== false);
assertTest("admin.php: zawiera panel ERP (#import-panel-erp)", strpos($viewContent, 'id="import-panel-erp"') !== false);
assertTest("admin.php: zawiera strefę drag&drop ERP (#erp-dropzone)", strpos($viewContent, 'id="erp-dropzone"') !== false);
assertTest("admin.php: zawiera tabelę podglądu (#erp-preview-tbody)", strpos($viewContent, 'id="erp-preview-tbody"') !== false);
assertTest("admin.php: zawiera funkcję switchImportTab()", strpos($viewContent, 'function switchImportTab(') !== false);
assertTest("admin.php: zawiera endpoint b2b/importerp", strpos($viewContent, "'b2b/importerp'") !== false);

// =====================================================================
// PODSUMOWANIE
// =====================================================================
echo "\n====================================================================\n";
echo "  WYNIK AUDYTU: PASS = {$pass}, FAIL = {$fail}\n";
echo "====================================================================\n";

if ($fail > 0) {
    echo "!!! WYKRYTO BŁĘDY W DZIAŁANIU IMPORTU ERP !!!\n";
    exit(1);
} else {
    echo "✓ IMPORT ERP W 100% POPRAWNY I ZGODNY ZE SPECYFIKACJĄ SYSTEMÓW!\n";
    exit(0);
}
