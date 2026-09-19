<?php
/**
 * Test TDD: Zapamiętywanie jednostek i opakowań zbiorczych przy imporcie nowego cennika oraz oznaczanie nowości (is_new).
 */
define('BASE_PATH', 'C:/laragon\www');
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';
require_once BASE_PATH . '/program/config/includes.php';

use App\B2bRepository;

echo "=== TEST: Pamięć jednostek/opakowań przy imporcie i oznaczenie Nowość ===\n";

$repo = new B2bRepository();
$pdo = $repo->getPdo();
$pdo->exec("DELETE FROM b2b_products WHERE name LIKE '%Test'");
$pdo->exec("DELETE FROM b2b_package_rules WHERE name_pattern LIKE '%test%'");

// 1. Zapisujemy początkowy zestaw produktów ze skonfigurowanymi jednostkami i opakowaniami
$initialProducts = [
    [
        'name'         => 'Marchewka Polska Test',
        'category'     => 'Warzywa',
        'unit'         => 'kg',
        'price'        => 3.50,
        'package_size' => 10.0,
        'package_unit' => 'worek',
        'is_available' => 1,
    ],
    [
        'name'         => 'Koperek Świeży Test',
        'category'     => 'Zioła i sałaty',
        'unit'         => 'pęczek',
        'price'        => 2.00,
        'package_size' => 1.0,
        'package_unit' => 'op.',
        'is_available' => 1,
    ]
];

$repo->saveProductsBatch($initialProducts, true);


// 2. Wgrywamy nowy cennik (z nowymi cenami, nowym towarem i 'surowymi' domyślnymi wartościami z Excela)
$newPriceList = [
    [
        'name'         => 'Marchewka Polska Test',
        'price'        => 4.20,
        'unit'         => 'kg',
        'package_size' => 1.0, // Excel nie ma kolumny opakowań, podaje 1.0
        'package_unit' => 'op.',
    ],
    [
        'name'         => 'Koperek Świeży Test',
        'price'        => 2.80,
        'unit'         => 'kg',  // Excel miał 'kg', ale w panelu wcześniej ustawiono 'pęczek'
        'package_size' => 1.0,
        'package_unit' => 'op.',
    ],
    [
        'name'         => 'Dynia Piżmowa Test', // Nowy produkt
        'price'        => 5.50,
        'unit'         => 'kg',
        'package_size' => 1.0,
        'package_unit' => 'op.',
    ]
];

$repo->saveProductsBatch($newPriceList, true);

// 3. Weryfikacja bazy danych
$allProds = $repo->getAllProductsAdmin();
$prodMap = [];
foreach ($allProds as $p) {
    $prodMap[$p['name']] = $p;
}

// A. Marchewka powinna zachować opakowanie 10 kg worek i zaktualizować cenę na 4.20
$m = $prodMap['Marchewka Polska Test'] ?? null;
assert($m !== null, "Brak Marchewki w bazie!");
$marchewPkgOk = ((float)$m['package_size'] === 10.0 && $m['package_unit'] === 'worek');
$marchewPriceOk = ((float)$m['price'] === 4.20);
$marchewNotNew = (isset($m['is_new']) && (int)$m['is_new'] === 0);
echo "1. Marchewka zachowała opakowanie zbiorcze (10.0 worek): " . ($marchewPkgOk ? "PASS" : "FAIL (got {$m['package_size']} {$m['package_unit']})") . "\n";
echo "2. Marchewka zaktualizowała cenę na 4.20: " . ($marchewPriceOk ? "PASS" : "FAIL") . "\n";
echo "3. Marchewka nie jest oznaczona jako nowość (is_new = 0): " . ($marchewNotNew ? "PASS" : "FAIL") . "\n";

// B. Koperek powinien zachować jednostkę 'pęczek' i cenę 2.80
$k = $prodMap['Koperek Świeży Test'] ?? null;
assert($k !== null, "Brak Koperku w bazie!");
$koperekUnitOk = ($k['unit'] === 'pęczek');
$koperekPriceOk = ((float)$k['price'] === 2.80);
$koperekNotNew = (isset($k['is_new']) && (int)$k['is_new'] === 0);
echo "4. Koperek zachował wcześniej ustawioną jednostkę 'pęczek': " . ($koperekUnitOk ? "PASS" : "FAIL (got {$k['unit']})") . "\n";
echo "5. Koperek zaktualizował cenę na 2.80: " . ($koperekPriceOk ? "PASS" : "FAIL") . "\n";
echo "6. Koperek nie jest oznaczony jako nowość (is_new = 0): " . ($koperekNotNew ? "PASS" : "FAIL") . "\n";

