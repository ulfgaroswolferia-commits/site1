<?php
/**
 * Kompletny test integracyjny E2E dla modułu eksportu ERP oraz harmonogramu dostaw z godziną graniczną (cut-off).
 */
define('BASE_PATH', 'C:/laragon/www');
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';
require_once BASE_PATH . '/program/config/includes.php';

use App\B2bRepository;
use App\ErpExporter;

echo "=== ROZPOCZĘCIE TESTU E2E: EKSPORT ERP I HARMONOGRAM DOSTAW CUT-OFF ===\n\n";

$repo = new B2bRepository();
$passed = true;

function check($desc, $cond) {
    global $passed;
    if ($cond) {
        echo " [OK] {$desc}\n";
    } else {
        echo " [BŁĄD] {$desc}\n";
        $passed = false;
    }
}

// -----------------------------------------------------------------------------
// 1. WAL Mode i pragmy SQLite
// -----------------------------------------------------------------------------
echo "1. Sprawdzanie trybu SQLite WAL i współbieżności...\n";
$journalMode = $repo->getPdo()->query("PRAGMA journal_mode")->fetchColumn();
check("Tryb journal_mode to WAL", strtolower((string)$journalMode) === 'wal');

$busyTimeout = $repo->getPdo()->query("PRAGMA busy_timeout")->fetchColumn();
check("Timeout blokady SQLite wynosi 5000 ms", (int)$busyTimeout === 5000);

// -----------------------------------------------------------------------------
// 2. Weryfikacja struktury bazy danych
// -----------------------------------------------------------------------------
echo "\n2. Weryfikacja rozszerzeń bazy danych (b2b_settings, erp_code, delivery_date)...\n";
$settingsCount = $repo->getPdo()->query("SELECT COUNT(*) FROM b2b_settings")->fetchColumn();
check("Tabela b2b_settings istnieje i zawiera wpisy", $settingsCount >= 3);

$ordersCols = $repo->getPdo()->query("PRAGMA table_info(b2b_orders)")->fetchAll();
$hasDeliveryDate = in_array('delivery_date', array_column($ordersCols, 'name'), true);
check("Kolumna delivery_date w b2b_orders istnieje", $hasDeliveryDate);

$productsCols = $repo->getPdo()->query("PRAGMA table_info(b2b_products)")->fetchAll();
$hasErpCode = in_array('erp_code', array_column($productsCols, 'name'), true);
check("Kolumna erp_code w b2b_products istnieje", $hasErpCode);

// -----------------------------------------------------------------------------
// 3. Logowanie hurtownika (administratora) przez cURL
// -----------------------------------------------------------------------------
echo "\n3. Autoryzacja administratora hurtowni...\n";
$cookieAdmin = tempnam(sys_get_temp_dir(), 'cook_adm_e2e_');
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieAdmin);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieAdmin);

curl_setopt($ch, CURLOPT_URL, 'http://localhost/home/login');
$loginHtml = curl_exec($ch);
preg_match('/name="_csrf" value="([^"]+)"/', $loginHtml, $mCsrf);
$adminLoginCsrf = $mCsrf[1] ?? '';

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'login'    => APP_LOGIN,
    'password' => APP_PASSWORD,
    '_csrf'    => $adminLoginCsrf
]));
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_exec($ch);

// Pobranie panelu hurtownika i CSRF
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, []);
curl_setopt($ch, CURLOPT_URL, 'http://localhost/b2b/admin');
$adminPanelHtml = curl_exec($ch);
preg_match('/const CSRF_TOKEN\s*=\s*\'([^\']+)\';/', $adminPanelHtml, $mAdminCsrf);
$adminCsrf = $mAdminCsrf[1] ?? '';
check("Zalogowano do panelu hurtownika i pobrano token CSRF", !empty($adminCsrf));
check("Panel zawiera zakładkę Ustawienia & ERP", strpos($adminPanelHtml, 'tab-btn-settings') !== false);
check("Panel zawiera kolumnę Kod ERP w cenniku", strpos($adminPanelHtml, 'Kod ERP') !== false);
check("Panel zawiera selektor paczki ERP w zamówieniach", strpos($adminPanelHtml, 'batch-erp-format') !== false);

// -----------------------------------------------------------------------------
// 4. Zapis konfiguracji hurtowni przez POST /b2b/savesettings
// -----------------------------------------------------------------------------
echo "\n4. Zapis konfiguracji hurtowni (/b2b/savesettings)...\n";
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_URL, 'http://localhost/b2b/savesettings');
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'cutoff_time'        => '21:45',
    'delivery_days'      => 'mon,tue,wed,thu,fri,sat',
    'default_erp_format' => 'optima',
    '_csrf'              => $adminCsrf
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Requested-With: XMLHttpRequest', 'Accept: application/json']);
$respSettings = curl_exec($ch);
$settingsJson = json_decode($respSettings, true);
check("Zapisano godzinę graniczną 21:45 i format optima", (!empty($settingsJson['ok']) && $repo->getSetting('cutoff_time') === '21:45' && $repo->getSetting('default_erp_format') === 'optima'));

