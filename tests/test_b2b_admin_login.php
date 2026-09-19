<?php
/**
 * Test weryfikacyjny logowania administratora (hurtownika) przez /b2b/login
 */
define('BASE_PATH', 'C:/laragon/www');
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';

$cookieFile = tempnam(sys_get_temp_dir(), 'cook_admin_login_');
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

echo "=== TEST: Logowanie hurtownika (admin) przez /b2b/login ===\n";

// 1. Pobierz formularz /b2b/login i odczytaj CSRF
$resGet = req('http://localhost/b2b/login');
preg_match('/name="_csrf"\s+value="([^"]+)"/', $resGet['body'], $m);
$csrf = $m[1] ?? '';
if (empty($csrf)) {
    preg_match('/name="csrf_token"\s+value="([^"]+)"/', $resGet['body'], $m);
    $csrf = $m[1] ?? '';
}

echo "1. Pobranie strony /b2b/login: HTTP {$resGet['code']}\n";

// 2. Wyślij dane logowania administratora (APP_LOGIN / APP_PASSWORD)
$resPost = req('http://localhost/b2b/login', [
    'login'      => APP_LOGIN,
    'password'   => APP_PASSWORD,
    '_csrf'      => $csrf,
    'csrf_token' => $csrf
], true);

echo "2. Logowanie danymi hurtownika (APP_LOGIN): HTTP {$resPost['code']}\n";
echo "   Docelowy URL: {$resPost['url']}\n";

$isRedirectedToAdmin = (strpos($resPost['url'], 'b2b/admin') !== false);
$hasAdminPanelTitle = (strpos($resPost['body'], 'Panel Hurtownika') !== false);

echo "3. Przekierowano do /b2b/admin: " . ($isRedirectedToAdmin ? "OK" : "BŁĄD") . "\n";
echo "4. Wyświetlono zawartość Panelu Hurtownika: " . ($hasAdminPanelTitle ? "OK" : "BŁĄD") . "\n";

@unlink($cookieFile);

$passed = ($isRedirectedToAdmin && $hasAdminPanelTitle);
echo $passed ? "=== TEST LOGOWANIA HURTOWNIKA PRZESZEDŁ POMYŚLNIE ===\n" : "=== TEST LOGOWANIA NIE PRZESZEDŁ ===\n";
exit($passed ? 0 : 1);
