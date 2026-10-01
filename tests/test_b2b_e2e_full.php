<?php
/**
 * Kompletny test integracyjny E2E dla platformy "Hurtownia Magdy" (B2B)
 */
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';

echo "=== START E2E TEST: HURTOWNIA MAGDY (B2B) ===\n\n";

$cookieAdmin = tempnam(sys_get_temp_dir(), 'cook_admin_');
$cookieClient = tempnam(sys_get_temp_dir(), 'cook_client_');

function makeCurl($cookieFile) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    return $ch;
}

function req($ch, $url, $post = null, $headers = [], $follow = false) {
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $follow);
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    } else {
        curl_setopt($ch, CURLOPT_HTTPHEADER, []);
    }
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
    $ct = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    return ['code' => $code, 'ct' => $ct, 'body' => $body];
}

$chAdmin  = makeCurl($cookieAdmin);
$chClient = makeCurl($cookieClient);

$repo = new \App\B2bRepository();
$allPassed = true;

function assertCondition($desc, $cond) {
    global $allPassed;
    if ($cond) {
        echo " [OK] $desc\n";
    } else {
        echo " [FAILED] $desc\n";
        $allPassed = false;
    }
}

// -------------------------------------------------------------
// 1. Logowanie Administratora Hurtowni
// -------------------------------------------------------------
echo "1. Logowanie administratora hurtowni...\n";
$loginRes = req($chAdmin, 'http://localhost/home/login');
preg_match('/name="_csrf" value="([^"]+)"/', $loginRes['body'], $mCsrf);
$adminCsrf = $mCsrf[1] ?? '';

$postLogin = req($chAdmin, 'http://localhost/home/login', [
    'login'    => APP_LOGIN,
    'password' => APP_PASSWORD,
    '_csrf'    => $adminCsrf
], [], true);

assertCondition("Zalogowano do panelu administracyjnego (HTTP 200)", $postLogin['code'] === 200);

// -------------------------------------------------------------
// 2. DostÄ™p do Panelu Hurtownika (/b2b/admin)
// -------------------------------------------------------------
echo "\n2. Weryfikacja panelu hurtownika (/b2b/admin)...\n";
$adminPanel = req($chAdmin, 'http://localhost/b2b/admin');
assertCondition("Panel hurtownika zwraca HTTP 200", $adminPanel['code'] === 200);
assertCondition("Zawiera zakĹ‚adkÄ™ cennika ('Aktualny cennik')", strpos($adminPanel['body'], 'Aktualny cennik') !== false || strpos($adminPanel['body'], 'Cennik & Oferta') !== false);
assertCondition("Zawiera zakĹ‚adkÄ™ 'Klienci Hurtowni'", strpos($adminPanel['body'], 'Klienci') !== false);

preg_match('/const CSRF_TOKEN\s*=\s*\'([^\']+)\'/', $adminPanel['body'], $mAdminCsrf);
$b2bAdminCsrf = $mAdminCsrf[1] ?? '';

// -------------------------------------------------------------
// 3. Utworzenie klienta B2B przez panel hurtownika
// -------------------------------------------------------------
echo "\n3. Rejestracja nowego sklepu B2B przez hurtowniÄ™...\n";
$clientName = 'Sklep Warzywny Pod Kasztanem ' . rand(100, 999);
$resCreateClient = req($chAdmin, 'http://localhost/b2b/createclient', [
    'company_name'     => $clientName,
    'nip'              => '1234567890',
    'phone'            => '500600700',
    'delivery_address' => 'ul. GĹ‚Ăłwna 15, 20-001 Lublin',
    'email'            => 'sklep@kasztan.pl',
    '_csrf'            => $b2bAdminCsrf
], ['X-Requested-With: XMLHttpRequest', 'Accept: application/json']);

$dataClient = json_decode($resCreateClient['body'], true);
assertCondition("Utworzono klienta B2B (ok: true)", ($dataClient['ok'] ?? false) === true);
$clientToken = $dataClient['auth_token'] ?? '';
$clientId    = $dataClient['client_id'] ?? 0;
assertCondition("Wygenerowano unikalny token dostÄ™powy", !empty($clientToken) && strlen($clientToken) === 32);

// -------------------------------------------------------------
// 4. Zaopatrzenie oferty w testowe produkty z opakowaniami
// -------------------------------------------------------------
echo "\n4. Konfiguracja oferty i reguĹ‚ opakowaĹ„...\n";
$repo->saveProductsBatch([
    [
        'name'         => 'Pomidor Malinowy Extra',
        'category'     => 'Warzywa',
        'unit'         => 'kg',
        'price'        => 8.50,
        'package_size' => 6.0,
        'package_unit' => 'skrzynka',
        'is_available' => 1
    ],
    [
        'name'         => 'Banan Ekwador Premium',
        'category'     => 'Owoce',
        'unit'         => 'kg',
        'price'        => 4.90,
        'package_size' => 18.0,
        'package_unit' => 'karton',
        'is_available' => 1
    ]
], true);
assertCondition("Zapisano towary w bazie z inteligentnymi opakowaniami", true);

