<?php
/**
 * Test renderowania widoku panelu hurtownika views/b2b/admin.php
 */
define('BASE_PATH', 'C:/laragon/www');
require_once BASE_PATH . '/program/config/data.php';

$cookieFile = tempnam(sys_get_temp_dir(), 'cook_admin_view_');
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);

// Logowanie admina
curl_setopt($ch, CURLOPT_URL, 'http://localhost/home/login');
$resLoginGet = curl_exec($ch);
preg_match('/name="_csrf" value="([^"]+)"/', $resLoginGet, $m);
$csrf = $m[1] ?? '';

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'login' => APP_LOGIN,
    'password' => APP_PASSWORD,
    '_csrf' => $csrf
]));
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_exec($ch);

// Pobranie /b2b/admin
curl_setopt($ch, CURLOPT_URL, 'http://localhost/b2b/admin');
curl_setopt($ch, CURLOPT_HTTPGET, true);
$html = curl_exec($ch);

echo "=== TEST: B2B Admin View ===\n";

$hasTailwind    = strpos($html, 'tailwindcss') !== false;
$hasTitle       = strpos($html, 'Hurtownia Magdy') !== false;
$hasTabProducts = strpos($html, 'tab-products') !== false;
$hasTabOrders   = strpos($html, 'tab-orders') !== false;
$hasTabClients  = strpos($html, 'tab-clients') !== false;
$hasDropzone    = strpos($html, 'dropzone') !== false || strpos($html, 'file-input') !== false;
$hasCopyBtn     = strpos($html, 'copy-token') !== false || strpos($html, 'copyToken') !== false;

echo "1. Tailwind CSS załadowany: " . ($hasTailwind ? "OK" : "BŁĄD") . "\n";
echo "2. Branding 'Hurtownia Magdy': " . ($hasTitle ? "OK" : "BŁĄD") . "\n";
echo "3. Zakładka 'Cennik & Oferta': " . ($hasTabProducts ? "OK" : "BŁĄD") . "\n";
echo "4. Zakładka 'Spływające Zamówienia': " . ($hasTabOrders ? "OK" : "BŁĄD") . "\n";
echo "5. Zakładka 'Klienci Hurtowni': " . ($hasTabClients ? "OK" : "BŁĄD") . "\n";
echo "6. Strefa Uploadu Excela: " . ($hasDropzone ? "OK" : "BŁĄD") . "\n";
echo "7. Kopiowanie linku z tokenem: " . ($hasCopyBtn ? "OK" : "BŁĄD") . "\n";

@unlink($cookieFile);

$passed = ($hasTailwind && $hasTitle && $hasTabProducts && $hasTabOrders && $hasTabClients && $hasDropzone && $hasCopyBtn);
exit($passed ? 0 : 1);
