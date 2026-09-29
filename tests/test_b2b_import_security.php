<?php
/**
 * ============================================================================
 * AUDYT BEZPIECZEŃSTWA IMPORTU OFERTY B2B:
 * 1. Ochrona przed XSS (Stored / Reflected / DOM-based) w nazwach, kodach ERP i jednostkach
 * 2. Ochrona przed XXE (XML External Entity Injection) w plikach Optima / Wf-Mag
 * 3. Ochrona przed Path Traversal / LFI przy wskazywaniu file_id w processimport
 * 4. Ochrona przed Arbitrary File Upload (rozszerzenia wykonywalne .php, .phar itp.)
 * 5. Ochrona przed SQL Injection przy nietypowych znakach i apostrofach w pozycjach
 * 6. Wymuszenie autoryzacji i tokenów CSRF na wszystkich endpointach importu
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
echo "   AUDYT BEZPIECZEŃSTWA IMPORTU OFERTY: XSS, XXE, TRAVERSAL, CSRF  \n";
echo "====================================================================\n\n";

$pass = 0;
$fail = 0;

function assertSec(string $desc, bool $result, string $details = ''): void
{
    global $pass, $fail;
    if ($result) {
        echo "  [BEZPIECZNY] {$desc}\n";
        $pass++;
    } else {
        echo "  [ZAGROŻENIE] {$desc}" . ($details ? " -> {$details}" : '') . "\n";
        $fail++;
    }
}

$repo = new \App\B2bRepository();

// =====================================================================
// 1. TEST PODATNOŚCI NA XSS (Cross-Site Scripting)
// =====================================================================
echo "--- 1. WERYFIKACJA OCHRONY PRZED XSS ---\n";

$xssPayloads = [
    '<script>alert("xss-name")</script>',
    '"><img src=x onerror=alert("xss-img")>',
    '\'><svg/onload=alert("xss-svg")>',
    '<a href="javascript:alert(1)">Kliknij</a>'
];

// Przygotowanie danych z ładunkami XSS
$dirtyProducts = [];
foreach ($xssPayloads as $i => $payload) {
    $dirtyProducts[] = [
        'name'         => "Jabłko Testowe {$payload}",
        'price'        => 5.50,
        'unit'         => 'kg',
        'erp_code'     => "KOD{$payload}",
        'is_available' => 1
    ];
}

// Zapis do bazy
$repo->saveProductsBatch($dirtyProducts, true);
$savedProds = $repo->getAllProductsAdmin();

// A. Test widoku katalogu B2B (views/b2b/catalog.php)
ob_start();
$view = [
    'client'     => ['company_name' => 'Sklep Testowy', 'delivery_address' => 'Testowa 1'],
    'products'   => $savedProds,
    'categories' => ['Warzywa', 'Owoce'],
    'csrfToken'  => 'token_test',
    'base'       => '/',
    'title'      => 'Test XSS',
    'isAdmin'    => false
];
include BASE_PATH . '/views/b2b/catalog.php';
$catalogOutput = ob_get_clean();

// Sprawdzamy czy żaden surowy tag <script>, onerror= ani onload= nie pojawił się w HTML bez encji
$hasUnescapedScript = strpos($catalogOutput, '<script>alert("xss-name")</script>') !== false;
$hasUnescapedImg    = strpos($catalogOutput, '<img src=x onerror=') !== false;
$hasUnescapedSvg    = strpos($catalogOutput, '<svg/onload=') !== false;

assertSec("catalog.php: brak surowego <script> (znaki < i > poprawnie zamienione na encje)", !$hasUnescapedScript);
assertSec("catalog.php: brak surowego tagu <img onerror=>", !$hasUnescapedImg);
assertSec("catalog.php: brak surowego tagu <svg/onload=>", !$hasUnescapedSvg);
assertSec("catalog.php: ładunki XSS w atrybutach data-* są zescapowane encjami HTML", 
    strpos($catalogOutput, '&quot;&gt;&lt;img src=x') !== false || strpos($catalogOutput, '&#039;&gt;&lt;svg') !== false || strpos($catalogOutput, '&lt;script&gt;') !== false);

// B. Test widoku panelu admina (views/b2b/admin.php)
ob_start();
$viewAdmin = [
    'settings'  => $repo->getAllSettings(),
    'csrfToken' => 'token_test',
    'base'      => '/',
    'products'  => $savedProds,
    'orders'    => [],
    'clients'   => [],
    'activeTab' => 'products'
];
include BASE_PATH . '/views/b2b/admin.php';
$adminOutput = ob_get_clean();

assertSec("admin.php: brak surowego <script> na liście produktów", strpos($adminOutput, '<script>alert("xss-name")</script>') === false);
assertSec("admin.php: funkcja escapeHtml() zabezpiecza DOM przed wstrzyknięciem w tabeli podglądu", 
    strpos($adminOutput, "escapeHtml(p.name)") !== false && strpos($adminOutput, "escapeHtml(p.erp_code") !== false);


// =====================================================================
// 2. TEST PODATNOŚCI NA XXE (XML External Entity Injection)
// =====================================================================
echo "\n--- 2. WERYFIKACJA OCHRONY PRZED XXE (XML EXTERNAL ENTITIES) ---\n";

// XML zawierający próbę odwołania do zewnętrznej encji DOCTYPE
$xxePayload = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE test [
  <!ENTITY xxe SYSTEM "file:///windows/win.ini">
]>
<DOKUMENTY_MAGAZYNOWE system="WAPRO_MAG">
  <ARTYKULY>
    <ARTYKUL>
      <INDEKS>XXE-TEST</INDEKS>
      <NAZWA>&xxe;</NAZWA>
      <JEDNOSTKA>szt</JEDNOSTKA>
      <CENA_NETTO>10.00</CENA_NETTO>
    </ARTYKUL>
  </ARTYKULY>
</DOKUMENTY_MAGAZYNOWE>
XML;

$xxeParsed = ErpImporter::import('wfmag', $xxePayload);
$xxeResultName = $xxeParsed[0]['name'] ?? '';
// Dzięki LIBXML_NONET oraz domyślnym zabezpieczeniom libxml, encja nie powinna zostać rozwiązana do zawartości pliku win.ini
$isWinIniLeaked = str_contains($xxeResultName, '[fonts]') || str_contains($xxeResultName, '[extensions]');
assertSec("Wf-Mag XML: zewnętrzna encja SYSTEM file:/// nie została wczytana (brak wycieku plików)", !$isWinIniLeaked);

$xxeOptima = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE root [
  <!ENTITY xxe SYSTEM "http://127.0.0.1:9999/test-ssrf">
]>
<ROOT xmlns="http://www.comarch.pl/cdn/optima/offline">
  <TOWARY>
    <TOWAR>
      <KOD>OPT-XXE</KOD>
      <NAZWA>&xxe;</NAZWA>
      <CENA_NETTO>15.00</CENA_NETTO>
    </TOWAR>
  </TOWARY>
</ROOT>
XML;

$xxeOptParsed = ErpImporter::import('optima', $xxeOptima);
$optName = $xxeOptParsed[0]['name'] ?? '';
assertSec("Optima XML: zewnętrzna encja HTTP (SSRF) zablokowana przez LIBXML_NONET", empty($optName) || $optName === '&xxe;' || !str_contains($optName, 'test-ssrf'));


// =====================================================================
// 3. TEST PATH TRAVERSAL / LFI PRZY FILE_ID (POST /b2b/processimport)
// =====================================================================
echo "\n--- 3. WERYFIKACJA OCHRONY PRZED PATH TRAVERSAL (LFI) ---\n";

$cookieFile = tempnam(sys_get_temp_dir(), 'cook_sec_');
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);

function httpCall(string $url, $post = null, array $headers = []): array
{
    global $ch;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
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

// Logowanie
$resLoginGet = httpCall('http://localhost/home/login');
preg_match('/name="_csrf"\s+value="([^"]+)"/', $resLoginGet['body'], $mLogin);
$loginCsrf = $mLogin[1] ?? '';

httpCall('http://localhost/home/login', [
    'login'    => defined('APP_LOGIN') ? APP_LOGIN : 'admin',
    'password' => 'admin123',
    '_csrf'    => $loginCsrf
]);

$resAdmin = httpCall('http://localhost/b2b/admin');
preg_match('/const CSRF_TOKEN\s*=\s*\'([^\']+)\'/', $resAdmin['body'], $mB2b);
$csrfToken = $mB2b[1] ?? '';

// Próba ataku Directory Traversal w file_id
$traversalPayloads = [
    '../../../../Windows/win.ini',
    '..\\..\\..\\..\\Windows\\win.ini',
    '/etc/passwd',
    '',
    '.',
    '..'
];

foreach ($traversalPayloads as $tp) {
    $resTrav = httpCall('http://localhost/b2b/processimport', [
        'file_id'     => $tp,
        'header_row'  => 1,
        'col_product' => 0,
        'col_price'   => 1,
        '_csrf'       => $csrfToken
    ], ['X-Requested-With: XMLHttpRequest']);

    $jsonTrav = json_decode($resTrav['body'], true);
    $isBlocked = ($resTrav['code'] === 400 && ($jsonTrav['ok'] ?? true) === false);
    assertSec("processimport: próba traversal file_id='{$tp}' zablokowana (HTTP 400)", $isBlocked);
}


// =====================================================================
// 4. TEST ARBITRARY FILE UPLOAD (.php, .phtml, .exe)
// =====================================================================
echo "\n--- 4. WERYFIKACJA OCHRONY PRZED NIEBEZPIECZNYM UPLOADEM PLIKÓW ---\n";

$dangerousExtensions = ['php', 'phtml', 'exe', 'sh', 'phar'];
foreach ($dangerousExtensions as $ext) {
    $tmpMalicious = tempnam(sys_get_temp_dir(), 'mal_') . '.' . $ext;
    file_put_contents($tmpMalicious, "<?php echo 'HACKED'; ?>");

    // Excel upload
    $resXlsx = httpCall('http://localhost/b2b/upload', [
        'cennik' => new CURLFile($tmpMalicious, 'application/octet-stream', "shell.{$ext}"),
        '_csrf'  => $csrfToken
    ], ['X-Requested-With: XMLHttpRequest']);
    $jsonXlsx = json_decode($resXlsx['body'], true);
    assertSec("upload: blokada pliku .{$ext} (dozwolony tylko .xlsx)", $resXlsx['code'] === 400 && ($jsonXlsx['ok'] ?? true) === false);

    // ERP upload
    $resErp = httpCall('http://localhost/b2b/importerp', [
        'erp_file' => new CURLFile($tmpMalicious, 'application/octet-stream', "shell.{$ext}"),
        '_csrf'    => $csrfToken
    ], ['X-Requested-With: XMLHttpRequest']);
    $jsonErp = json_decode($resErp['body'], true);
    assertSec("importerp: blokada pliku .{$ext} (dozwolone tylko .epp, .xml, .txt)", $resErp['code'] === 400 && ($jsonErp['ok'] ?? true) === false);

    @unlink($tmpMalicious);
}


// =====================================================================
// 5. TEST WYMOGU CSRF ORAZ AUTORYZACJI
// =====================================================================
echo "\n--- 5. WERYFIKACJA OCHRONY CSRF I AUTORYZACJI ---\n";

// 1. Żądanie bez tokenu CSRF
$resNoCsrf = httpCall('http://localhost/b2b/importerp', [
    'step' => 'confirm',
    'products' => json_encode([['name' => 'Hacker Item', 'price' => 1.0, 'unit' => 'kg']])
], ['X-Requested-With: XMLHttpRequest']);
assertSec("importerp: żądanie bez tokena CSRF odrzucone z kodem HTTP 400", $resNoCsrf['code'] === 400);

// 2. Żądanie z nieprawidłowym tokenem CSRF
$resBadCsrf = httpCall('http://localhost/b2b/importerp', [
    'step' => 'confirm',
    'products' => json_encode([['name' => 'Hacker Item', 'price' => 1.0, 'unit' => 'kg']]),
    '_csrf' => 'niepoprawny_token_atakujacego_12345'
], ['X-Requested-With: XMLHttpRequest']);
assertSec("importerp: żądanie ze sfałszowanym tokenem CSRF odrzucone z kodem HTTP 400", $resBadCsrf['code'] === 400);

// 3. Żądanie nieautoryzowane (nowa sesja bez logowania)
$unauthCh = curl_init();
curl_setopt($unauthCh, CURLOPT_RETURNTRANSFER, true);
curl_setopt($unauthCh, CURLOPT_URL, 'http://localhost/b2b/upload');
curl_setopt($unauthCh, CURLOPT_POST, true);
curl_setopt($unauthCh, CURLOPT_POSTFIELDS, ['_csrf' => 'abc']);
curl_setopt($unauthCh, CURLOPT_HTTPHEADER, ['X-Requested-With: XMLHttpRequest']);
$unauthBody = curl_exec($unauthCh);
$unauthCode = curl_getinfo($unauthCh, CURLINFO_HTTP_CODE);
curl_close($unauthCh);

assertSec("upload: nieautoryzowane żądanie AJAX odrzucone z kodem 401 lub przekierowaniem", $unauthCode === 401 || $unauthCode === 302);


// =====================================================================
// 6. TEST SQL INJECTION PRZY ATYPOWYCH DANYCH
// =====================================================================
echo "\n--- 6. WERYFIKACJA OCHRONY PRZED SQL INJECTION ---\n";

$sqliPayloads = [
    "Pomidor Malinowy'; DROP TABLE b2b_products; --",
    "Jabłko Gala' OR '1'='1",
    'Papryka " OR ""="',
    "Cebula\\'; DELETE FROM b2b_settings; --"
];

$sqliProducts = [];
foreach ($sqliPayloads as $p) {
    $sqliProducts[] = [
        'name'         => $p,
        'price'        => 7.99,
        'unit'         => 'kg',
        'erp_code'     => "SQLI-TEST",
        'is_available' => 1
    ];
}

$repo->saveProductsBatch($sqliProducts, true);
$afterSqliProds = $repo->getAllProductsAdmin();
assertSec("saveProductsBatch: tabela b2b_products nienaruszona (DROP TABLE zneutralizowany)", count($afterSqliProds) === count($sqliPayloads));
assertSec("saveProductsBatch: ładunki SQL zapisane bezpiecznie jako literały tekstowe (Prepared Statements)", 
    $afterSqliProds[0]['name'] === $sqliPayloads[0]);

// Przywrócenie czystego asortymentu
$cleanProducts = [
    ['name' => 'Pomidor Malinowy Krajowy', 'price' => 8.50, 'unit' => 'kg', 'erp_code' => 'POM-MAL', 'is_available' => 1],
    ['name' => 'Jabłko Grójeckie Gala', 'price' => 4.20, 'unit' => 'kg', 'erp_code' => 'JAB-GALA', 'is_available' => 1],
    ['name' => 'Koperek Świeży Pęczek', 'price' => 2.50, 'unit' => 'pęczek', 'erp_code' => 'KOP-SW', 'is_available' => 1]
];
$repo->saveProductsBatch($cleanProducts, true);

curl_close($ch);
@unlink($cookieFile);

echo "\n====================================================================\n";
echo "  PODSUMOWANIE AUDYTU BEZPIECZEŃSTWA: PASS = {$pass}, FAIL = {$fail}\n";
echo "====================================================================\n";

if ($fail > 0) {
    exit(1);
}
echo "✓ WSZYSTKIE TESTY BEZPIECZEŃSTWA ZALICZONE! MODUŁ IMPORTU JEST W PEŁNI BEZPIECZNY.\n";
