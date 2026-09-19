<?php
/**
 * Test weryfikacyjny wyszukiwarki klientów po nazwie w widoku Baza Odbiorców B2B
 */
define('BASE_PATH', 'C:/laragon/www');
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';

$cookieFile = tempnam(sys_get_temp_dir(), 'cook_client_search_');
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
        curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($post) ? http_build_query($post) : $post);
    } else {
        curl_setopt($ch, CURLOPT_HTTPGET, true);
    }
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $urlEff = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    return ['code' => $code, 'url' => $urlEff, 'body' => $body];
}

echo "=== TEST: Wyszukiwarka klientów po nazwie w Bazie Odbiorców B2B ===\n";

// 1. Logowanie administratora hurtowni
$resLoginGet = req('http://localhost/home/login');
preg_match('/name="_csrf"\s+value="([^"]+)"/', $resLoginGet['body'], $m);
$csrf = $m[1] ?? '';

$resLogin = req('http://localhost/home/login', [
    'login'    => APP_LOGIN,
    'password' => APP_PASSWORD,
    '_csrf'    => $csrf
], true);

echo "1. Logowanie hurtownika: HTTP {$resLogin['code']}\n";

// 2. Pobranie panelu hurtownika
$resAdmin = req('http://localhost/b2b/admin?tab=clients');
$html = $resAdmin['body'];
echo "2. Pobranie panelu hurtownika (tab=clients): HTTP {$resAdmin['code']}\n";

// 3. Weryfikacja elementów wyszukiwarki
$checks = [
    'Pole wyszukiwarki (#client-search-admin)'     => strpos($html, 'id="client-search-admin"') !== false,
    'Przycisk czyszczenia (#client-search-clear)' => strpos($html, 'id="client-search-clear"') !== false,
    'Licznik wyników (#clients-count-badge)'       => strpos($html, 'id="clients-count-badge"') !== false,
    'Wiersze z klasą client-data-row'             => strpos($html, 'client-data-row') !== false,
    'Atrybut data-client-name w wierszu klienta'  => strpos($html, 'data-client-name=') !== false,
    'Wiersz braku wyników (#no-clients-search-row)'=> strpos($html, 'id="no-clients-search-row"') !== false,
    'Funkcja JavaScript filterClients()'          => strpos($html, 'function filterClients(') !== false,
    'Funkcja JavaScript clearClientSearch()'      => strpos($html, 'function clearClientSearch(') !== false,
    'Rejestracja zdarzenia input dla wyszukiwarki'=> strpos($html, "clientSearchInput.addEventListener('input', filterClients)") !== false,
];

$allPassed = true;
$idx = 1;
foreach ($checks as $name => $ok) {
    echo "{$idx}. {$name}: " . ($ok ? "OK" : "BŁĄD") . "\n";
    if (!$ok) $allPassed = false;
    $idx++;
}

@unlink($cookieFile);

echo $allPassed ? "=== TEST WYSZUKIWARKI KLIENTÓW ZAKOŃCZONY SUKCESEM ===\n" : "=== TEST ZAKOŃCZONY BŁĘDEM ===\n";
exit($allPassed ? 0 : 1);