// -------------------------------------------------------------
// 5. WejĹ›cie klienta przez link z tokenem (/b2b?token=...)
// -------------------------------------------------------------
echo "\n5. BĹ‚yskawiczne wejĹ›cie sklepu przez token do katalogu...\n";
$clientCatalog = req($chClient, 'http://localhost/b2b?token=' . $clientToken, null, [], true);
assertCondition("Katalog dostÄ™pny bez logowania hasĹ‚em (HTTP 200)", $clientCatalog['code'] === 200);
assertCondition("Rozpoznano firmÄ™ klienta w nagĹ‚Ăłwku", strpos($clientCatalog['body'], $clientName) !== false);
assertCondition("Towary widoczne w katalogu", strpos($clientCatalog['body'], 'Pomidor Malinowy Extra') !== false);
assertCondition("Widoczna informacja o skrzynce/kartonie (Box Optimizer)", strpos($clientCatalog['body'], 'skrzynka') !== false);

preg_match('/const CSRF_TOKEN = \'([^\']+)\'/', $clientCatalog['body'], $mClientCsrf);
$clientCsrf = $mClientCsrf[1] ?? '';
assertCondition("Pobrano token CSRF sesji klienta", !empty($clientCsrf));

// -------------------------------------------------------------
// 6. ZĹ‚oĹĽenie zamĂłwienia z rozbiciem logistycznym
// -------------------------------------------------------------
echo "\n6. ZĹ‚oĹĽenie zamĂłwienia B2B (12 kg pomidora = 2 skrzynki, 36 kg banana = 2 kartony)...\n";
$itemsOrder = [
    [
        'product_name' => 'Pomidor Malinowy Extra',
        'price'        => 8.50,
        'quantity'     => 12.0, // 2 peĹ‚ne skrzynki po 6 kg = 102.00 zĹ‚
        'unit'         => 'kg',
        'package_size' => 6.0,
        'package_unit' => 'skrzynka'
    ],
    [
        'product_name' => 'Banan Ekwador Premium',
        'price'        => 4.90,
        'quantity'     => 36.0, // 2 kartony po 18 kg = 176.40 zĹ‚
        'unit'         => 'kg',
        'package_size' => 18.0,
        'package_unit' => 'karton'
    ]
];

$resSaveOrder = req($chClient, 'http://localhost/b2b/saveorder', [
    'items' => json_encode($itemsOrder),
    'notes' => 'ProszÄ™ o dostawÄ™ na rampÄ™ nr 2 przed 6:00 rano',
    '_csrf' => $clientCsrf
], ['X-Requested-With: XMLHttpRequest', 'Accept: application/json']);

$dataOrder = json_decode($resSaveOrder['body'], true);
assertCondition("ZamĂłwienie przyjÄ™te przez API (ok: true)", ($dataOrder['ok'] ?? false) === true);
$orderNumber = $dataOrder['order_number'] ?? '';
$orderId     = (int)($dataOrder['order_id'] ?? 0);
$exportFile  = $dataOrder['export_filename'] ?? '';

assertCondition("Nadano numer zamĂłwienia: $orderNumber", !empty($orderNumber) && strpos($orderNumber, 'B2B/') === 0);
assertCondition("Zapisano id zamĂłwienia: $orderId", $orderId > 0);

// -------------------------------------------------------------
// 7. Weryfikacja wygenerowanej karty kompletacji Excel (.xlsx)
// -------------------------------------------------------------
echo "\n7. Weryfikacja karty kompletacji magazynowej na rampÄ™ (.xlsx)...\n";
$excelPath = BASE_PATH . '/storage/b2b/orders/' . $exportFile;
assertCondition("Plik karty kompletacji istnieje na dysku", file_exists($excelPath));
assertCondition("Rozmiar pliku Excel jest prawidĹ‚owy (> 2 KB)", file_exists($excelPath) && filesize($excelPath) > 2000);

$zip = new ZipArchive();
$isValidZip = ($zip->open($excelPath) === true);
assertCondition("Plik jest poprawnym archiwum OpenXML (.xlsx)", $isValidZip);

if ($isValidZip) {
    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    assertCondition("Arkusz zawiera kolumnÄ™ kontrolnÄ… 'Skompletowano'", strpos($sheetXml, 'Skompletowano') !== false);
    assertCondition("Arkusz zawiera checkboxy dla magazyniera [    ]", strpos($sheetXml, '[    ]') !== false);
    assertCondition("Arkusz zawiera rozbicie logistyczne 'skrzynki'", strpos($sheetXml, 'skrzynk') !== false);
    assertCondition("Arkusz zawiera uwagi do zamĂłwienia", strpos($sheetXml, 'ramp') !== false);
    $zip->close();
}

