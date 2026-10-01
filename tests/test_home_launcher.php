<?php
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
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
$hasDesc = strpos($res, 'ZrĂłb zamĂłwienie z pliku excel') !== false;
$noOldName = strpos($res, 'ZamĂłwienia z cennika excel') === false;
$hasPlaceholder = strpos($res, 'NastÄ™pny moduĹ‚') !== false;
$hasPlaceholderSub = strpos($res, 'moĹĽe Ty masz pomysĹ‚ co to moĹĽe byÄ‡?') !== false;

echo "1. Nowa nazwa moduĹ‚u ('Zamawiarka Magdy'): " . ($hasName ? "OK" : "BĹÄ„D") . "\n";
echo "2. Nowy opis moduĹ‚u ('ZrĂłb zamĂłwienie z pliku excel'): " . ($hasDesc ? "OK" : "BĹÄ„D") . "\n";
echo "3. Stara nazwa usuniÄ™ta: " . ($noOldName ? "OK" : "BĹÄ„D") . "\n";
echo "4. Placeholder 'NastÄ™pny moduĹ‚': " . ($hasPlaceholder ? "OK" : "BĹÄ„D") . "\n";
echo "5. Podpis placeholderu ('moĹĽe Ty masz pomysĹ‚...'): " . ($hasPlaceholderSub ? "OK" : "BĹÄ„D") . "\n";

@unlink($cookieFile);
exit(($hasName && $hasDesc && $noOldName && $hasPlaceholder && $hasPlaceholderSub) ? 0 : 1);
