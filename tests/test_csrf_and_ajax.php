<?php
/**
 * Test weryfikujący obsługę żądań AJAX, tokenów CSRF (zarówno _csrf jak i csrf_token)
 * oraz zapobieganie błędom JSON.parse przy wygasłej sesji i błędach CSRF.
 */
define('BASE_PATH', 'C:/laragon/www');
require_once BASE_PATH . '/program/config/data.php';

$cookieFile = tempnam(sys_get_temp_dir(), 'cook_test_');
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);

function httpReq($url, $post = null, $headers = [], $follow = false) {
    global $ch;
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

echo "=== TEST: CSRF & AJAX Upload ===\n";

// 1. Niezalogowane żądanie AJAX nie powinno zwracać przekierowania HTML (303/200 text/html)
$resUnauth = httpReq('http://localhost/order/upload', ['test' => 1], ['X-Requested-With: XMLHttpRequest', 'Accept: application/json']);
echo "1. Unauth AJAX status: {$resUnauth['code']}\n";
echo "   Unauth Content-Type: {$resUnauth['ct']}\n";

// 2. Logowanie
$resLoginGet = httpReq('http://localhost/home/login');
preg_match('/name="_csrf" value="([^"]+)"/', $resLoginGet['body'], $m);
$loginCsrf = $m[1] ?? '';

$resLoginPost = httpReq('http://localhost/home/login', [
    'login' => APP_LOGIN,
    'password' => APP_PASSWORD,
    '_csrf' => $loginCsrf
], [], true);

// 3. Pobranie tokena z kreatora zamówień
$resOrderGet = httpReq('http://localhost/order/index');
preg_match('/const CSRF_TOKEN = \'([^\']+)\'/', $resOrderGet['body'], $mOrder);
$orderCsrf = $mOrder[1] ?? '';
echo "2. CSRF Token uzyskany: " . (!empty($orderCsrf) ? "TAK" : "NIE") . "\n";

// 4. Utworzenie przykładowego pliku XLSX
$sampleFile = sys_get_temp_dir() . '/sample_test_order.xlsx';
$zip = new ZipArchive();
$zip->open($sampleFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
$zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
$zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
$zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
$zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Cennik" sheetId="1" r:id="rId1"/></sheets></workbook>');
$sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData><row r="1"><c r="A1" t="inlineStr"><is><t>Produkt</t></is></c><c r="B1" t="inlineStr"><is><t>Cena</t></is></c></row><row r="2"><c r="A2" t="inlineStr"><is><t>Pomidory</t></is></c><c r="B2"><v>12.50</v></c></row></sheetData></worksheet>';
$zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
$zip->close();

// 5. Test wysłania z nazwą pola "csrf_token" (tak jak wcześniej wysyłał frontend)
$uploadDataWithCsrfToken = [
    'price_list' => new CURLFile($sampleFile, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'sample.xlsx'),
    'csrf_token' => $orderCsrf
];
$resUpload1 = httpReq('http://localhost/order/upload', $uploadDataWithCsrfToken, ['X-Requested-With: XMLHttpRequest', 'Accept: application/json']);
echo "3. Upload z polem 'csrf_token' - status: {$resUpload1['code']}, json: " . (json_decode($resUpload1['body']) ? "TAK" : "NIE") . "\n";

// 6. Test wysłania z nazwą pola "_csrf"
$uploadDataWithUnderscoreCsrf = [
    'price_list' => new CURLFile($sampleFile, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'sample.xlsx'),
    '_csrf' => $orderCsrf
];
$resUpload2 = httpReq('http://localhost/order/upload', $uploadDataWithUnderscoreCsrf, ['X-Requested-With: XMLHttpRequest', 'Accept: application/json']);
echo "4. Upload z polem '_csrf' - status: {$resUpload2['code']}, json: " . (json_decode($resUpload2['body']) ? "TAK" : "NIE") . "\n";

// 7. Test wysłania ze złym CSRF
$uploadBadCsrf = [
    'price_list' => new CURLFile($sampleFile, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'sample.xlsx'),
    '_csrf' => 'invalid_token_123'
];
$resUploadBad = httpReq('http://localhost/order/upload', $uploadBadCsrf, ['X-Requested-With: XMLHttpRequest', 'Accept: application/json']);
echo "5. Upload ze złym CSRF - status: {$resUploadBad['code']}, json: " . (json_decode($resUploadBad['body']) ? "TAK" : "NIE") . "\n";
echo "   Treść błędu: " . ($resUploadBad['body']) . "\n";

@unlink($cookieFile);
@unlink($sampleFile);
