<?php
/**
 * Test weryfikujący zachowanie oferty B2B przy zmianie cennika dzień po dniu:
 * - Cennik 1 (Dzień 1) -> Konfiguracja opakowań -> Zamówienie klienta
 * - Cennik 2 (Dzień 2) -> Nowe ceny, wycofany towar, nowy towar
 * - Weryfikacja: co widzi klient, co dzieje się z pamięcią opakowań, co z historią zamówień
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';
require_once BASE_PATH . '/program/lib/ErpImporter.php';
require_once BASE_PATH . '/program/lib/ErpExporter.php';
require_once BASE_PATH . '/program/lib/XlsxParser.php';
require_once BASE_PATH . '/program/lib/XlsxWriter.php';
require_once BASE_PATH . '/program/lib/Tools.php';
require_once BASE_PATH . '/program/core/App.php';
require_once BASE_PATH . '/program/core/Controller.php';
require_once BASE_PATH . '/program/script/AppController.php';
require_once BASE_PATH . '/program/script/B2bController.php';

echo "====================================================================\n";
echo "  SYMULACJA ZMIANY CENNIKA: DZIEŃ 1 -> DZIEŃ 2 (REAKCJA KATALOGU)  \n";
echo "====================================================================\n\n";

$pass = 0;
$fail = 0;

function assertSim(string $desc, bool $result, string $details = ''): void
{
    global $pass, $fail;
    if ($result) {
        echo "  [PASS] {$desc}\n";
        $pass++;
    } else {
        echo "  [FAIL] {$desc}" . ($details ? " -> {$details}" : '') . "\n";
        $fail++;
    }
}

$repo = new \App\B2bRepository();

// =====================================================================
// KROK 1: DZIEŃ 1 — WDROŻENIE CENNIKA 1
// =====================================================================
echo "--- 1. DZIEŃ 1: WDROŻENIE CENNIKA 1 ---\n";

$cennik1 = [
    ['name' => 'Pomidor Malinowy Kaliber BB', 'price' => 8.00, 'unit' => 'kg', 'erp_code' => 'POM-MAL', 'category' => 'Warzywa'],
    ['name' => 'Truskawka Kaszubska Słodka',  'price' => 14.00, 'unit' => 'kg', 'erp_code' => 'TRUSK-KASZ', 'category' => 'Owoce'],
    ['name' => 'Koperek Świeży Pęczek',       'price' => 2.50, 'unit' => 'pęczek', 'erp_code' => 'KOP-SW', 'category' => 'Zioła i sałaty']
];

$count1 = $repo->saveProductsBatch($cennik1, true);
assertSim("Dzień 1: wgrano 3 produkty z cennika 1", $count1 === 3);

// Hurtownik w panelu konfiguruje Pomidorowi opakowanie: skrzynka 6 kg
$prodsD1 = $repo->getAllProductsAdmin();
$pomidorD1 = current(array_filter($prodsD1, fn($p) => $p['erp_code'] === 'POM-MAL'));
$truskawkaD1 = current(array_filter($prodsD1, fn($p) => $p['erp_code'] === 'TRUSK-KASZ'));
$repo->updateProduct($pomidorD1['id'], [
    'package_size' => 6.0,
    'package_unit' => 'skrzynka'
]);

// Klient składa zamówienie w Dniu 1 na Pomidora i Truskawkę
$clientOrderD1 = [
    [
        'product_id'      => $pomidorD1['id'],
        'name'            => 'Pomidor Malinowy Kaliber BB',
        'price'           => 8.00,
        'quantity'        => 12.0,
        'unit'            => 'kg',
        'package_size'    => 6.0,
        'package_unit'    => 'skrzynka',
        'package_summary' => '2 skrzynki',
        'item_total'      => 96.00
    ],
    [
        'product_id'      => $truskawkaD1['id'],
        'name'            => 'Truskawka Kaszubska Słodka',
        'price'           => 14.00,
        'quantity'        => 5.0,
        'unit'            => 'kg',
        'package_size'    => 1.0,
        'package_unit'    => 'op.',
        'package_summary' => '5 kg',
        'item_total'      => 70.00
    ]
];

$orderIdD1 = $repo->createOrder([
    'order_number'              => 'ZAM/D1/001',
    'client_id'                 => 1,
    'client_name_snapshot'      => 'Sklep Warzywny U Ani',
    'client_phone_snapshot'     => '500100200',
    'delivery_address_snapshot' => 'Gdańsk, ul. Długa 10',
    'delivery_date'             => date('Y-m-d'),
    'status'                    => 'new',
    'total_amount'              => 166.00,
    'notes'                     => 'Dostawa Dzień 1'
], $clientOrderD1);

assertSim("Dzień 1: klient złożył zamówienie #{$orderIdD1} na kwotę 166.00 zł", $orderIdD1 > 0);


// =====================================================================
// KROK 2: DZIEŃ 2 — WDROŻENIE CENNIKA 2 (KOLEJNY DZIEŃ)
// =====================================================================
echo "\n--- 2. DZIEŃ 2: WDROŻENIE CENNIKA 2 (NASTĘPNY DZIEŃ) ---\n";
// W cenniku 2:
// - Pomidor staniał z 8.00 na 7.50 zł
// - Truskawka brak (wyprzedana na giełdzie, brak w pliku)
// - Koperek cena bez zmian (2.50 zł)
// - Śliwka Węgierka nowy towar (6.80 zł)
$cennik2 = [
    ['name' => 'Pomidor Malinowy Kaliber BB', 'price' => 7.50, 'unit' => 'kg', 'erp_code' => 'POM-MAL'], // staniał
    ['name' => 'Koperek Świeży Pęczek',       'price' => 2.50, 'unit' => 'pęczek', 'erp_code' => 'KOP-SW'], // bez zmian
    ['name' => 'Śliwka Węgierka Dąbrowicka',  'price' => 6.80, 'unit' => 'kg', 'erp_code' => 'SLIW-WEG']  // nowy towar
];

$count2 = $repo->saveProductsBatch($cennik2, true);
assertSim("Dzień 2: wgrano 3 produkty z cennika 2", $count2 === 3);

$prodsD2 = $repo->getAllProductsAdmin();
$activeProdsD2 = $repo->getActiveProducts();


// =====================================================================
// KROK 3: WERYFIKACJA REAKCJI OFERTY DLA KLIENTA
// =====================================================================
echo "\n--- 3. WERYFIKACJA REAKCJI OFERTY DLA KLIENTA (KATALOG B2B) ---\n";

// A. Aktualizacja ceny
$pomidorD2 = current(array_filter($prodsD2, fn($p) => $p['erp_code'] === 'POM-MAL'));
assertSim("Reakcja 1 (Cena): Pomidor ma natychmiast nową cenę 7.50 zł (staniał o 50 gr)", 
    $pomidorD2 && abs((float)$pomidorD2['price'] - 7.50) < 0.01);

// B. Pamięć opakowań i logistyki
assertSim("Reakcja 2 (Pamięć opakowań): Pomidor zachował skrzynkę 6 kg z Dnia 1 mimo braku tej info w pliku 2", 
    $pomidorD2 && (float)$pomidorD2['package_size'] === 6.0 && $pomidorD2['package_unit'] === 'skrzynka');
assertSim("Reakcja 2b (Pamięć kategorii): Pomidor zachował kategorię 'Warzywa' z Dnia 1",
    $pomidorD2 && $pomidorD2['category'] === 'Warzywa');
assertSim("Reakcja 2c (Flaga nowości): Pomidor nie ma flagi nowości (is_new = 0)",
    $pomidorD2 && (int)$pomidorD2['is_new'] === 0);

// C. Wycofanie towaru brakującego w nowym cenniku
$truskawkaD2 = current(array_filter($prodsD2, fn($p) => $p['erp_code'] === 'TRUSK-KASZ'));
assertSim("Reakcja 3 (Wycofanie): Truskawka została usunięta z aktualnej oferty (brak na liście)", $truskawkaD2 === false);

$truskawkaInActive = current(array_filter($activeProdsD2, fn($p) => str_contains($p['name'], 'Truskawka')));
assertSim("Reakcja 3b (Niewidoczna dla klienta): Klient nie widzi Truskawki w katalogu", $truskawkaInActive === false);

// D. Nowy towar w cenniku 2
$sliwkaD2 = current(array_filter($prodsD2, fn($p) => $p['erp_code'] === 'SLIW-WEG'));
assertSim("Reakcja 4 (Nowość): Śliwka Węgierka pojawiła się w ofercie z ceną 6.80 zł", 
    $sliwkaD2 && abs((float)$sliwkaD2['price'] - 6.80) < 0.01);
assertSim("Reakcja 4b (Oznaczenie nowości): Śliwka ma flagę is_new = 1 (podświetlona jako nowość)", 
    $sliwkaD2 && (int)$sliwkaD2['is_new'] === 1);
assertSim("Reakcja 4c (Autodetekcja kategorii): Śliwka otrzymała automatycznie kategorię 'Owoce'", 
    $sliwkaD2 && $sliwkaD2['category'] === 'Owoce');


// =====================================================================
// KROK 4: WERYFIKACJA HISTORII I BEZPIECZEŃSTWA ZAMÓWIEŃ
// =====================================================================
echo "\n--- 4. WERYFIKACJA HISTORII ZAMÓWIEŃ I OCHRONY PRZED BRAKAMI ---\n";

// A. Sprawdzenie zamówienia z Dnia 1 w bazie
$orderItemsD1 = $repo->getOrderItems($orderIdD1);
$savedTruskawka = current(array_filter($orderItemsD1, fn($it) => str_contains($it['product_name'], 'Truskawka')));
$savedPomidor = current(array_filter($orderItemsD1, fn($it) => str_contains($it['product_name'], 'Pomidor')));

assertSim("Historia 1: Zamówienie z Dnia 1 zachowało pozycję Truskawka (ilość 5 kg, kwota 70 zł)", 
    $savedTruskawka && abs((float)$savedTruskawka['price'] - 14.00) < 0.01 && abs((float)$savedTruskawka['item_total'] - 70.00) < 0.01);
assertSim("Historia 2: Zamówienie z Dnia 1 zachowało starą cenę Pomidora (8.00 zł, a nie nową 7.50 zł)", 
    $savedPomidor && abs((float)$savedPomidor['price'] - 8.00) < 0.01);

// B. Ochrona: co jeśli klient miał otwartą kartę i próbuje wysłać zamówienie ze starym ID Truskawki z Dnia 1?
$attemptStaleOrder = false;
try {
    $repo->prepareOrderItems([
        ['product_id' => $truskawkaD1['id'], 'quantity' => 10]
    ]);
} catch (\InvalidArgumentException $e) {
    $attemptStaleOrder = true;
}
assertSim("Ochrona koszyka: próba zamówienia wycofanego produktu z nieaktualnej karty rzuca wyjątek niedostępności", $attemptStaleOrder);

echo "\n====================================================================\n";
echo "  PODSUMOWANIE SYMULACJI: PASS = {$pass}, FAIL = {$fail}\n";
echo "====================================================================\n";

if ($fail > 0) exit(1);
