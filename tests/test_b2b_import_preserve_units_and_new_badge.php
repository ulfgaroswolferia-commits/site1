<?php
/**
 * Test TDD: ZapamiÄ™tywanie jednostek i opakowaĹ„ zbiorczych przy imporcie nowego cennika oraz oznaczanie nowoĹ›ci (is_new).
 */
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';
require_once BASE_PATH . '/program/config/includes.php';

use App\B2bRepository;

echo "=== TEST: PamiÄ™Ä‡ jednostek/opakowaĹ„ przy imporcie i oznaczenie NowoĹ›Ä‡ ===\n";

$repo = new B2bRepository();
$pdo = $repo->getPdo();
$pdo->exec("DELETE FROM b2b_products WHERE name LIKE '%Test'");
$pdo->exec("DELETE FROM b2b_package_rules WHERE name_pattern LIKE '%test%'");

// 1. Zapisujemy poczÄ…tkowy zestaw produktĂłw ze skonfigurowanymi jednostkami i opakowaniami
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
        'name'         => 'Koperek ĹšwieĹĽy Test',
        'category'     => 'ZioĹ‚a i saĹ‚aty',
        'unit'         => 'pÄ™czek',
        'price'        => 2.00,
        'package_size' => 1.0,
        'package_unit' => 'op.',
        'is_available' => 1,
    ]
];

$repo->saveProductsBatch($initialProducts, true);


