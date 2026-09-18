<?php
define('BASE_PATH', 'C:/laragon/www');
require_once BASE_PATH . '/program/config/data.php';

$cookieFile = tempnam(sys_get_temp_dir(), 'cook_test_');
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);

// Login
curl_setopt($ch, CURLOPT_URL, 'http://localhost/home/login');
$res = curl_exec($ch);
preg_match('/name="_csrf" value="([^"]+)"/', $res, $m);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'login' => APP_LOGIN,
    'password' => APP_PASSWORD,
    '_csrf' => $m[1] ?? ''
]));
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$res = curl_exec($ch);

// GET /home/index
curl_setopt($ch, CURLOPT_URL, 'http://localhost/home/index');
curl_setopt($ch, CURLOPT_HTTPGET, true);
$res = curl_exec($ch);

$hasName = strpos($res, 'Zamawiarka Magdy') !== false;
$hasDesc = strpos($res, 'Zrób zamówienie z pliku excel') !== false;
$noOldName = strpos($res, 'Zamówienia z cennika excel') === false;
$hasPlaceholder = strpos($res, 'Następny moduł') !== false;
$hasPlaceholderSub = strpos($res, 'może Ty masz pomysł co to może być?') !== false;

echo "1. Nowa nazwa modułu ('Zamawiarka Magdy'): " . ($hasName ? "OK" : "BŁĄD") . "\n";
echo "2. Nowy opis modułu ('Zrób zamówienie z pliku excel'): " . ($hasDesc ? "OK" : "BŁĄD") . "\n";
echo "3. Stara nazwa usunięta: " . ($noOldName ? "OK" : "BŁĄD") . "\n";
echo "4. Placeholder 'Następny moduł': " . ($hasPlaceholder ? "OK" : "BŁĄD") . "\n";
echo "5. Podpis placeholderu ('może Ty masz pomysł...'): " . ($hasPlaceholderSub ? "OK" : "BŁĄD") . "\n";

@unlink($cookieFile);
exit(($hasName && $hasDesc && $noOldName && $hasPlaceholder && $hasPlaceholderSub) ? 0 : 1);
