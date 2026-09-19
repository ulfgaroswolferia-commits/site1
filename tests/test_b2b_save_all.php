<?php
/**
 * Test weryfikacyjny hurtowego zapisu zmian asortymentu (Zapisz wszystkie zmiany)
 * oraz endpointu POST /b2b/updateproductsbatch
 */
define('BASE_PATH', 'C:/laragon/www');
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';

$cookieFile = tempnam(sys_get_temp_dir(), 'cook_save_all_');
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

echo "=== TEST: Zapisz wszystkie zmiany w asortymencie (B2B Admin) ===\n";

// 1. Pobierz formularz logowania i odczytaj CSRF
$resGet = req('http://localhost/home/login');
preg_match('/name="_csrf"\s+value="([^"]+)"/', $resGet['body'], $m);
$csrf = $m[1] ?? '';

// 2. Zaloguj się jako hurtownik / admin
$resLogin = req('http://localhost/home/login', [
    'login'    => APP_LOGIN,
    'password' => APP_PASSWORD,
    '_csrf'    => $csrf
], true);

echo "1. Logowanie hurtownika: HTTP {$resLogin['code']}\n";

// 3. Pobierz panel hurtownika /b2b/admin
$resAdmin = req('http://localhost/b2b/admin');
$html = $resAdmin['body'];
echo "2. Pobranie panelu hurtownika: HTTP {$resAdmin['code']}\n";

// Pobierz token CSRF z panelu
preg_match('/const CSRF_TOKEN = "([^"]+)";/', $html, $mCsrf);
$adminCsrf = $mCsrf[1] ?? $csrf;

// 4. Weryfikacja elementów interfejsu
$hasAlertPill = (strpos($html, 'id="unsaved-alert-pill"') !== false);
$hasSaveAllBtn = (strpos($html, 'id="btn-save-all-products"') !== false);
$hasSaveAllCount = (strpos($html, 'id="btn-save-all-count"') !== false);
$hasSaveAllFunction = (strpos($html, 'function saveAllProducts()') !== false);
$hasCtrlS = (strpos($html, "e.key.toLowerCase() === 's'") !== false);

echo "3. Alert niezatwierdzonych zmian (#unsaved-alert-pill): " . ($hasAlertPill ? "OK" : "BŁĄD") . "\n";
echo "4. Przycisk 'Zapisz wszystkie zmiany' (#btn-save-all-products): " . ($hasSaveAllBtn ? "OK" : "BŁĄD") . "\n";
echo "5. Licznik zmian w przycisku (#btn-save-all-count): " . ($hasSaveAllCount ? "OK" : "BŁĄD") . "\n";
echo "6. Funkcja JavaScript saveAllProducts(): " . ($hasSaveAllFunction ? "OK" : "BŁĄD") . "\n";
echo "7. Obsługa skrótu klawiaturowego Ctrl+S / Cmd+S: " . ($hasCtrlS ? "OK" : "BŁĄD") . "\n";

// 5. Test endpointu POST /b2b/updateproductsbatch
$repo = new \App\B2bRepository();
$products = $repo->getAllProductsAdmin();

if (count($products) < 2) {
    echo "BŁĄD: Wymagane co najmniej 2 produkty w bazie do testu hurtowego zapisu.\n";
    exit(1);
}

$prod1 = $products[0];
$prod2 = $products[1];

$newPrice1 = round((float)$prod1['price'] + 1.25, 2);
$newPrice2 = round((float)$prod2['price'] + 0.75, 2);
$newPkgSize1 = 12.5;

$batchPayload = [
    [
        'id'           => $prod1['id'],
        'name'         => $prod1['name'],
        'category'     => $prod1['category'],
        'price'        => $newPrice1,
        'unit'         => $prod1['unit'],
        'package_size' => $newPkgSize1,
        'package_unit' => 'karton'
    ],
    [
        'id'           => $prod2['id'],
        'name'         => $prod2['name'],
        'category'     => $prod2['category'],
        'price'        => $newPrice2,
        'unit'         => $prod2['unit'],
        'package_size' => (float)$prod2['package_size'],
        'package_unit' => $prod2['package_unit']
    ]
];

$resBatch = req('http://localhost/b2b/updateproductsbatch', [
    'products' => json_encode($batchPayload),
    '_csrf'    => $adminCsrf
]);

echo "8. Wysłanie hurtowego zapisu na /b2b/updateproductsbatch: HTTP {$resBatch['code']}\n";
$batchJson = json_decode($resBatch['body'], true);
echo "   Odpowiedź JSON: " . json_encode($batchJson, JSON_UNESCAPED_UNICODE) . "\n";

$isBatchOk = !empty($batchJson['ok']) && (int)($batchJson['saved_count'] ?? 0) === 2;
echo "9. Sukces hurtowego zapisu (saved_count == 2): " . ($isBatchOk ? "OK" : "BŁĄD") . "\n";

// 6. Weryfikacja w bazie danych
$updatedProd1 = $repo->getProductById((int)$prod1['id']);
$updatedProd2 = $repo->getProductById((int)$prod2['id']);

$dbOk1 = (abs((float)$updatedProd1['price'] - $newPrice1) < 0.001) && (abs((float)$updatedProd1['package_size'] - $newPkgSize1) < 0.001);
$dbOk2 = (abs((float)$updatedProd2['price'] - $newPrice2) < 0.001);

echo "10. Weryfikacja produktu 1 w SQLite (cena: {$updatedProd1['price']}, opakowanie: {$updatedProd1['package_size']}): " . ($dbOk1 ? "OK" : "BŁĄD") . "\n";
echo "11. Weryfikacja produktu 2 w SQLite (cena: {$updatedProd2['price']}): " . ($dbOk2 ? "OK" : "BŁĄD") . "\n";

// Przywrócenie pierwotnych wartości
$repo->updateProduct((int)$prod1['id'], [
    'price'        => (float)$prod1['price'],
    'package_size' => (float)$prod1['package_size'],
    'package_unit' => $prod1['package_unit']
]);
$repo->updateProduct((int)$prod2['id'], [
    'price' => (float)$prod2['price']
]);

@unlink($cookieFile);

$passed = ($hasAlertPill && $hasSaveAllBtn && $hasSaveAllCount && $hasSaveAllFunction && $hasCtrlS && $isBatchOk && $dbOk1 && $dbOk2);
echo $passed ? "=== TEST HURTOWEGO ZAPISU ASORTYMENTU ZAKOŃCZONY SUKCESEM ===\n" : "=== TEST ZAKOŃCZONY BŁĘDEM ===\n";
exit($passed ? 0 : 1);
