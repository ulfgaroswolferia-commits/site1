<?php
/**
 * Test weryfikujący edycję danych klienta B2B oraz wysyłkę linku z tokenem (email/SMS)
 */
define('BASE_PATH', 'C:/laragon/www');
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';
require_once BASE_PATH . '/program/config/includes.php';

$cookieFile = tempnam(sys_get_temp_dir(), 'cook_client_mgmt_');
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);

function httpReq($url, $post = null, $headers = []) {
    global $ch;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    } else {
        curl_setopt($ch, CURLOPT_HTTPHEADER, []);
    }
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($post) ? http_build_query($post) : $post);
    } else {
        curl_setopt($ch, CURLOPT_HTTPGET, true);
    }
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    return ['code' => $code, 'body' => $body];
}

echo "=== TEST: Zarządzanie klientami B2B (Edycja i Wysyłka Linku) ===\n";

// 1. Logowanie do panelu administratora
$resLoginGet = httpReq('http://localhost/home/login');
preg_match('/name="_csrf" value="([^"]+)"/', $resLoginGet['body'], $m);
$loginCsrf = $m[1] ?? '';

httpReq('http://localhost/home/login', [
    'login' => APP_LOGIN,
    'password' => APP_PASSWORD,
    '_csrf' => $loginCsrf
]);

// 2. Pobranie strony /b2b/admin i odczytanie CSRF
$resAdminGet = httpReq('http://localhost/b2b/admin');
preg_match('/const CSRF_TOKEN\s*=\s*\'([^\']+)\'/', $resAdminGet['body'], $mB2b);
$csrfToken = $mB2b[1] ?? $loginCsrf;

$html = $resAdminGet['body'];

$hasModalEdit   = strpos($html, 'id="modal-edit-client"') !== false;
$hasModalSms    = strpos($html, 'id="modal-send-sms"') !== false;
$hasClientsData = strpos($html, 'CLIENTS_DATA') !== false;
$hasEditBtn     = strpos($html, 'openEditClientModal(') !== false;
$hasEmailBtn    = strpos($html, 'sendTokenEmail(') !== false;
$hasSmsBtn      = strpos($html, 'openSendSmsModal(') !== false;

echo "1. Modal edycji klienta obecny w HTML: " . ($hasModalEdit ? "OK" : "BŁĄD") . "\n";
echo "2. Modal wysyłki SMS/WhatsApp obecny w HTML: " . ($hasModalSms ? "OK" : "BŁĄD") . "\n";
echo "3. Słownik CLIENTS_DATA w skrypcie: " . ($hasClientsData ? "OK" : "BŁĄD") . "\n";
echo "4. Przyciski akcji (Edytuj, E-mail, SMS) w tabeli: " . (($hasEditBtn && $hasEmailBtn && $hasSmsBtn) ? "OK" : "BŁĄD") . "\n";

// 3. Utworzenie klienta przez API
$uniqueSuffix = time() . '_' . mt_rand(100, 999);
$resCreate = httpReq('http://localhost/b2b/createclient', [
    'company_name'     => 'Warzywniak Testowy ' . $uniqueSuffix,
    'nip'              => '5252525252',
    'phone'            => '+48 500 600 700',
    'email'            => 'klient' . $uniqueSuffix . '@example.com',
    'delivery_address' => 'ul. Zielona 10, Warszawa',
    '_csrf'            => $csrfToken
], ['X-Requested-With: XMLHttpRequest', 'Accept: application/json']);

$dataCreate = json_decode($resCreate['body'], true);
$clientId = (int)($dataCreate['client_id'] ?? 0);
$initialToken = $dataCreate['auth_token'] ?? '';
echo "5. Utworzenie klienta testowego (ID: {$clientId}): " . ($clientId > 0 ? "OK" : "BŁĄD: {$resCreate['body']}") . "\n";

