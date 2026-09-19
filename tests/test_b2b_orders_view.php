<?php
/**
 * Test weryfikacyjny widoku spływających zamówień w panelu hurtownika:
 * - brak osobnej kolumny "Akcje"
 * - link do szczegółów umieszczony pod numerem zamówienia
 */
define('BASE_PATH', 'C:/laragon/www');
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

echo "=== TEST: Widok Spływających Zamówień (Usunięcie kolumny Akcje, link pod numerem) ===\n";

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

// 2. Pobranie panelu hurtownika z zakładką orders
$resAdmin = req('http://localhost/b2b/admin?tab=orders');
$html = $resAdmin['body'];
echo "2. Pobranie panelu hurtownika (tab=orders): HTTP {$resAdmin['code']}\n";

// Wyizoluj sekcję tab-orders
preg_match('/<main id="tab-orders"[^>]*>(.*?)<\/main>/s', $html, $mOrders);
$tabOrdersHtml = $mOrders[1] ?? '';

if (empty($tabOrdersHtml)) {
    echo "BŁĄD: Nie znaleziono sekcji tab-orders w HTML.\n";
    exit(1);
}

// 3. Sprawdź, czy w nagłówku tabeli usunięto kolumnę "Akcje"
$hasActionsHeader = preg_match('/<th[^>]*>\s*Akcje\s*<\/th>/i', $tabOrdersHtml);
echo "3. Brak kolumny 'Akcje' w thead: " . (!$hasActionsHeader ? "OK (usunięto)" : "BŁĄD (nadal występuje)") . "\n";

// 4. Policz kolumny w nagłówku thead (powinno być 7)
preg_match('/<table[^>]*>.*?<thead[^>]*>(.*?)<\/thead>/is', $tabOrdersHtml, $mThead);
$theadContent = $mThead[1] ?? '';
preg_match_all('/<th[^>]*>(.*?)<\/th>/is', $theadContent, $thMatches);
$thCount = count($thMatches[0]);
echo "4. Liczba kolumn w thead: {$thCount} (oczekiwano 7)\n";
foreach ($thMatches[1] as $idx => $thText) {
    echo "   - Kolumna " . ($idx + 1) . ": " . trim(strip_tags($thText)) . "\n";
}

// 5. Sprawdź czy link "Szczegóły zamówienia" jest pod numerem zamówienia
$hasLinkUnderNumber = (strpos($tabOrdersHtml, 'Szczegóły zamówienia') !== false && strpos($tabOrdersHtml, 'showOrderModal(') !== false);
echo "5. Link do szczegółów zamówienia zintegrowany przy/pod numerem: " . ($hasLinkUnderNumber ? "OK" : "BŁĄD") . "\n";

// 6. Sprawdź czy modal zamówienia nadal istnieje w dokumencie
$hasOrderModal = (strpos($html, 'id="modal-order"') !== false);
echo "6. Okno modalne szczegółów zamówienia obecne w HTML: " . ($hasOrderModal ? "OK" : "BŁĄD") . "\n";

@unlink($cookieFile);

$passed = (!$hasActionsHeader && $thCount === 7 && $hasLinkUnderNumber && $hasOrderModal);
echo $passed ? "=== TEST WIDOKU ZAMÓWIEŃ ZAKOŃCZONY SUKCESEM ===\n" : "=== TEST ZAKOŃCZONY BŁĘDEM ===\n";
exit($passed ? 0 : 1);
