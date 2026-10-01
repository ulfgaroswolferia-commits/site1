<?php
/**
 * Test weryfikujÄ…cy mechanizm dirty-state (podkreĹ›lenie edytowanych pozycji)
 * oraz rekomendacjÄ™ zatwierdzenia zmian w panelu hurtownika views/b2b/admin.php
 */
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
require_once BASE_PATH . '/program/config/data.php';

$cookieFile = tempnam(sys_get_temp_dir(), 'cook_dirty_state_');
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);

// 1. Logowanie admina
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

// 2. Pobranie panelu hurtownika
curl_setopt($ch, CURLOPT_URL, 'http://localhost/b2b/admin');
curl_setopt($ch, CURLOPT_HTTPGET, true);
$html = curl_exec($ch);

echo "=== TEST: B2B Dirty State & Save Action UX ===\n";

$checks = [
    'WskaĹşnik niezapisanych zmian (unsaved-alert-pill)' => strpos($html, 'id="unsaved-alert-pill"') !== false,
    'Wiersze z klasÄ… prod-row i data-prod-id'           => strpos($html, 'prod-row') !== false && strpos($html, 'data-prod-id=') !== false,
    'Etykieta dirty badge (dirty-badge-)'               => strpos($html, 'id="dirty-badge-') !== false,
    'Pola edycyjne z klasÄ… prod-field i data-initial'   => strpos($html, 'prod-field') !== false && strpos($html, 'data-initial=') !== false,
    'Przycisk zapisu z etykietÄ… tekstowÄ… save-label'    => strpos($html, 'save-label-') !== false,
    'Funkcja JS checkFieldDirty()'                      => strpos($html, 'function checkFieldDirty(') !== false,
    'Funkcja JS updateRowDirtyState()'                  => strpos($html, 'function updateRowDirtyState(') !== false,
    'Funkcja JS updateGlobalUnsavedCount()'             => strpos($html, 'function updateGlobalUnsavedCount(') !== false,
    'ObsĹ‚uga eventĂłw input i change w tabeli'           => strpos($html, "addEventListener('input'") !== false && strpos($html, "addEventListener('change'") !== false,
    'ObsĹ‚uga klawisza Enter w wierszu'                  => strpos($html, "e.key === 'Enter'") !== false,
    'Ochrona beforeunload przed utratÄ… zmian'           => strpos($html, "addEventListener('beforeunload'") !== false,
    'Animacja sukcesu i stan Ĺ‚adowania w saveProduct'   => strpos($html, 'Zapisywanie...') !== false && strpos($html, 'Zatwierdzono!') !== false,
];

$allPassed = true;
$idx = 1;
foreach ($checks as $name => $ok) {
    echo "{$idx}. {$name}: " . ($ok ? "OK" : "BĹÄ„D") . "\n";
    if (!$ok) $allPassed = false;
    $idx++;
}

@unlink($cookieFile);

echo $allPassed ? "=== ALL DIRTY STATE UX TESTS PASSED ===\n" : "=== TESTS FAILED ===\n";
exit($allPassed ? 0 : 1);
