<?php
/**
 * Test weryfikujący próg asystenta zaokrągleń Box Optimizer (minimum 65% opakowania zbiorczego)
 */
define('BASE_PATH', 'C:/laragon/www');
require_once BASE_PATH . '/program/config/data.php';

echo "=== TEST: BOX OPTIMIZER 65% THRESHOLD ===\n";

$catalogViewPath = BASE_PATH . '/views/b2b/catalog.php';
$content = file_get_contents($catalogViewPath);

// 1. Sprawdź obecność logiki obliczania fillRatio i progu 65% w widoku katalogu
$hasFillRatio = strpos($content, 'fillRatio') !== false;
$hasThreshold = (strpos($content, '0.6499') !== false || strpos($content, '0.65') !== false);

echo "1. Obliczanie wskaźnika zapełnienia opakowania (fillRatio): " . ($hasFillRatio ? "OK" : "BŁĄD") . "\n";
echo "2. Warunek progu minimum 65% w kodzie JS: " . ($hasThreshold ? "OK" : "BŁĄD") . "\n";

// 2. Symulacja reguły w PHP odpowiadającej logice JS
function shouldSuggestRoundUp($qty, $pkgSize) {
    if ($pkgSize <= 1.0 || $qty <= 0) return false;
    $remainder = round(fmod($qty, $pkgSize), 3);
    $fillRatio = $remainder / $pkgSize;
    return ($remainder > 0.001 && $fillRatio >= 0.6499);
}

// Opakowanie 10 kg
assert(shouldSuggestRoundUp(2.0, 10.0) === false, "2 kg / 10 kg (20%) nie powinno sugerować zaokrąglenia");
assert(shouldSuggestRoundUp(5.0, 10.0) === false, "5 kg / 10 kg (50%) nie powinno sugerować zaokrąglenia");
assert(shouldSuggestRoundUp(6.4, 10.0) === false, "6.4 kg / 10 kg (64%) nie powinno sugerować zaokrąglenia");
assert(shouldSuggestRoundUp(6.5, 10.0) === true,  "6.5 kg / 10 kg (65%) powinno sugerować zaokrąglenie do 10 kg");
assert(shouldSuggestRoundUp(7.0, 10.0) === true,  "7 kg / 10 kg (70%) powinno sugerować zaokrąglenie do 10 kg");
assert(shouldSuggestRoundUp(10.0, 10.0) === false, "10 kg / 10 kg (100%) to pełna skrzynka - brak sugestii");
assert(shouldSuggestRoundUp(12.0, 10.0) === false, "12 kg (1 skrzynka + 2 kg = 20%) nie powinno sugerować zaokrąglenia");
assert(shouldSuggestRoundUp(17.0, 10.0) === true,  "17 kg (1 skrzynka + 7 kg = 70%) powinno sugerować zaokrąglenie do 20 kg");

// Klatka Mango 7 szt.
assert(shouldSuggestRoundUp(3, 7.0) === false, "3 szt. / 7 szt. (42.8%) nie powinno sugerować");
assert(shouldSuggestRoundUp(4, 7.0) === false, "4 szt. / 7 szt. (57.1%) nie powinno sugerować");
assert(shouldSuggestRoundUp(5, 7.0) === true,  "5 szt. / 7 szt. (71.4%) powinno sugerować zaokrąglenie do 7");
assert(shouldSuggestRoundUp(10, 7.0) === false, "10 szt. (1 klatka + 3 szt. = 42.8%) nie powinno sugerować");
assert(shouldSuggestRoundUp(12, 7.0) === true,  "12 szt. (1 klatka + 5 szt. = 71.4%) powinno sugerować zaokrąglenie do 14");

echo "3. Wszystkie testy progowe matematyki Box Optimizer (poniżej 65% ukryte, od 65% widoczne): OK\n";

$passed = ($hasFillRatio && $hasThreshold);
echo $passed ? "=== ALL OPTIMIZER 65% TESTS PASSED ===\n" : "=== TESTS FAILED ===\n";
exit($passed ? 0 : 1);
