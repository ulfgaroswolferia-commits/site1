<?php
/**
 * Test weryfikujący ulepszenia bezpieczeństwa logowania tokenem B2B:
 * 1. Czyszczenie URL z tokenu (PRG pattern / redirect na czyste /b2b)
 * 2. Przekierowanie niepoprawnego tokenu z komunikatem błędu na /b2b/login
 * 3. Skrypt obronny history.replaceState w widoku catalog.php
 * 4. Reguła HTTPS w .htaccess z wykluczeniem localhost
 * 5. Nagłówek HSTS (Strict-Transport-Security) w headers.php
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';

echo "====================================================================\n";
echo "  TEST BEZPIECZEŃSTWA: TOKEN MAGIC-LINK I OCHRONA URL W B2B        \n";
echo "====================================================================\n\n";

$pass = 0;
$fail = 0;

function assertTest(string $desc, bool $cond) {
    global $pass, $fail;
    if ($cond) {
        echo "  [PASS] {$desc}\n";
        $pass++;
    } else {
        echo "  [FAIL] {$desc}\n";
        $fail++;
    }
}

// 1. Sprawdzenie reguł w .htaccess
$htaccess = file_get_contents(BASE_PATH . '/.htaccess');
assertTest(".htaccess zawiera regułę wymuszenia HTTPS", strpos($htaccess, 'RewriteCond %{HTTPS} off') !== false);
assertTest(".htaccess wyklucza localhost z wymuszenia HTTPS", strpos($htaccess, 'RewriteCond %{HTTP_HOST} !^(localhost|127\.0\.0\.1)') !== false);

// 2. Sprawdzenie nagłówków w headers.php
$headers = file_get_contents(BASE_PATH . '/program/config/headers.php');
assertTest("headers.php zawiera Strict-Transport-Security", strpos($headers, 'Strict-Transport-Security') !== false);
assertTest("headers.php zawiera Referrer-Policy", strpos($headers, 'Referrer-Policy: strict-origin-when-cross-origin') !== false);

// 3. Sprawdzenie widoków catalog.php i login.php
$catalogView = file_get_contents(BASE_PATH . '/views/b2b/catalog.php');
assertTest("catalog.php zawiera skrypt czyszczący token z paska adresu (history.replaceState)", strpos($catalogView, "url.searchParams.delete('token')") !== false);

$loginView = file_get_contents(BASE_PATH . '/views/b2b/login.php');
assertTest("login.php obsługuje wyświetlanie błędu (np. nieprawidłowy token)", strpos($loginView, 'htmlspecialchars($error)') !== false);

// 4. Sprawdzenie B2bController::actionIndex oraz actionLogin
$controller = file_get_contents(BASE_PATH . '/program/script/B2bController.php');
assertTest("B2bController po poprawnym tokenie wykonuje App::redirect('b2b')", strpos($controller, "App::redirect('b2b');") !== false);
assertTest("B2bController po błędnym tokenie przekierowuje do b2b/login z błędem", strpos($controller, "App::redirect('b2b/login?error=") !== false);
assertTest("B2bController::actionLogin obsługuje parametr \$_GET['error']", strpos($controller, "\$this->outputData['error'] = trim((string)\$_GET['error']);") !== false);

// 5. Test funkcjonalny bazy i repozytorium
$repo = new \App\B2bRepository();
$repo->initDatabase();

$testToken = bin2hex(random_bytes(16));
$clientId = $repo->createClient([
    'company_name'     => 'Sklep Bezpieczny Sp. z o.o.',
    'phone'            => '600700800',
    'delivery_address' => 'ul. Główna 1, Poznań',
    'auth_token'       => $testToken,
    'is_active'        => 1
]);

$foundClient = $repo->getClientByToken($testToken);
assertTest("Repozytorium odnajduje klienta po poprawnym tokenie", $foundClient !== null && (int)$foundClient['id'] === (int)$clientId);

$invalidFound = $repo->getClientByToken('niepoprawny_token_123');
assertTest("Repozytorium zwraca null dla nieprawidłowego tokenu", $invalidFound === null);

// Zablokowanie klienta
$repo->toggleClientStatus((int)$clientId);
$blockedFound = $repo->getClientByToken($testToken);
assertTest("Zablokowany klient (is_active = 0) nie może zalogować się tokenem", $blockedFound === null);

// Przywrócenie
$repo->toggleClientStatus((int)$clientId);

echo "\n====================================================================\n";
echo "WYNIK: {$pass} zaliczonych, {$fail} niezaliczonych.\n";
echo "====================================================================\n";

if ($fail > 0) {
    exit(1);
}
