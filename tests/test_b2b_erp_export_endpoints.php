<?php
/**
 * Test TDD: Weryfikacja endpointów eksportu ERP (pojedynczy, zbiorczy) i zapisu ustawień w B2bController
 */
define('BASE_PATH', 'C:/laragon\www');
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';
require_once BASE_PATH . '/program/config/includes.php';

use App\B2bRepository;

echo "=== TEST: Endpointy eksportu ERP i panel ustawień hurtownika ===\n";

$repo = new B2bRepository();

$stmtProd = $repo->getPdo()->prepare("INSERT INTO b2b_products (name, erp_code, category, unit, price, package_size, package_unit, is_available, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)");
$stmtProd->execute(['Papryka Czerwona Test', 'PAP-CZERW-01', 'Warzywa', 'kg', 8.80, 5.0, 'karton', date('Y-m-d H:i:s')]);
$testProdId = (int)$repo->getPdo()->lastInsertId();

// 1. Utworzenie zamówienia testowego do eksportu
$orderId = $repo->createOrder([
    'order_number'              => 'B2B/ERP/TEST/' . time(),
    'client_id'                 => 1,
    'client_name_snapshot'      => 'Sklep Testowy ERP',
    'delivery_address_snapshot' => 'ul. Testowa 10, Warszawa',
    'delivery_date'             => '2026-09-25',
    'status'                    => 'new',
    'total_amount'              => 88.00,
    'notes'                     => 'Test eksportu ERP'
], [
    [
        'product_id'      => $testProdId,
        'product_name'    => 'Papryka Czerwona Test',
        'price'           => 8.80,
        'quantity'        => 10,
        'unit'            => 'kg',
        'package_size'    => 5.0,
        'package_unit'    => 'karton',
        'package_summary' => '2 kartony',
        'item_total'      => 88.00
    ]
]);
assert($orderId > 0, "Błąd tworzenia zamówienia testowego!");

$cookieFile = tempnam(sys_get_temp_dir(), 'cook_erp_');
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);

// Logowanie admina
curl_setopt($ch, CURLOPT_URL, 'http://localhost/home/login');
$resLogin = curl_exec($ch);
preg_match('/name="_csrf" value="([^"]+)"/', $resLogin, $mCsrf);
$loginCsrf = $mCsrf[1] ?? '';

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'login' => APP_LOGIN,
    'password' => APP_PASSWORD,
    '_csrf' => $loginCsrf
]));
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_exec($ch);

// Pobranie panelu admina i CSRF
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_URL, 'http://localhost/b2b/admin');
$adminHtml = curl_exec($ch);
preg_match('/const CSRF_TOKEN\s*=\s*\'([^\']+)\';/', $adminHtml, $mAdminCsrf);
$adminCsrf = $mAdminCsrf[1] ?? '';

// 2. Test zapisu ustawień przez POST /b2b/savesettings
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_URL, 'http://localhost/b2b/savesettings');
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'cutoff_time'        => '22:15',
    'default_erp_format' => 'optima',
    '_csrf'              => $adminCsrf
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Requested-With: XMLHttpRequest', 'Accept: application/json']);
$respSettings = curl_exec($ch);
$settingsData = json_decode($respSettings, true);
$saveSettingsOk = (!empty($settingsData['ok']) && $repo->getSetting('cutoff_time') === '22:15');
echo "1. Zapis ustawień przez /b2b/savesettings: " . ($saveSettingsOk ? "PASS" : "FAIL") . "\n";

// Przywrócenie domyślnych
$repo->setSetting('cutoff_time', '21:30');
$repo->setSetting('default_erp_format', 'subiekt');

// 3. Test eksportu pojedynczego zamówienia GET /b2b/exporterp?id=...&format=subiekt
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, []);
curl_setopt($ch, CURLOPT_URL, "http://localhost/b2b/exporterp?id={$orderId}&format=subiekt");
$eppContent = curl_exec($ch);
$httpCodeEpp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$eppValid = ($httpCodeEpp === 200 && strpos($eppContent, '[INFO]') !== false && strpos($eppContent, 'PAP-CZERW-01') !== false);
echo "2. Pobranie pliku Subiekt EPP (/b2b/exporterp): " . ($eppValid ? "PASS" : "FAIL (Code: $httpCodeEpp, Content: " . substr($eppContent, 0, 100) . ")") . "\n";

// 4. Test eksportu pojedynczego zamówienia w formacie Comarch Optima XML
curl_setopt($ch, CURLOPT_URL, "http://localhost/b2b/exporterp?id={$orderId}&format=optima");
$xmlContent = curl_exec($ch);
$httpCodeOptima = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$optimaValid = (strpos($xmlContent, '<?xml') !== false && strpos($xmlContent, 'PAP-CZERW-01') !== false);
echo "3. Pobranie pliku Optima XML (/b2b/exporterp): " . ($optimaValid ? "PASS" : "FAIL (Code: $httpCodeOptima, Content: " . substr($xmlContent, 0, 100) . ")") . "\n";

// 5. Test eksportu zbiorczego GET /b2b/exportbatch?format=subiekt
curl_setopt($ch, CURLOPT_URL, "http://localhost/b2b/exportbatch?format=subiekt&status=new");
$batchContent = curl_exec($ch);
$httpCodeBatch = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$batchValid = (strpos($batchContent, '[INFO]') !== false && strpos($batchContent, 'PAP-CZERW-01') !== false);
echo "4. Pobranie paczki zbiorczej ERP (/b2b/exportbatch): " . ($batchValid ? "PASS" : "FAIL (Code: $httpCodeBatch, Content: " . substr($batchContent, 0, 100) . ")") . "\n";

curl_close($ch);
@unlink($cookieFile);

// Sprzątanie
$repo->getPdo()->exec("DELETE FROM b2b_order_items WHERE order_id = {$orderId}");
$repo->getPdo()->exec("DELETE FROM b2b_orders WHERE id = {$orderId}");
$repo->getPdo()->exec("DELETE FROM b2b_products WHERE id = {$testProdId}");

if (!$saveSettingsOk || !$eppValid || !$optimaValid || !$batchValid) {
    echo "=== TEST ZAKOŃCZONY BŁĘDEM (Stan RED) ===\n";
    exit(1);
}

echo "=== TEST ZAKOŃCZONY SUKCESEM (Stan GREEN) ===\n";
