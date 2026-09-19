<?php
/**
 * Test TDD: Usuwanie klienta B2B z bazy danych (w oknie edycji klienta)
 */
define('BASE_PATH', 'C:/laragon/www');
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';
require_once BASE_PATH . '/program/config/includes.php';

use App\B2bRepository;

echo "=== TEST: Usuwanie klienta B2B z poziomu modalu edycji ===\n";

$repo = new B2bRepository();

$cookieFile = tempnam(sys_get_temp_dir(), 'cook_del_client_');
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);

// 1. Logowanie administratora
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
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_exec($ch);

// Pobranie panelu i odczytanie CSRF
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_URL, 'http://localhost/b2b/admin');
$html = curl_exec($ch);

preg_match('/const CSRF_TOKEN\s*=\s*\'([^\']+)\'/', $html, $mAdminCsrf);
$csrfToken = $mAdminCsrf[1] ?? $loginCsrf;

// 2. Utworzenie nowego klienta testowego
$unique = time() . '_' . mt_rand(100, 999);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'company_name'     => 'Klient Do Usunięcia ' . $unique,
    'nip'              => '1231231234',
    'phone'            => '555666777',
    'email'            => 'dousuniecia' . $unique . '@example.com',
    'delivery_address' => 'ul. Kasacyjna 99',
    '_csrf'            => $csrfToken
]));
curl_setopt($ch, CURLOPT_URL, 'http://localhost/b2b/createclient');
$resCreate = curl_exec($ch);
$dataCreate = json_decode($resCreate, true);

$clientId = (int)($dataCreate['client_id'] ?? 0);
$token = $dataCreate['auth_token'] ?? '';
assert($clientId > 0, "Nie udało się utworzyć klienta testowego!");
echo "1. Utworzono klienta testowego (ID: {$clientId}, Token: {$token})\n";

// Sprawdzenie obecności w bazie
$clientInDb = $repo->getClientById($clientId);
echo "2. Klient istnieje w bazie: " . ($clientInDb ? "PASS" : "FAIL") . "\n";

// 3. Test API usunięcia klienta /b2b/deleteclient
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'id'    => $clientId,
    '_csrf' => $csrfToken
]));
curl_setopt($ch, CURLOPT_URL, 'http://localhost/b2b/deleteclient');
$resDelete = curl_exec($ch);
$dataDelete = json_decode($resDelete, true);

$deleteApiOk = ($dataDelete && ($dataDelete['ok'] ?? false) === true);
echo "3. Odpowiedź API /b2b/deleteclient: " . ($deleteApiOk ? "PASS" : "FAIL: {$resDelete}") . "\n";

// 4. Sprawdzenie czy klient faktycznie został usunięty z bazy
$clientAfter = $repo->getClientById($clientId);
$deletedFromDb = ($clientAfter === null);
echo "4. Klient został trwale usunięty z bazy danych: " . ($deletedFromDb ? "PASS" : "FAIL") . "\n";

// 5. Sprawdzenie czy klient nie może już zalogować się starym tokenem
$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_URL, "http://localhost/b2b?token={$token}");
curl_setopt($ch2, CURLOPT_FOLLOWLOCATION, false);
$resTokenAccess = curl_exec($ch2);
$tokenHttpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

$tokenBlocked = in_array($tokenHttpCode, [301, 302, 303, 403, 404]) || strpos($resTokenAccess, 'login') !== false;
echo "5. Dostęp usuniętego klienta przez token zablokowany: " . ($tokenBlocked ? "PASS" : "FAIL (HTTP {$tokenHttpCode})") . "\n";

// 6. Sprawdzenie widoku HTML i funkcji JS
$hasDeleteBtn = strpos($html, 'id="btn-delete-client"') !== false;
$hasDeleteFn = strpos($html, 'deleteCurrentClient') !== false;

echo "6. Przycisk usunięcia w modalu edycji klienta (#btn-delete-client): " . ($hasDeleteBtn ? "PASS" : "FAIL") . "\n";
echo "7. Funkcja JavaScript deleteCurrentClient(): " . ($hasDeleteFn ? "PASS" : "FAIL") . "\n";

@unlink($cookieFile);

if (!$deleteApiOk || !$deletedFromDb || !$tokenBlocked || !$hasDeleteBtn || !$hasDeleteFn) {
    echo "=== TESTY ZAKOŃCZONE BŁĘDEM ===\n";
    exit(1);
}

echo "=== WSZYSTKIE TESTY USUWANIA KLIENTA ZAKOŃCZONE SUKCESEM ===\n";
