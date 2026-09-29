<?php
/**
 * Test weryfikujący zjednoczenie i konfigurację domyślnego importu oraz formatu ERP
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
echo "  TEST USTAWIEŃ: DOMYŚLNY IMPORT I ZUNIFIKOWANY FORMAT ERP         \n";
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

$repo = new \App\B2bRepository();

// 1. Sprawdzenie default settings w repozytorium
$settings = $repo->getAllSettings();
assertCheck(array_key_exists('default_import_method', $settings), "getAllSettings zawiera klucz default_import_method");
assertCheck(array_key_exists('default_erp_format', $settings), "getAllSettings zawiera klucz default_erp_format");

// 2. Test zapisu: Ustawienie na ERP + symfonia
$repo->setSetting('default_import_method', 'erp');
$repo->setSetting('default_erp_format', 'symfonia');

$settingsAfterErp = $repo->getAllSettings();
assertCheck($settingsAfterErp['default_import_method'] === 'erp', "Zapisano default_import_method = 'erp'");
assertCheck($settingsAfterErp['default_erp_format'] === 'symfonia', "Zapisano default_erp_format = 'symfonia'");

// 3. Renderowanie widoku views/b2b/admin.php przy ERP
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['csrf_token'] = bin2hex(random_bytes(16));

ob_start();
// Zmienne oczekiwane przez widok admin.php ($view)
$view = [
    'settings'  => $settingsAfterErp,
    'csrfToken' => $_SESSION['csrf_token'],
    'base'      => '/',
    'products'  => [],
    'orders'    => [],
    'clients'   => [],
    'activeTab' => 'products'
];
include BASE_PATH . '/views/b2b/admin.php';
$htmlErp = ob_get_clean();

// Sprawdzenie kolejności przycisków w nagłówku importu (ERP powinno być przed Excel)
$posErpBtn = strpos($htmlErp, 'id="import-tab-btn-erp"');
$posExcelBtn = strpos($htmlErp, 'id="import-tab-btn-excel"');
assertCheck($posErpBtn !== false && $posExcelBtn !== false && $posErpBtn < $posExcelBtn, 
    "Gdy default_import_method='erp': Przycisk ERP znajduje się przed przyciskiem Excel");

// Sprawdzenie widoczności paneli
assertCheck(strpos($htmlErp, 'id="import-panel-erp" role="tabpanel" aria-labelledby="import-tab-btn-erp" class="p-6 "') !== false,
    "Gdy default_import_method='erp': Panel ERP jest widoczny (brak klasy hidden)");
assertCheck(strpos($htmlErp, 'id="import-panel-excel" role="tabpanel" aria-labelledby="import-tab-btn-excel" class="p-6 hidden"') !== false,
    "Gdy default_import_method='erp': Panel Excel ma klasę hidden");

// Sprawdzenie wyboru formatu ERP w formularzu importu
assertCheck(strpos($htmlErp, '<option value="symfonia" selected>Symfonia Handel (.txt') !== false,
    "Format symfonia jest zaznaczony (selected) w selektorze importu ERP");

// Sprawdzenie zaznaczenia w sekcji Ustawienia
assertCheck(strpos($htmlErp, 'id="settings-default-import-erp" name="default_import_method" value="erp" checked') !== false,
    "W sekcji Ustawienia radio button ERP ma atrybut checked");
assertCheck(strpos($htmlErp, '<option value="symfonia" selected>Symfonia Handel (.txt)</option>') !== false,
    "W sekcji Ustawienia format symfonia ma atrybut selected");


// 4. Test zapisu: Ustawienie na Excel + subiekt
$repo->setSetting('default_import_method', 'excel');
$repo->setSetting('default_erp_format', 'subiekt');

$settingsAfterExcel = $repo->getAllSettings();
assertCheck($settingsAfterExcel['default_import_method'] === 'excel', "Zapisano default_import_method = 'excel'");
assertCheck($settingsAfterExcel['default_erp_format'] === 'subiekt', "Zapisano default_erp_format = 'subiekt'");

ob_start();
$view['settings'] = $settingsAfterExcel;
include BASE_PATH . '/views/b2b/admin.php';
$htmlExcel = ob_get_clean();

$posExcelBtn2 = strpos($htmlExcel, 'id="import-tab-btn-excel"');
$posErpBtn2 = strpos($htmlExcel, 'id="import-tab-btn-erp"');
assertCheck($posExcelBtn2 !== false && $posErpBtn2 !== false && $posExcelBtn2 < $posErpBtn2,
    "Gdy default_import_method='excel': Przycisk Excel znajduje się przed przyciskiem ERP");

assertCheck(strpos($htmlExcel, 'id="import-panel-excel" role="tabpanel" aria-labelledby="import-tab-btn-excel" class="p-6 "') !== false,
    "Gdy default_import_method='excel': Panel Excel jest widoczny (brak klasy hidden)");
assertCheck(strpos($htmlExcel, 'id="import-panel-erp" role="tabpanel" aria-labelledby="import-tab-btn-erp" class="p-6 hidden"') !== false,
    "Gdy default_import_method='excel': Panel ERP ma klasę hidden");

assertCheck(strpos($htmlExcel, 'id="settings-default-import-excel" name="default_import_method" value="excel" checked') !== false,
    "W sekcji Ustawienia radio button Excel ma atrybut checked");


// 5. Weryfikacja funkcji synchronizacji w JS
assertCheck(strpos($htmlExcel, 'function syncErpFormatSelection(') !== false,
    "Widok zawiera JS syncErpFormatSelection() synchronizujący format z Ustawień do Importu");
assertCheck(strpos($htmlExcel, 'function syncErpFormatFromImport(') !== false,
    "Widok zawiera JS syncErpFormatFromImport() synchronizujący format z Importu do Ustawień");
assertCheck(strpos($htmlExcel, 'function reorderImportTabs(') !== false,
    "Widok zawiera JS reorderImportTabs() fizycznie przestawiający taby w DOM");

// 6. Test integracyjny przez HTTP z formularzem zapisu ustawień
$cookieFile = tempnam(sys_get_temp_dir(), 'cook_settings_test_');
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);

function httpReq($url, $post = null, $headers = []) {
    global $ch;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $finalHeaders = array_merge(['Expect:'], !empty($headers) ? $headers : []);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $finalHeaders);
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($post) ? http_build_query($post) : $post);
    } else {
        curl_setopt($ch, CURLOPT_HTTPGET, true);
    }
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    return ['code' => $code, 'body' => $body];
}

// Logowanie
$resLoginGet = httpReq('http://localhost/home/login');
preg_match('/name="_csrf"\s+value="([^"]+)"/', $resLoginGet['body'], $m);
$loginCsrf = $m[1] ?? '';

httpReq('http://localhost/home/login', [
    'login'    => defined('APP_LOGIN') ? APP_LOGIN : 'admin',
    'password' => 'admin123',
    '_csrf'    => $loginCsrf
]);

// Pobranie panelu i CSRF
$resAdmin = httpReq('http://localhost/b2b/admin');
preg_match('/const CSRF_TOKEN\s*=\s*\'([^\']+)\'/', $resAdmin['body'], $m2);
$adminCsrf = $m2[1] ?? '';

// Zapisanie ustawień przez HTTP POST: default_import_method = 'erp', default_erp_format = 'optima'
$resSave = httpReq('http://localhost/b2b/savesettings', [
    '_csrf' => $adminCsrf,
    'cutoff_time' => '21:30',
    'delivery_days' => 'mon,tue,wed,thu,fri',
    'default_import_method' => 'erp',
    'default_erp_format' => 'optima',
    'finalize_action' => 'print',
    'finalize_erp_format' => 'default'
], ['X-Requested-With: XMLHttpRequest']);

$saveJson = json_decode($resSave['body'], true);
assertCheck(isset($saveJson['ok']) && $saveJson['ok'] === true, "POST /b2b/savesettings zwrócił ok=true");

$savedSettings = $repo->getAllSettings();
assertCheck($savedSettings['default_import_method'] === 'erp', "Baza danych: po zapisie default_import_method = 'erp'");
assertCheck($savedSettings['default_erp_format'] === 'optima', "Baza danych: po zapisie default_erp_format = 'optima'");

// Sprawdzenie czy po odświeżeniu /b2b/admin przez HTTP ERP jest pierwszy
$resAdminAfter = httpReq('http://localhost/b2b/admin');
$pErp = strpos($resAdminAfter['body'], 'id="import-tab-btn-erp"');
$pExcel = strpos($resAdminAfter['body'], 'id="import-tab-btn-excel"');
assertCheck($pErp !== false && $pExcel !== false && $pErp < $pExcel, "HTTP GET /b2b/admin: zakładka ERP jest pierwsza w kodzie HTML strony");
assertCheck(strpos($resAdminAfter['body'], '<option value="optima" selected>Comarch ERP Optima') !== false, "HTTP GET /b2b/admin: format optima ma selected w selektorze importu");

// Przywrócenie domyślnych dla czystości
$repo->setSetting('default_import_method', 'excel');
$repo->setSetting('default_erp_format', 'subiekt');

curl_close($ch);
@unlink($cookieFile);

echo "\n====================================================================\n";
echo "  WYNIK TESTU: PASS = {$pass}, FAIL = {$fail}\n";
echo "====================================================================\n";

if ($fail > 0) {
    exit(1);
}