// 2. Wgrywamy nowy cennik (z nowymi cenami, nowym towarem i 'surowymi' domyĹ›lnymi wartoĹ›ciami z Excela)
$newPriceList = [
    [
        'name'         => 'Marchewka Polska Test',
        'price'        => 4.20,
        'unit'         => 'kg',
        'package_size' => 1.0, // Excel nie ma kolumny opakowaĹ„, podaje 1.0
        'package_unit' => 'op.',
    ],
    [
        'name'         => 'Koperek ĹšwieĹĽy Test',
        'price'        => 2.80,
        'unit'         => 'kg',  // Excel miaĹ‚ 'kg', ale w panelu wczeĹ›niej ustawiono 'pÄ™czek'
        'package_size' => 1.0,
        'package_unit' => 'op.',
    ],
    [
        'name'         => 'Dynia PiĹĽmowa Test', // Nowy produkt
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

// A. Marchewka powinna zachowaÄ‡ opakowanie 10 kg worek i zaktualizowaÄ‡ cenÄ™ na 4.20
$m = $prodMap['Marchewka Polska Test'] ?? null;
assert($m !== null, "Brak Marchewki w bazie!");
$marchewPkgOk = ((float)$m['package_size'] === 10.0 && $m['package_unit'] === 'worek');
$marchewPriceOk = ((float)$m['price'] === 4.20);
$marchewNotNew = (isset($m['is_new']) && (int)$m['is_new'] === 0);
echo "1. Marchewka zachowaĹ‚a opakowanie zbiorcze (10.0 worek): " . ($marchewPkgOk ? "PASS" : "FAIL (got {$m['package_size']} {$m['package_unit']})") . "\n";
echo "2. Marchewka zaktualizowaĹ‚a cenÄ™ na 4.20: " . ($marchewPriceOk ? "PASS" : "FAIL") . "\n";
echo "3. Marchewka nie jest oznaczona jako nowoĹ›Ä‡ (is_new = 0): " . ($marchewNotNew ? "PASS" : "FAIL") . "\n";

// B. Koperek powinien zachowaÄ‡ jednostkÄ™ 'pÄ™czek' i cenÄ™ 2.80
$k = $prodMap['Koperek ĹšwieĹĽy Test'] ?? null;
assert($k !== null, "Brak Koperku w bazie!");
$koperekUnitOk = ($k['unit'] === 'pÄ™czek');
$koperekPriceOk = ((float)$k['price'] === 2.80);
$koperekNotNew = (isset($k['is_new']) && (int)$k['is_new'] === 0);
echo "4. Koperek zachowaĹ‚ wczeĹ›niej ustawionÄ… jednostkÄ™ 'pÄ™czek': " . ($koperekUnitOk ? "PASS" : "FAIL (got {$k['unit']})") . "\n";
echo "5. Koperek zaktualizowaĹ‚ cenÄ™ na 2.80: " . ($koperekPriceOk ? "PASS" : "FAIL") . "\n";
echo "6. Koperek nie jest oznaczony jako nowoĹ›Ä‡ (is_new = 0): " . ($koperekNotNew ? "PASS" : "FAIL") . "\n";

// C. Dynia powinna byÄ‡ oznaczona jako NowoĹ›Ä‡ (is_new = 1)
$d = $prodMap['Dynia PiĹĽmowa Test'] ?? null;
assert($d !== null, "Brak Dyni w bazie!");
$dyniaNewOk = (isset($d['is_new']) && (int)$d['is_new'] === 1);
echo "7. Nowy produkt Dynia oznaczony jako NowoĹ›Ä‡ (is_new = 1): " . ($dyniaNewOk ? "PASS" : "FAIL (got " . ($d['is_new'] ?? 'brak') . ")") . "\n";

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

$hasNowoscBadge = (strpos($html, 'NowoĹ›Ä‡') !== false);
echo "8. Widok admina zawiera etykietÄ™ 'NowoĹ›Ä‡': " . ($hasNowoscBadge ? "PASS" : "FAIL") . "\n";

// 5. Operator edytuje DyniÄ™ (ustawia opakowanie 5 kg karton) -> is_new powinno spaĹ›Ä‡ do 0
$repo->updateProduct((int)$d['id'], [
    'package_size' => 5.0,
    'package_unit' => 'karton'
]);
$dAfterEdit = $repo->getProductById((int)$d['id']);
$dyniaResetNewOk = (isset($dAfterEdit['is_new']) && (int)$dAfterEdit['is_new'] === 0);
echo "9. Po zapisaniu zmian przez operatora, flaga NowoĹ›Ä‡ zostaĹ‚a wyzerowana (is_new = 0): " . ($dyniaResetNewOk ? "PASS" : "FAIL") . "\n";

// 6. Kolejny import nowego cennika z surowymi danymi (Dynia znĂłw w cenniku z pak. 1.0 op.)
$thirdPriceList = [
    [
        'name'         => 'Dynia PiĹĽmowa Test',
        'price'        => 6.00,
        'unit'         => 'kg',
        'package_size' => 1.0,
        'package_unit' => 'op.',
    ]
];
$repo->saveProductsBatch($thirdPriceList, true);
$dThird = null;
foreach ($repo->getAllProductsAdmin() as $p) {
    if ($p['name'] === 'Dynia PiĹĽmowa Test') {
        $dThird = $p;
        break;
    }
}
$dyniaPreservedPkgOk = ((float)$dThird['package_size'] === 5.0 && $dThird['package_unit'] === 'karton');
$dyniaStillNotNew = ((int)$dThird['is_new'] === 0);
echo "10. Po kolejnym imporcie Dynia zachowaĹ‚a ustalone opakowanie 5.0 karton: " . ($dyniaPreservedPkgOk ? "PASS" : "FAIL") . "\n";
echo "11. Po kolejnym imporcie Dynia nadal nie jest oznaczona jako nowoĹ›Ä‡: " . ($dyniaStillNotNew ? "PASS" : "FAIL") . "\n";

// SprzÄ…tanie po teĹ›cie
$pdo->exec("DELETE FROM b2b_products WHERE name LIKE '%Test'");
$pdo->exec("DELETE FROM b2b_package_rules WHERE name_pattern LIKE '%test%'");

if (!$marchewPkgOk || !$marchewPriceOk || !$marchewNotNew || !$koperekUnitOk || !$koperekPriceOk || !$koperekNotNew || !$dyniaNewOk || !$hasNowoscBadge || !$dyniaResetNewOk || !$dyniaPreservedPkgOk || !$dyniaStillNotNew) {
    echo "=== TEST ZAKOĹCZONY BĹÄDEM (Stan RED) ===\n";
    exit(1);
}

echo "=== WSZYSTKIE TESTY PAMIÄCI JEDNOSTEK I NOWOĹšCI ZAKOĹCZONE SUKCESEM ===\n";

