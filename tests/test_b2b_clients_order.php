<?php
/**
 * Test weryfikacyjny sortowania klientĂłw hurtowni od ostatnio zarejestrowanego
 */
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';

$cookieFile = tempnam(sys_get_temp_dir(), 'cook_clients_order_');
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

echo "=== TEST: Sortowanie klientĂłw hurtowni (od najnowszego) ===\n";

// 1. Sprawdzenie sortowania w B2bRepository
$repo = new \App\B2bRepository();

// UtwĂłrz 2 klientĂłw testowych z odstÄ™pem czasowym / kolejnymi ID
$uniq = time() . '_' . rand(100, 999);
$id1 = $repo->createClient([
    'company_name'     => 'Firma Test A ' . $uniq,
    'phone'            => '111 222 333',
    'delivery_address' => 'Adres A'
]);
// Odczekaj chwilÄ™, aby stworzyÄ‡ drugiego klienta
usleep(50000);
$id2 = $repo->createClient([
    'company_name'     => 'Firma Test B (Najnowszy) ' . $uniq,
    'phone'            => '444 555 666',
    'delivery_address' => 'Adres B'
]);

$allClients = $repo->getAllClients();

echo "1. Klient 1 utworzony (ID: {$id1})\n";
echo "2. Klient 2 utworzony (ID: {$id2})\n";

// ID drugiego powinno pojawiÄ‡ siÄ™ przed ID pierwszego w tablicy
$pos1 = null;
$pos2 = null;
foreach ($allClients as $idx => $c) {
    if ((int)$c['id'] === $id1) $pos1 = $idx;
    if ((int)$c['id'] === $id2) $pos2 = $idx;
}

$repoOrderOk = ($pos2 !== null && $pos1 !== null && $pos2 < $pos1);
echo "3. Klient najnowszy (ID: {$id2}) jest wyĹĽej na liĹ›cie niĹĽ starszy (ID: {$id1}): " . ($repoOrderOk ? "OK (indeks {$pos2} < {$pos1})" : "BĹÄ„D") . "\n";

// 2. Logowanie hurtownika do panelu
$resLoginGet = req('http://localhost/home/login');
preg_match('/name="_csrf"\s+value="([^"]+)"/', $resLoginGet['body'], $m);
$csrf = $m[1] ?? '';

$resLogin = req('http://localhost/home/login', [
    'login'    => APP_LOGIN,
    'password' => APP_PASSWORD,
    '_csrf'    => $csrf
], true);

// 3. Pobranie widoku /b2b/admin?tab=clients
$resAdmin = req('http://localhost/b2b/admin?tab=clients');
$html = $resAdmin['body'];

$hasHeaderSubtext = (strpos($html, 'od ostatnio zarejestrowanego') !== false);
echo "4. PodtytuĹ‚ z informacjÄ… o sortowaniu w panelu: " . ($hasHeaderSubtext ? "OK" : "BĹÄ„D") . "\n";

$hasRegistrationDate = (strpos($html, 'Dodano:') !== false);
echo "5. Etykieta daty rejestracji przy odbiorcy: " . ($hasRegistrationDate ? "OK" : "BĹÄ„D") . "\n";

// Sprawdzenie kolejnoĹ›ci w wyrenderowanym HTML
$posHtml2 = strpos($html, 'client-row-' . $id2);
$posHtml1 = strpos($html, 'client-row-' . $id1);

$htmlOrderOk = ($posHtml2 !== false && $posHtml1 !== false && $posHtml2 < $posHtml1);
echo "6. KolejnoĹ›Ä‡ wierszy w tabeli HTML (nowy wiersz wyĹĽej niĹĽ starszy): " . ($htmlOrderOk ? "OK" : "BĹÄ„D") . "\n";

// SprzÄ…tanie rekordĂłw testowych
$pdo = $repo->getPdo();
$pdo->exec("DELETE FROM b2b_clients WHERE id IN ({$id1}, {$id2})");

@unlink($cookieFile);

$passed = ($repoOrderOk && $hasHeaderSubtext && $hasRegistrationDate && $htmlOrderOk);
echo $passed ? "=== TEST SORTOWANIA KLIENTĂ“W ZAKOĹCZONY SUKCESEM ===\n" : "=== TEST ZAKOĹCZONY BĹÄDEM ===\n";
exit($passed ? 0 : 1);
