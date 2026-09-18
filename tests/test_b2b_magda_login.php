<?php
/**
 * Test weryfikacyjny konta klienta Magda (hasło: Magda123) oraz usunięcia linku do Zamawiarki Magdy z panelu B2B.
 */
define('BASE_PATH', 'C:/laragon/www');
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';

echo "=== TEST: KONTO MAGDA & B2B CLIENT VIEW ===\n";

$cookieFile = tempnam(sys_get_temp_dir(), 'cook_magda_');
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

// 1. Logowanie na /b2b/login loginem 'Magda' i hasłem 'Magda123'
$resLoginGet = req('http://localhost/b2b/login');
preg_match('/name="csrf_token"\s+value="([^"]+)"/', $resLoginGet['body'], $m);
$csrfToken = $m[1] ?? '';

$resLoginPost = req('http://localhost/b2b/login', [
    'login'      => 'Magda',
    'password'   => 'Magda123',
    'csrf_token' => $csrfToken
], true);

echo "1. Logowanie na /b2b/login (HTTP code): {$resLoginPost['code']}\n";
echo "   Docelowy URL: {$resLoginPost['url']}\n";

$hasClientName = strpos($resLoginPost['body'], 'Sklep Magda') !== false;
$hasCatalogTable = strpos($resLoginPost['body'], 'catalogTable') !== false;
$hasFloatingCart = strpos($resLoginPost['body'], 'floatingCart') !== false;
$isNotAdmin = strpos($resLoginPost['body'], 'Panel Hurtownika — Zarządzanie') === false;

echo "2. Rozpoznano firmę 'Sklep Magda': " . ($hasClientName ? "OK" : "BŁĄD") . "\n";
echo "3. Wyświetlono widok katalogu klienta B2B (tabela i koszyk): " . (($hasCatalogTable && $hasFloatingCart) ? "OK" : "BŁĄD") . "\n";
echo "4. Brak widoku panelu hurtownika (widok wyłącznie klienta): " . ($isNotAdmin ? "OK" : "BŁĄD") . "\n";

// Wylogowanie
req('http://localhost/b2b/logout', null, true);

// 2. Logowanie przez /home/login danymi 'Magda' / 'Magda123'
$resHomeLoginGet = req('http://localhost/home/login');
preg_match('/name="_csrf"\s+value="([^"]+)"/', $resHomeLoginGet['body'], $m2);
$homeCsrf = $m2[1] ?? '';

$resHomeLoginPost = req('http://localhost/home/login', [
    'login'      => 'Magda',
    'password'   => 'Magda123',
    '_csrf'      => $homeCsrf
], true);

echo "5. Logowanie przez /home/login jako Magda (HTTP code): {$resHomeLoginPost['code']}\n";
echo "   Docelowy URL: {$resHomeLoginPost['url']}\n";
$hasClientName2 = strpos($resHomeLoginPost['body'], 'Sklep Magda') !== false;
echo "6. Automatyczne przekierowanie do katalogu B2B z nazwą 'Sklep Magda': " . ($hasClientName2 ? "OK" : "BŁĄD") . "\n";

// 3. Weryfikacja braku linku do "Zamawiarka Magdy" w panelu B2B
$cookieAdmin = tempnam(sys_get_temp_dir(), 'cook_admin_test_');
$chAdmin = curl_init();
curl_setopt($chAdmin, CURLOPT_RETURNTRANSFER, true);
curl_setopt($chAdmin, CURLOPT_COOKIEJAR, $cookieAdmin);
curl_setopt($chAdmin, CURLOPT_COOKIEFILE, $cookieAdmin);
curl_setopt($chAdmin, CURLOPT_FOLLOWLOCATION, true);

// Login admin
curl_setopt($chAdmin, CURLOPT_URL, 'http://localhost/home/login');
$bodyL = curl_exec($chAdmin);
preg_match('/name="_csrf"\s+value="([^"]+)"/', $bodyL, $m3);
curl_setopt($chAdmin, CURLOPT_POST, true);
curl_setopt($chAdmin, CURLOPT_POSTFIELDS, http_build_query([
    'login'    => APP_LOGIN,
    'password' => APP_PASSWORD,
    '_csrf'    => $m3[1] ?? ''
]));
curl_exec($chAdmin);

// Pobierz /b2b/admin
curl_setopt($chAdmin, CURLOPT_HTTPGET, true);
curl_setopt($chAdmin, CURLOPT_URL, 'http://localhost/b2b/admin');
$adminPanelBody = curl_exec($chAdmin);

$hasNoZamawiarka = strpos($adminPanelBody, 'Zamawiarka Magdy') === false;
$hasNoOrderLink  = strpos($adminPanelBody, 'order/index') === false;

echo "7. Link 'Zamawiarka Magdy' usunięty z panelu B2B: " . ($hasNoZamawiarka ? "OK" : "BŁĄD") . "\n";
echo "8. Ścieżka 'order/index' usunięta z panelu B2B: " . ($hasNoOrderLink ? "OK" : "BŁĄD") . "\n";

@unlink($cookieFile);
@unlink($cookieAdmin);

$passed = ($hasClientName && $hasCatalogTable && $hasFloatingCart && $isNotAdmin && $hasClientName2 && $hasNoZamawiarka && $hasNoOrderLink);

echo "\n" . ($passed ? "=== ALL MAGDA B2B TESTS PASSED ===" : "=== TESTS FAILED ===") . "\n";
exit($passed ? 0 : 1);
