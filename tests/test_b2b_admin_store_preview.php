<?php
/**
 * Test weryfikacyjny podglądu sklepu B2B dla administratora (hurtownika)
 */
define('BASE_PATH', 'C:/laragon/www');
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';

$cookieFile = tempnam(sys_get_temp_dir(), 'cook_preview_');
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);

function req($url, $post = null, $follow = false) {
    global $ch;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $follow);
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    } else {
        curl_setopt($ch, CURLOPT_HTTPGET, true);
    }
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $urlEff = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    return ['code' => $code, 'url' => $urlEff, 'body' => $body];
}

echo "=== TEST: Podgląd sklepu B2B dla zalogowanego hurtownika (admin) ===\n";

// 1. Pobierz formularz /b2b/login i odczytaj CSRF
$resGet = req('http://localhost/b2b/login');
preg_match('/name="_csrf"\s+value="([^"]+)"/', $resGet['body'], $m);
$csrf = $m[1] ?? '';
if (empty($csrf)) {
    preg_match('/name="csrf_token"\s+value="([^"]+)"/', $resGet['body'], $m);
    $csrf = $m[1] ?? '';
}

// 2. Zaloguj hurtownika (admin)
$resLogin = req('http://localhost/b2b/login', [
    'login'      => APP_LOGIN,
    'password'   => APP_PASSWORD,
    '_csrf'      => $csrf,
    'csrf_token' => $csrf
], true);

echo "1. Logowanie hurtownika: HTTP {$resLogin['code']}, URL: {$resLogin['url']}\n";

// 3. Sprawdź czy w panelu hurtownika link 'Podgląd sklepu B2B' istnieje i dokąd prowadzi
$resAdmin = req('http://localhost/b2b/admin');
preg_match('/<a[^>]+href="([^"]*b2b[^"]*)"[^>]*>\s*<svg[^>]*>.*?<\/svg>\s*Podgląd sklepu B2B/is', $resAdmin['body'], $linkMatches);
$previewHref = $linkMatches[1] ?? '';
echo "2. Link podglądu w panelu hurtownika: [{$previewHref}]\n";

// 4. Wejdź na /b2b z ciasteczkiem zalogowanego hurtownika (admin) BEZ automatycznego follow
$resStoreNoFollow = req('http://localhost/b2b', null, false);
echo "3. Wejście na /b2b (bez follow redirect): HTTP {$resStoreNoFollow['code']}\n";

// 5. Wejdź na /b2b z follow location
$resStore = req('http://localhost/b2b', null, true);
echo "4. Efektywny URL po wejściu na /b2b: {$resStore['url']}\n";

$isB2bCatalog = (strpos($resStore['url'], 'b2b/admin') === false && strpos($resStore['body'], 'Platforma zamówień B2B') !== false);
$hasProductsTable = (strpos($resStore['body'], 'catalogTable') !== false || strpos($resStore['body'], 'searchInput') !== false);
$hasPreviewBanner = (strpos($resStore['body'], 'TRYB PODGLĄDU SKLEPU B2B') !== false);
$hasReturnButton  = (strpos($resStore['body'], 'Wróć do Panelu Hurtownika') !== false);

echo "5. Czy otwiera sklep B2B (a NIE b2b/admin): " . ($isB2bCatalog ? "TAK (SUKCES)" : "NIE (BŁĄD)") . "\n";
echo "6. Czy widoczne są elementy katalogu (wyszukiwarka / tabela): " . ($hasProductsTable ? "TAK (SUKCES)" : "NIE (BŁĄD)") . "\n";
echo "7. Czy wyświetla pasek trybu podglądu: " . ($hasPreviewBanner ? "TAK (SUKCES)" : "NIE (BŁĄD)") . "\n";
echo "8. Czy zawiera przycisk powrotu do panelu hurtownika: " . ($hasReturnButton ? "TAK (SUKCES)" : "NIE (BŁĄD)") . "\n";

// 6. Sprawdź kliknięcie 'Historia' dla admina w podglądzie
$resHistory = req('http://localhost/b2b/history', null, false);
echo "9. Wejście na /b2b/history w trybie admina: HTTP {$resHistory['code']}, przekierowanie do panelu\n";

@unlink($cookieFile);

$passed = ($isB2bCatalog && $hasProductsTable && $resStoreNoFollow['code'] === 200 && $hasPreviewBanner && $hasReturnButton && $resHistory['code'] === 303);
echo $passed ? "=== TEST PODGLĄDU SKLEPU B2B ZAKOŃCZONY SUKCESEM ===\n" : "=== TEST ZAKOŃCZONY BŁĘDEM ===\n";
exit($passed ? 0 : 1);
