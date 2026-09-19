<?php
/**
 * Test TDD: Poprawność gramatyczna rozbicia opakowań (np. "2 klatki", "2 worki + 5 kg", "5 klatek", "5 worków")
 */
define('BASE_PATH', 'C:/laragon/www');
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';
require_once BASE_PATH . '/program/config/includes.php';

use App\B2bRepository;

echo "=== TEST: Poprawność językowa rozbicia opakowań B2B ===\n";

$repo = new B2bRepository();

// 1. Sprawdzenie metody odmiany przez przypadki inflectPolish()
$cases = [
    [1, 'klatka', 'klatka'],
    [2, 'klatka', 'klatki'],
    [4, 'klatka', 'klatki'],
    [5, 'klatka', 'klatek'],
    [12, 'klatka', 'klatek'],
    [22, 'klatka', 'klatki'],

    [1, 'worek', 'worek'],
    [2, 'worek', 'worki'],
    [4, 'worek', 'worki'],
    [5, 'worek', 'worków'],
    [14, 'worek', 'worków'],
    [23, 'worek', 'worki'],

    [1, 'skrzynka', 'skrzynka'],
    [2, 'skrzynka', 'skrzynki'],
    [5, 'skrzynka', 'skrzynek'],

    [1, 'karton', 'karton'],
    [2, 'karton', 'kartony'],
    [5, 'karton', 'kartonów'],

    [1, 'op.', 'op.'],
    [2, 'op.', 'op.'],
    [5, 'op.', 'op.'],

    [1, 'kg', 'kg'],
    [2, 'kg', 'kg'],
    [5, 'kg', 'kg'],
];

$allCasesOk = true;
if (method_exists($repo, 'inflectPolish')) {
    foreach ($cases as [$num, $unit, $expected]) {
        $actual = B2bRepository::inflectPolish($num, $unit);
        if ($actual !== $expected) {
            echo "FAIL: inflectPolish({$num}, '{$unit}') => '{$actual}', oczekiwano '{$expected}'\n";
            $allCasesOk = false;
        }
    }
    if ($allCasesOk) {
        echo "1. inflectPolish() poprawnie odmienia wszystkie jednostki: PASS\n";
    }
} else {
    echo "FAIL: Metoda B2bRepository::inflectPolish() nie istnieje!\n";
    $allCasesOk = false;
}

// 2. Sprawdzenie formatPackageSummary()
$summaryCases = [
    // [quantity, packageSize, packageUnit, unit, expected]
    [20.0, 10.0, 'klatka', 'szt.', '2 klatki'],
    [14.0, 7.0, 'klatka', 'szt.', '2 klatki'],
    [7.0, 7.0, 'klatka', 'szt.', '1 klatka'],
    [35.0, 7.0, 'klatka', 'szt.', '5 klatek'],

    [17.0, 6.0, 'worek', 'kg', '2 worki + 5 kg'],
    [12.0, 5.0, 'worek', 'kg', '2 worki + 2 kg'],
    [5.0, 5.0, 'worek', 'kg', '1 worek'],
    [25.0, 5.0, 'worek', 'kg', '5 worków'],
    [31.0, 5.0, 'worek', 'kg', '6 worków + 1 kg'],

    [15.0, 6.0, 'skrzynka', 'kg', '2 skrzynki + 3 kg'],
    [30.0, 6.0, 'skrzynka', 'kg', '5 skrzynek'],
];

$allSummariesOk = true;
foreach ($summaryCases as [$qty, $pkgSize, $pkgUnit, $unit, $expected]) {
    $actual = $repo->formatPackageSummary((float)$qty, (float)$pkgSize, $pkgUnit, $unit);
    if ($actual !== $expected) {
        echo "FAIL: formatPackageSummary({$qty}, {$pkgSize}, '{$pkgUnit}', '{$unit}') => '{$actual}', oczekiwano '{$expected}'\n";
        $allSummariesOk = false;
    }
}
if ($allSummariesOk) {
    echo "2. formatPackageSummary() generuje poprawne rozbicie gramatyczne: PASS\n";
}

// 3. Sprawdzenie katalogu JS (catalog.php)
$catalogSource = file_get_contents(BASE_PATH . '/views/b2b/catalog.php');
$hasJsInflection = strpos($catalogSource, 'inflectPolishJs') !== false;
echo "3. Funkcja inflectPolishJs w JavaScript (catalog.php): " . ($hasJsInflection ? "PASS" : "FAIL") . "\n";

// 4. Sprawdzenie endpointu b2b/orderdetails (czy zwraca poprawione rozbicie)
$cookieFile = tempnam(sys_get_temp_dir(), 'cook_grammar_');
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);

// Logowanie hurtownika
$resLoginGet = curl_exec($ch);
curl_setopt($ch, CURLOPT_URL, 'http://localhost/home/login');
$resLogin = curl_exec($ch);
preg_match('/name="_csrf" value="([^"]+)"/', $resLogin, $m);
$loginCsrf = $m[1] ?? '';

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'login' => APP_LOGIN,
    'password' => APP_PASSWORD,
    '_csrf' => $loginCsrf
]));
curl_exec($ch);

// Utworzenie zamówienia z niepoprawnym zapisem w bazie ("2 klatka", "2 worek + 5 kg") i sprawdzenie czy orderdetails to koryguje
$orderNumber = $repo->generateOrderNumber();
$orderId = $repo->createOrder([
    'order_number'              => $orderNumber,
    'client_id'                 => 1,
    'client_name_snapshot'      => 'Klient Test Gramatyka',
    'client_phone_snapshot'     => '123456789',
    'delivery_address_snapshot' => 'ul. Gramatyczna 1',
    'total_amount'              => 100.00,
    'notes'                     => 'Test'
], [
    [
        'product_id'      => 1,
        'product_name'    => 'Mango',
        'price'           => 10.0,
        'quantity'        => 14,
        'unit'            => 'szt.',
        'package_size'    => 7.0,
        'package_unit'    => 'klatka',
        'package_summary' => '2 klatka', // symulacja starego wpisu
        'item_total'      => 140.0
    ],
    [
        'product_id'      => 2,
        'product_name'    => 'Ziemniaki',
        'price'           => 2.0,
        'quantity'        => 17,
        'unit'            => 'kg',
        'package_size'    => 6.0,
        'package_unit'    => 'worek',
        'package_summary' => '2 worek + 5 kg', // symulacja starego wpisu
        'item_total'      => 34.0
    ]
]);

curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_URL, "http://localhost/b2b/orderdetails?id={$orderId}");
$resDetails = curl_exec($ch);
$detailsData = json_decode($resDetails, true);

$items = $detailsData['items'] ?? [];
$item1Summary = $items[0]['package_summary'] ?? '';
$item2Summary = $items[1]['package_summary'] ?? '';

echo "4. Szczegóły zamówienia (ID: {$orderId}):\n";
echo "   - Pozycja 1: {$item1Summary} (oczekiwano '2 klatki')\n";
echo "   - Pozycja 2: {$item2Summary} (oczekiwano '2 worki + 5 kg')\n";

$detailsOk = ($item1Summary === '2 klatki' && $item2Summary === '2 worki + 5 kg');
echo "   Wynik korygowania w orderdetails: " . ($detailsOk ? "PASS" : "FAIL") . "\n";

if (!$allCasesOk || !$allSummariesOk || !$hasJsInflection || !$detailsOk) {
    echo "=== TESTY ZAKOŃCZONE BŁĘDEM ===\n";
    exit(1);
}

echo "=== WSZYSTKIE TESTY GRAMATYKI ZAKOŃCZONE SUKCESEM ===\n";