// -----------------------------------------------------------------------------
// 5. Test logiki harmonogramu dostaw i cut-off
// -----------------------------------------------------------------------------
echo "\n5. Weryfikacja harmonogramu dostaw z cut-offem...\n";
// Wtorek 14:00 (przed 21:45) -> domyślna dostawa to środa
$tueAfternoon = strtotime('2026-09-22 14:00:00');
$schedBefore = $repo->getDeliverySchedule($tueAfternoon);
check("Przed cut-off: dostawa na jutro (2026-09-23)", $schedBefore['default_date'] === '2026-09-23' && !$schedBefore['is_cutoff_passed']);

// Wtorek 22:00 (po 21:45) -> zamówienia na środę rano zamknięte, domyślna dostawa to czwartek
$tueNight = strtotime('2026-09-22 22:00:00');
$schedAfter = $repo->getDeliverySchedule($tueNight);
check("Po cut-off: dostawa przesunięta na pojutrze (2026-09-24)", $schedAfter['default_date'] === '2026-09-24' && $schedAfter['is_cutoff_passed']);

// Sobota 22:00 -> niedziela wyłączona z dostaw, domyślna dostawa to poniedziałek
$satNight = strtotime('2026-09-26 22:00:00');
$schedSat = $repo->getDeliverySchedule($satNight);
check("Sobota wieczorem: pominięcie niedzieli, dostawa na poniedziałek (2026-09-28)", $schedSat['default_date'] === '2026-09-28');

// -----------------------------------------------------------------------------
// 6. Utworzenie produktu testowego z Kodem ERP oraz zamówienia ze sklepu B2B
// -----------------------------------------------------------------------------
echo "\n6. Tworzenie produktu z Kodem ERP i zamówienia B2B...\n";
$stmtProd = $repo->getPdo()->prepare("INSERT INTO b2b_products (name, erp_code, category, unit, price, package_size, package_unit, is_available, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)");
$stmtProd->execute(['Gruszka Konferencja E2E', 'GRUSZ-KONF-01', 'Owoce', 'kg', 6.50, 10.0, 'skrzynka', date('Y-m-d H:i:s')]);
$testProdId = (int)$repo->getPdo()->lastInsertId();
check("Utworzono produkt z kodem ERP GRUSZ-KONF-01", $testProdId > 0);

$orderId = $repo->createOrder([
    'order_number'              => 'B2B/E2E/TEST/' . time(),
    'client_id'                 => 1,
    'client_name_snapshot'      => 'Sklep Warzywny Zielony Zakątek',
    'delivery_address_snapshot' => 'ul. Zielona 25, 00-001 Warszawa',
    'delivery_date'             => '2026-09-24',
    'status'                    => 'new',
    'total_amount'              => 130.00,
    'notes'                     => 'Proszę o dostawę do godziny 7:00 rano'
], [
    [
        'product_id'      => $testProdId,
        'product_name'    => 'Gruszka Konferencja E2E',
        'price'           => 6.50,
        'quantity'        => 20.0,
        'unit'            => 'kg',
        'package_size'    => 10.0,
        'package_unit'    => 'skrzynka',
        'package_summary' => '2 skrzynki',
        'item_total'      => 130.00
    ]
]);
check("Zapisano zamówienie B2B z wybraną datą dostawy 2026-09-24", $orderId > 0);

$savedOrder = $repo->getOrderById($orderId);
check("Data dostawy w bazie to 2026-09-24", ($savedOrder['delivery_date'] ?? '') === '2026-09-24');

// -----------------------------------------------------------------------------
// 7. Eksport pojedynczego zamówienia do 4 formatów ERP
// -----------------------------------------------------------------------------
echo "\n7. Weryfikacja eksportu pojedynczego zamówienia do 4 formatów ERP...\n";
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, []);

// Subiekt GT / Nexo (.epp)
curl_setopt($ch, CURLOPT_URL, "http://localhost/b2b/exporterp?id={$orderId}&format=subiekt");
$epp = curl_exec($ch);
$subiektOk = (strpos($epp, '[INFO]') !== false && strpos($epp, 'GRUSZ-KONF-01') !== false && strpos($epp, 'Zielony') !== false);
check("Eksport Subiekt GT/Nexo (.epp EDI++ Windows-1250)", $subiektOk);