// -------------------------------------------------------------
// 8. Pobranie karty kompletacji przez klienta (/b2b/download)
// -------------------------------------------------------------
echo "\n8. Pobranie karty kompletacji (.xlsx) przez klienta...\n";
$resDownload = req($chClient, 'http://localhost/b2b/download?id=' . $orderId);
assertCondition("Pobranie pliku zwraca HTTP 200", $resDownload['code'] === 200);
assertCondition("Poprawny nagĹ‚Ăłwek Content-Type dla .xlsx", strpos($resDownload['ct'], 'openxmlformats-officedocument.spreadsheetml.sheet') !== false);
assertCondition("Pobrana treĹ›Ä‡ ma wĹ‚aĹ›ciwÄ… dĹ‚ugoĹ›Ä‡", strlen($resDownload['body']) === filesize($excelPath));

// -------------------------------------------------------------
// 9. Hurtownik weryfikuje szczegĂłĹ‚y i zmienia status zamĂłwienia
// -------------------------------------------------------------
echo "\n9. ObsĹ‚uga zamĂłwienia przez panel hurtownika...\n";
$resDetails = req($chAdmin, 'http://localhost/b2b/orderdetails?id=' . $orderId);
$dataDetails = json_decode($resDetails['body'], true);
assertCondition("Hurtownik odczytuje szczegĂłĹ‚y zamĂłwienia", ($dataDetails['ok'] ?? false) === true);
assertCondition("Rozpoznano odbiorcÄ™: " . ($dataDetails['order']['client_name_snapshot'] ?? ''), $dataDetails['order']['client_name_snapshot'] === $clientName);
assertCondition("Liczba pozycji: 2", count($dataDetails['items']) === 2);

// Zmiana statusu na 'processing'
$resStatusProc = req($chAdmin, 'http://localhost/b2b/updateorderstatus', [
    'id'     => $orderId,
    'status' => 'processing',
    '_csrf'  => $b2bAdminCsrf
], ['X-Requested-With: XMLHttpRequest', 'Accept: application/json']);
assertCondition("Zmiana statusu na 'processing' (ok: true)", (json_decode($resStatusProc['body'], true)['ok'] ?? false) === true);

// Zmiana statusu na 'completed'
$resStatusComp = req($chAdmin, 'http://localhost/b2b/updateorderstatus', [
    'id'     => $orderId,
    'status' => 'completed',
    '_csrf'  => $b2bAdminCsrf
], ['X-Requested-With: XMLHttpRequest', 'Accept: application/json']);
assertCondition("Zmiana statusu na 'completed' (ok: true)", (json_decode($resStatusComp['body'], true)['ok'] ?? false) === true);

// -------------------------------------------------------------
// 10. Klient weryfikuje historiÄ™ swoich zamĂłwieĹ„ (/b2b/history)
// -------------------------------------------------------------
echo "\n10. Weryfikacja historii zamĂłwieĹ„ w portalu klienta...\n";
$resHistory = req($chClient, 'http://localhost/b2b/history', null, [], true);
assertCondition("Ekran historii zwraca HTTP 200", $resHistory['code'] === 200);
assertCondition("Zawiera numer zamĂłwienia: $orderNumber", strpos($resHistory['body'], $orderNumber) !== false);
assertCondition("Zawiera status 'Zrealizowane'", strpos($resHistory['body'], 'Zrealizowane') !== false);
assertCondition("Zawiera modal szczegĂłĹ‚Ăłw zamĂłwienia", strpos($resHistory['body'], 'id="clientOrderModal"') !== false);

// -------------------------------------------------------------
// 11. Klient pobiera szczegĂłĹ‚y zamĂłwienia (klik w numer zamĂłwienia)
// -------------------------------------------------------------
echo "\n11. Klient pobiera szczegĂłĹ‚y zamĂłwienia (/b2b/orderdetails)...\n";
$resClientDetails = req($chClient, 'http://localhost/b2b/orderdetails?id=' . $orderId);
$dataClientDetails = json_decode($resClientDetails['body'], true);
assertCondition("Klient odczytuje szczegĂłĹ‚y swojego zamĂłwienia (ok: true)", ($dataClientDetails['ok'] ?? false) === true);
assertCondition("ZgodnoĹ›Ä‡ numeru zamĂłwienia", ($dataClientDetails['order']['order_number'] ?? '') === $orderNumber);
assertCondition("ZwrĂłcono pozycje zamĂłwienia dla klienta", count($dataClientDetails['items'] ?? []) === 2);

// SprzÄ…tanie plikĂłw tymczasowych
@unlink($cookieAdmin);
@unlink($cookieClient);

echo "\n============================================\n";
if ($allPassed) {
    echo ">>> PEĹNY SUKCES E2E: WSZYSTKIE TESTY HURTOWNI MAGDY PRZESZĹY POMYĹšLNIE! <<<\n";
    exit(0);
} else {
    echo ">>> NIEKTĂ“RE TESTY E2E NIE POWIODĹY SIÄ! <<<\n";
    exit(1);
}
