<?php
/**
 * Test TDD: Konfiguracja akcji przycisku "Finalizuj zamówienie" w panelu hurtownika
 */
define('BASE_PATH', 'C:/laragon/www');
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';
require_once BASE_PATH . '/program/config/includes.php';

use App\B2bRepository;

echo "=== TEST: Konfiguracja akcji przycisku Finalizacja zamówienia ===\n";

$repo = new B2bRepository();

// 1. Sprawdzenie domyślnych wartości w bazie
$finalizeAction = $repo->getSetting('finalize_action', 'print');
$finalizeErp = $repo->getSetting('finalize_erp_format', 'default');
echo "1. Domyślna akcja finalizacji: '{$finalizeAction}' (oczekiwano: print): " . ($finalizeAction === 'print' ? "PASS" : "FAIL") . "\n";
assert($finalizeAction === 'print', "Błędna domyślna akcja finalizacji");

// 2. Test zapisu przez API POST /b2b/savesettings
$cookieAdmin = tempnam(sys_get_temp_dir(), 'cook_fin_');
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieAdmin);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieAdmin);

// Logowanie
curl_setopt($ch, CURLOPT_URL, 'http://localhost/home/login');
$loginHtml = curl_exec($ch);
preg_match('/name="_csrf" value="([^"]+)"/', $loginHtml, $mCsrf);
$csrf = $mCsrf[1] ?? '';

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'login'    => APP_LOGIN,
    'password' => APP_PASSWORD,
    '_csrf'    => $csrf
]));
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_exec($ch);

// Pobranie panelu i tokenu CSRF
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, []);
curl_setopt($ch, CURLOPT_URL, 'http://localhost/b2b/admin');
$adminHtml = curl_exec($ch);
preg_match('/const CSRF_TOKEN\s*=\s*\'([^\']+)\';/', $adminHtml, $mAdminCsrf);
$adminCsrf = $mAdminCsrf[1] ?? '';

// Test zapisu akcji 'excel'
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_URL, 'http://localhost/b2b/savesettings');
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'cutoff_time'         => '21:30',
    'delivery_days'       => 'mon,tue,wed,thu,fri,sat',
    'default_erp_format'  => 'subiekt',
    'finalize_action'     => 'excel',
    'finalize_erp_format' => 'default',
    '_csrf'               => $adminCsrf
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Requested-With: XMLHttpRequest', 'Accept: application/json']);
$resp1 = curl_exec($ch);
$json1 = json_decode($resp1, true);
$okExcel = (!empty($json1['ok']) && $repo->getSetting('finalize_action') === 'excel');
echo "2. Zapis akcji 'excel': " . ($okExcel ? "PASS" : "FAIL") . "\n";

// Test zapisu akcji 'erp' z formatem 'optima'
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'cutoff_time'         => '21:30',
    'delivery_days'       => 'mon,tue,wed,thu,fri,sat',
    'default_erp_format'  => 'subiekt',
    'finalize_action'     => 'erp',
    'finalize_erp_format' => 'optima',
    '_csrf'               => $adminCsrf
]));
$resp2 = curl_exec($ch);
$json2 = json_decode($resp2, true);
$okErp = (!empty($json2['ok']) && $repo->getSetting('finalize_action') === 'erp' && $repo->getSetting('finalize_erp_format') === 'optima');
echo "3. Zapis akcji 'erp' z formatem 'optima': " . ($okErp ? "PASS" : "FAIL") . "\n";

// 3. Sprawdzenie obecności kontrolek w widoku panelu admina
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, []);
curl_setopt($ch, CURLOPT_URL, 'http://localhost/b2b/admin?tab=settings');
$settingsHtml = curl_exec($ch);

$hasRadioPrint = (strpos($settingsHtml, 'name="finalize_action"') !== false && strpos($settingsHtml, 'value="print"') !== false);
$hasRadioExcel = (strpos($settingsHtml, 'value="excel"') !== false);
$hasRadioErp   = (strpos($settingsHtml, 'value="erp"') !== false);
$hasErpSelect  = (strpos($settingsHtml, 'name="finalize_erp_format"') !== false);
$hasSubtext    = (strpos($settingsHtml, 'btn-modal-finalize-subtext') !== false);

echo "4. Kontrolki wyboru akcji finalizacji w HTML panelu: " . (($hasRadioPrint && $hasRadioExcel && $hasRadioErp && $hasErpSelect) ? "PASS" : "FAIL") . "\n";
echo "5. Dynamiczny opis przycisku w oknie modalnym (btn-modal-finalize-subtext): " . ($hasSubtext ? "PASS" : "FAIL") . "\n";

curl_close($ch);
@unlink($cookieAdmin);

// Przywrócenie domyślnych
$repo->setSetting('finalize_action', 'print');
$repo->setSetting('finalize_erp_format', 'default');

if (!$okExcel || !$okErp || !$hasRadioPrint || !$hasRadioExcel || !$hasRadioErp || !$hasErpSelect || !$hasSubtext) {
    echo "=== TEST ZAKOŃCZONY BŁĘDEM (Stan RED) ===\n";
    exit(1);
}

echo "=== TEST ZAKOŃCZONY SUKCESEM (Stan GREEN) ===\n";