// Comarch ERP Optima (.xml)
curl_setopt($ch, CURLOPT_URL, "http://localhost/b2b/exporterp?id={$orderId}&format=optima");
$xmlOptima = curl_exec($ch);
$optimaOk = (strpos($xmlOptima, 'http://www.comarch.pl/cdn/optima/offline') !== false && strpos($xmlOptima, 'GRUSZ-KONF-01') !== false && strpos($xmlOptima, '2026-09-24') !== false);
check("Eksport Comarch ERP Optima (.xml z datą dostawy)", $optimaOk);

// Symfonia Handel (.txt)
curl_setopt($ch, CURLOPT_URL, "http://localhost/b2b/exporterp?id={$orderId}&format=symfonia");
$txtSymfonia = curl_exec($ch);
$symfoniaOk = (strpos($txtSymfonia, 'SymfoniaHandel') !== false && strpos($txtSymfonia, 'GRUSZ-KONF-01') !== false);
check("Eksport Symfonia Handel (.txt Windows-1250)", $symfoniaOk);

// Asseco WAPRO / Wf-Mag (.xml)
curl_setopt($ch, CURLOPT_URL, "http://localhost/b2b/exporterp?id={$orderId}&format=wfmag");
$xmlWfMag = curl_exec($ch);
$wfmagOk = (strpos($xmlWfMag, '<DOKUMENTY_MAGAZYNOWE') !== false && strpos($xmlWfMag, 'GRUSZ-KONF-01') !== false);
check("Eksport Asseco WAPRO / Wf-Mag (.xml)", $wfmagOk);

// -----------------------------------------------------------------------------
// 8. Zbiorczy eksport paczki zamówień (/b2b/exportbatch)
// -----------------------------------------------------------------------------
echo "\n8. Weryfikacja zbiorczego eksportu paczki dziennej ERP (/b2b/exportbatch)...\n";
curl_setopt($ch, CURLOPT_URL, "http://localhost/b2b/exportbatch?format=subiekt&status=new");
$batchSubiekt = curl_exec($ch);
check("Paczka zbiorcza Subiekt EPP dla nowych zamówień", strpos($batchSubiekt, 'GRUSZ-KONF-01') !== false);

curl_setopt($ch, CURLOPT_URL, "http://localhost/b2b/exportbatch?format=optima&date=2026-09-24");
$batchOptima = curl_exec($ch);
check("Paczka zbiorcza Optima XML dla dostaw 2026-09-24", strpos($batchOptima, 'GRUSZ-KONF-01') !== false);

// -----------------------------------------------------------------------------
// 9. Aktualizacja kodu ERP towaru przez POST /b2b/updateproduct
// -----------------------------------------------------------------------------
echo "\n9. Edycja kodu ERP w panelu hurtownika (/b2b/updateproduct)...\n";
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_URL, 'http://localhost/b2b/updateproduct');
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'id'           => $testProdId,
    'name'         => 'Gruszka Konferencja E2E Edytowana',
    'erp_code'     => 'GRUSZ-NOWY-KOD',
    'category'     => 'Owoce',
    'price'        => 6.80,
    'unit'         => 'kg',
    'package_size' => 12.0,
    'package_unit' => 'skrzynka',
    '_csrf'        => $adminCsrf
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Requested-With: XMLHttpRequest', 'Accept: application/json']);
$respUpd = curl_exec($ch);
$updJson = json_decode($respUpd, true);
$prodAfter = $repo->getProductById($testProdId);
check("Zaktualizowano kod ERP produktu na GRUSZ-NOWY-KOD", (!empty($updJson['ok']) && ($prodAfter['erp_code'] ?? '') === 'GRUSZ-NOWY-KOD'));

// -----------------------------------------------------------------------------
// 10. Sprzątanie danych testowych
// -----------------------------------------------------------------------------
curl_close($ch);
@unlink($cookieAdmin);

$repo->getPdo()->exec("DELETE FROM b2b_order_items WHERE order_id = {$orderId}");
$repo->getPdo()->exec("DELETE FROM b2b_orders WHERE id = {$orderId}");
$repo->getPdo()->exec("DELETE FROM b2b_products WHERE id = {$testProdId}");

// Przywrócenie domyślnych ustawień hurtowni
$repo->setSetting('cutoff_time', '21:30');
$repo->setSetting('delivery_days', 'mon,tue,wed,thu,fri,sat');
$repo->setSetting('default_erp_format', 'subiekt');

echo "\n-------------------------------------------------------------\n";
if ($passed) {
    echo "=== WSZYSTKIE TESTY E2E ZAKOŃCZONE SUKCESEM (GREEN) ===\n";
    exit(0);
} else {
    echo "=== TESTY E2E ZAKOŃCZONE BŁĘDEM (RED) ===\n";
    exit(1);
}