// C. Dynia powinna być oznaczona jako Nowość (is_new = 1)
$d = $prodMap['Dynia Piżmowa Test'] ?? null;
assert($d !== null, "Brak Dyni w bazie!");
$dyniaNewOk = (isset($d['is_new']) && (int)$d['is_new'] === 1);
echo "7. Nowy produkt Dynia oznaczony jako Nowość (is_new = 1): " . ($dyniaNewOk ? "PASS" : "FAIL (got " . ($d['is_new'] ?? 'brak') . ")") . "\n";

// 4. Weryfikacja widoku panelu hurtownika
$cookieFile = tempnam(sys_get_temp_dir(), 'cook_new_badge_');
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);

// Logowanie
curl_setopt($ch, CURLOPT_URL, 'http://localhost/home/login');
$resLogin = curl_exec($ch);
preg_match('/name="_csrf" value="([^"]+)"/', $resLogin, $mCsrf);
$loginCsrf = $mCsrf[1] ?? '';

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'login' => APP_LOGIN,
    'password' => APP_PASSWORD,
    '_csrf' => $loginCsrf
]));
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_exec($ch);

curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_URL, 'http://localhost/b2b/admin');
$html = curl_exec($ch);
curl_close($ch);
@unlink($cookieFile);

$hasNowoscBadge = (strpos($html, 'Nowość') !== false);
echo "8. Widok admina zawiera etykietę 'Nowość': " . ($hasNowoscBadge ? "PASS" : "FAIL") . "\n";

// 5. Operator edytuje Dynię (ustawia opakowanie 5 kg karton) -> is_new powinno spaść do 0
$repo->updateProduct((int)$d['id'], [
    'package_size' => 5.0,
    'package_unit' => 'karton'
]);
$dAfterEdit = $repo->getProductById((int)$d['id']);
$dyniaResetNewOk = (isset($dAfterEdit['is_new']) && (int)$dAfterEdit['is_new'] === 0);
echo "9. Po zapisaniu zmian przez operatora, flaga Nowość została wyzerowana (is_new = 0): " . ($dyniaResetNewOk ? "PASS" : "FAIL") . "\n";

// 6. Kolejny import nowego cennika z surowymi danymi (Dynia znów w cenniku z pak. 1.0 op.)
$thirdPriceList = [
    [
        'name'         => 'Dynia Piżmowa Test',
        'price'        => 6.00,
        'unit'         => 'kg',
        'package_size' => 1.0,
        'package_unit' => 'op.',
    ]
];
$repo->saveProductsBatch($thirdPriceList, true);
$dThird = null;
foreach ($repo->getAllProductsAdmin() as $p) {
    if ($p['name'] === 'Dynia Piżmowa Test') {
        $dThird = $p;
        break;
    }
}
$dyniaPreservedPkgOk = ((float)$dThird['package_size'] === 5.0 && $dThird['package_unit'] === 'karton');
$dyniaStillNotNew = ((int)$dThird['is_new'] === 0);
echo "10. Po kolejnym imporcie Dynia zachowała ustalone opakowanie 5.0 karton: " . ($dyniaPreservedPkgOk ? "PASS" : "FAIL") . "\n";
echo "11. Po kolejnym imporcie Dynia nadal nie jest oznaczona jako nowość: " . ($dyniaStillNotNew ? "PASS" : "FAIL") . "\n";

// Sprzątanie po teście
$pdo->exec("DELETE FROM b2b_products WHERE name LIKE '%Test'");
$pdo->exec("DELETE FROM b2b_package_rules WHERE name_pattern LIKE '%test%'");

if (!$marchewPkgOk || !$marchewPriceOk || !$marchewNotNew || !$koperekUnitOk || !$koperekPriceOk || !$koperekNotNew || !$dyniaNewOk || !$hasNowoscBadge || !$dyniaResetNewOk || !$dyniaPreservedPkgOk || !$dyniaStillNotNew) {
    echo "=== TEST ZAKOŃCZONY BŁĘDEM (Stan RED) ===\n";
    exit(1);
}

echo "=== WSZYSTKIE TESTY PAMIĘCI JEDNOSTEK I NOWOŚCI ZAKOŃCZONE SUKCESEM ===\n";

