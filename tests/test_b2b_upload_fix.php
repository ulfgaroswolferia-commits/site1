<?php
/**
 * Test weryfikacyjny uploadu cennika Excel oraz mapowania w panelu hurtownika B2B
 */
define('BASE_PATH', 'C:/laragon/www');
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';
require_once BASE_PATH . '/program/lib/XlsxWriter.php';
require_once BASE_PATH . '/program/lib/XlsxParser.php';

$cookieFile = tempnam(sys_get_temp_dir(), 'cook_upload_test_');
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);

function httpReq($url, $post = null, $headers = [], $follow = false) {
    global $ch;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $follow);
    curl_setopt($ch, CURLOPT_HTTPHEADER, !empty($headers) ? $headers : []);
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

echo "=== TEST: Upload Excela i Import w Panelu Hurtownika (/b2b/upload & /b2b/processimport) ===\n";

// 1. Zaloguj admina
$resLoginGet = httpReq('http://localhost/home/login');
preg_match('/name="_csrf" value="([^"]+)"/', $resLoginGet['body'], $m);
$loginCsrf = $m[1] ?? '';

httpReq('http://localhost/home/login', [
    'login' => APP_LOGIN,
    'password' => APP_PASSWORD,
    '_csrf' => $loginCsrf
], [], true);

// 2. Pobierz stronę /b2b/admin i odczytaj CSRF
$resAdminGet = httpReq('http://localhost/b2b/admin');
preg_match('/const CSRF_TOKEN = \'([^\']+)\'/', $resAdminGet['body'], $mB2b);
$csrfToken = $mB2b[1] ?? '';

if (empty($csrfToken)) {
    echo "[FAILED] Nie znaleziono CSRF tokena w panelu hurtownika\n";
    exit(1);
}
echo "[OK] Zalogowano do panelu hurtownika, CSRF token: " . substr($csrfToken, 0, 8) . "...\n";

// 3. Przygotuj plik testowy Excel
$testExcelPath = sys_get_temp_dir() . '/test_cennik_hurtownia_' . time() . '.xlsx';
$testItems = [
    ['name' => 'Papryka czerwona PL', 'price' => 11.50, 'quantity' => 10, 'unit' => 'kg'],
    ['name' => 'Ogórek gruntowy',    'price' => 6.20,  'quantity' => 15, 'unit' => 'kg'],
    ['name' => 'Kapusta młoda',       'price' => 4.50,  'quantity' => 20, 'unit' => 'szt.'],
];
XlsxWriter::saveToFile($testExcelPath, $testItems, [
    'order_number' => 'CENNIK/HURT/01',
    'supplier_name' => 'Hurtownia Magdy',
    'created_at' => date('Y-m-d H:i')
]);

if (!file_exists($testExcelPath)) {
    echo "[FAILED] Nie udało się utworzyć pliku tymczasowego Excel\n";
    exit(1);
}
echo "[OK] Utworzono testowy arkusz XLSX: " . filesize($testExcelPath) . " bajtów\n";

// 4. POST /b2b/upload
$cfile = new CURLFile($testExcelPath, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'cennik.xlsx');
$resUpload = httpReq('http://localhost/b2b/upload', [
    'cennik' => $cfile,
    '_csrf'  => $csrfToken
], ['X-Requested-With: XMLHttpRequest', 'Accept: application/json']);

echo "Odpowiedź /b2b/upload: HTTP {$resUpload['code']}\n";
$dataUpload = json_decode($resUpload['body'], true);

if (($dataUpload['ok'] ?? false) !== true) {
    echo "[FAILED] Błąd uploadu: " . $resUpload['body'] . "\n";
    @unlink($testExcelPath);
    @unlink($cookieFile);
    exit(1);
}

echo "[OK] Upload powiódł się. File ID: {$dataUpload['file_id']}\n";

// Sprawdź czy preview_rows i candidate_columns są obecne
$previewRows = $dataUpload['preview_rows'] ?? null;
$candidates = $dataUpload['candidate_columns'] ?? null;

if (empty($previewRows)) {
    echo "[FAILED] Brak preview_rows w odpowiedzi\n";
    exit(1);
}
echo "[OK] preview_rows zwrócone (liczba wierszy: " . count($previewRows) . ")\n";

if (empty($candidates)) {
    echo "[FAILED] Brak candidate_columns w odpowiedzi\n";
    exit(1);
}

// Sprawdź czy candidate_columns ma właściwe indeksy
$headerRow = $candidates['header_row_index'] ?? $candidates['headerRow'] ?? null;
$prodCol   = $candidates['product_col_index'] ?? $candidates['productCol'] ?? null;
$priceCol  = $candidates['price_col_index'] ?? $candidates['priceCol'] ?? null;

echo "[OK] Kandydaci kolumn: HeaderRow={$headerRow}, ProdCol={$prodCol}, PriceCol={$priceCol}\n";

// 5. POST /b2b/processimport
$resImport = httpReq('http://localhost/b2b/processimport', [
    'file_id'     => $dataUpload['file_id'],
    'header_row'  => $headerRow,
    'col_product' => $prodCol,
    'col_price'   => $priceCol,
    'col_unit'    => $candidates['unit_col_index'] ?? $candidates['unitCol'] ?? '',
    '_csrf'       => $csrfToken
], ['X-Requested-With: XMLHttpRequest', 'Accept: application/json']);

$dataImport = json_decode($resImport['body'], true);
if (($dataImport['ok'] ?? false) !== true || ($dataImport['total_imported'] ?? 0) <= 0) {
    echo "[FAILED] Import nie powiódł się: " . $resImport['body'] . "\n";
    @unlink($testExcelPath);
    @unlink($cookieFile);
    exit(1);
}

echo "[OK] Import zakończony sukcesem! Zaimportowano: {$dataImport['total_imported']} produktów\n";

// 6. Sprawdź w bazie danych
$repo = new \App\B2bRepository();
$allProds = $repo->getAllProductsAdmin();
$foundPapryka = false;
foreach ($allProds as $p) {
    if (strpos($p['name'], 'Papryka') !== false) {
        $foundPapryka = true;
        break;
    }
}

if (!$foundPapryka) {
    echo "[FAILED] Nie znaleziono zaimportowanego produktu w bazie b2b_products\n";
    exit(1);
}
echo "[OK] Zaimportowany towar ('Papryka') poprawnie odnaleziony w ofercie hurtowni!\n";

@unlink($testExcelPath);
@unlink($cookieFile);
echo "=== WSZYSTKIE TESTY UPLOADU I IMPORTU ZAKOŃCZONE SUKCESEM ===\n";
exit(0);
