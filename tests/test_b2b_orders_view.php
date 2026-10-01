<?php
/**
 * Test weryfikacyjny widoku spĹ‚ywajÄ…cych zamĂłwieĹ„ w panelu hurtownika:
 * - brak osobnej kolumny "Akcje"
 * - link do szczegĂłĹ‚Ăłw umieszczony pod numerem zamĂłwienia
 */
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';

$cookieFile = tempnam(sys_get_temp_dir(), 'cook_orders_view_');
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

echo "=== TEST: Widok SpĹ‚ywajÄ…cych ZamĂłwieĹ„ (UsuniÄ™cie kolumny Akcje, link pod numerem) ===\n";

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

// 2. Pobranie panelu hurtownika z zakĹ‚adkÄ… orders
$resAdmin = req('http://localhost/b2b/admin?tab=orders');
$html = $resAdmin['body'];
echo "2. Pobranie panelu hurtownika (tab=orders): HTTP {$resAdmin['code']}\n";

// Wyizoluj sekcjÄ™ tab-orders
preg_match('/<main id="tab-orders"[^>]*>(.*?)<\/main>/s', $html, $mOrders);
$tabOrdersHtml = $mOrders[1] ?? '';

if (empty($tabOrdersHtml)) {
    echo "BĹÄ„D: Nie znaleziono sekcji tab-orders w HTML.\n";
    exit(1);
}

// 3. SprawdĹş, czy w nagĹ‚Ăłwku tabeli usuniÄ™to kolumnÄ™ "Akcje"
$hasActionsHeader = preg_match('/<th[^>]*>\s*Akcje\s*<\/th>/i', $tabOrdersHtml);
echo "3. Brak kolumny 'Akcje' w thead: " . (!$hasActionsHeader ? "OK (usuniÄ™to)" : "BĹÄ„D (nadal wystÄ™puje)") . "\n";

// 4. Policz kolumny w nagĹ‚Ăłwku thead (powinno byÄ‡ 7)
preg_match('/<table[^>]*>.*?<thead[^>]*>(.*?)<\/thead>/is', $tabOrdersHtml, $mThead);
$theadContent = $mThead[1] ?? '';
preg_match_all('/<th[^>]*>(.*?)<\/th>/is', $theadContent, $thMatches);
$thCount = count($thMatches[0]);
echo "4. Liczba kolumn w thead: {$thCount} (oczekiwano 7)\n";
foreach ($thMatches[1] as $idx => $thText) {
    echo "   - Kolumna " . ($idx + 1) . ": " . trim(strip_tags($thText)) . "\n";
}

// 5. SprawdĹş czy link "SzczegĂłĹ‚y zamĂłwienia" jest pod numerem zamĂłwienia
$hasLinkUnderNumber = (strpos($tabOrdersHtml, 'SzczegĂłĹ‚y zamĂłwienia') !== false && strpos($tabOrdersHtml, 'showOrderModal(') !== false);
echo "5. Link do szczegĂłĹ‚Ăłw zamĂłwienia zintegrowany przy/pod numerem: " . ($hasLinkUnderNumber ? "OK" : "BĹÄ„D") . "\n";

// 6. SprawdĹş czy modal zamĂłwienia nadal istnieje w dokumencie
$hasOrderModal = (strpos($html, 'id="modal-order"') !== false);
echo "6. Okno modalne szczegĂłĹ‚Ăłw zamĂłwienia obecne w HTML: " . ($hasOrderModal ? "OK" : "BĹÄ„D") . "\n";

@unlink($cookieFile);

$passed = (!$hasActionsHeader && $thCount === 7 && $hasLinkUnderNumber && $hasOrderModal);
echo $passed ? "=== TEST WIDOKU ZAMĂ“WIEĹ ZAKOĹCZONY SUKCESEM ===\n" : "=== TEST ZAKOĹCZONY BĹÄDEM ===\n";
exit($passed ? 0 : 1);