// 4. Edycja klienta przez API /b2b/updateclient
$resUpdate = httpReq('http://localhost/b2b/updateclient', [
    'id'               => $clientId,
    'company_name'     => 'Warzywniak Zaktualizowany ' . $uniqueSuffix,
    'nip'              => '1112223344',
    'phone'            => '+48 600 700 800',
    'email'            => 'nowy_email_' . $uniqueSuffix . '@example.com',
    'delivery_address' => 'ul. Nowoowocowa 99, Kraków',
    'regenerate_token' => '1',
    '_csrf'            => $csrfToken
], ['X-Requested-With: XMLHttpRequest', 'Accept: application/json']);

$dataUpdate = json_decode($resUpdate['body'], true);
$updateOk = ($resUpdate['code'] === 200 && ($dataUpdate['ok'] ?? false) === true);
$updatedClient = $dataUpdate['client'] ?? [];
$newToken = $updatedClient['auth_token'] ?? '';

$fieldsMatch = (
    $updatedClient['company_name'] === 'Warzywniak Zaktualizowany ' . $uniqueSuffix &&
    $updatedClient['nip'] === '1112223344' &&
    $updatedClient['phone'] === '+48 600 700 800' &&
    $updatedClient['email'] === 'nowy_email_' . $uniqueSuffix . '@example.com' &&
    $updatedClient['delivery_address'] === 'ul. Nowoowocowa 99, Kraków' &&
    !empty($newToken) &&
    $newToken !== $initialToken
);

echo "6. Aktualizacja danych klienta przez /b2b/updateclient: " . ($updateOk && $fieldsMatch ? "OK" : "BŁĄD") . "\n";
echo "   - Nowa nazwa: {$updatedClient['company_name']}\n";
echo "   - Zregenerowany token: {$newToken} (poprzedni: {$initialToken})\n";

// 5. Test generatora wiadomości e-mail B2bController::buildTokenEmailHtml
require_once BASE_PATH . '/program/script/B2bController.php';
$tokenUrl = 'http://localhost/b2b?token=' . $newToken;
$emailHtml = B2bController::buildTokenEmailHtml($updatedClient, $tokenUrl);
$hasCompanyInEmail = strpos($emailHtml, 'Warzywniak Zaktualizowany') !== false;
$hasUrlInEmail     = strpos($emailHtml, $tokenUrl) !== false;
$hasCtaBtn         = strpos($emailHtml, 'Przejdź do składania zamówienia') !== false;

echo "7. Generowanie szablonu HTML e-mail z tokenem: " . (($hasCompanyInEmail && $hasUrlInEmail && $hasCtaBtn) ? "OK" : "BŁĄD") . "\n";

// 6. Test endpointu /b2b/sendtoken
$resSend = httpReq('http://localhost/b2b/sendtoken', [
    'id'      => $clientId,
    'channel' => 'email',
    '_csrf'   => $csrfToken
], ['X-Requested-With: XMLHttpRequest', 'Accept: application/json']);

$dataSend = json_decode($resSend['body'], true);
// Endpoint powinien odpowiedzieć kodem 200 z sukcesem LUB kontrolowanym błędem poczty (np. brak skonfigurowanego serwera SMTP) z fallback_mailto
$sendHandled = (
    ($resSend['code'] === 200 && ($dataSend['ok'] ?? false) === true) ||
    ($resSend['code'] === 500 && isset($dataSend['fallback_mailto']))
);
echo "8. Obsługa endpointu /b2b/sendtoken: " . ($sendHandled ? "OK (Odpowiedź kontrolowana: " . ($dataSend['ok'] ? 'Wysłano' : 'Brak SMTP, wygenerowano fallback mailto') . ")" : "BŁĄD: {$resSend['body']}") . "\n";

@unlink($cookieFile);

$allPassed = ($hasModalEdit && $hasModalSms && $hasClientsData && $hasEditBtn && $hasEmailBtn && $hasSmsBtn && $clientId > 0 && $updateOk && $fieldsMatch && $hasCompanyInEmail && $hasUrlInEmail && $sendHandled);

echo $allPassed ? "=== ALL CLIENT MANAGEMENT TESTS PASSED ===\n" : "=== TESTS FAILED ===\n";
exit($allPassed ? 0 : 1);
