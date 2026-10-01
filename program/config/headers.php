<?php
/**
 * Nagłówki HTTP, obsługa błędów, strefa czasowa i start sesji dla żądań HTML.
 * Dla endpointów JSON użyj zamiast tego headersjson.php.
 */

// Błędy PHP nigdy nie trafiają do przeglądarki na produkcji (wyciek ścieżek/SQL) — tylko do logu.
ini_set('display_errors', (defined('APP_ENV') && APP_ENV === 'development') ? '1' : '0');
ini_set('log_errors', '1');

date_default_timezone_set('Europe/Warsaw');

header('Content-Type: text/html; charset=utf-8');

// Brak cache — aplikacja renderuje treści zależne od sesji.
header('Expires: Tue, 03 Jul 2001 06:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Ochrona przed clickjackingiem i MIME sniffingiem.
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Content Security Policy: skrypty, style i połączenia wyłącznie z własnej domeny (plus Google Fonts).
// 'unsafe-inline' dla skryptów jest konieczne, dopóki widoki mają inline <script> i atrybuty onclick —
// mimo to CSP blokuje ładowanie kodu z obcych domen, wtyczki, <base> i wysyłkę formularzy na zewnątrz.
header("Content-Security-Policy: default-src 'self'; "
    . "script-src 'self' 'unsafe-inline'; "
    . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
    . "font-src 'self' https://fonts.gstatic.com; "
    . "img-src 'self' data:; "
    . "connect-src 'self'; "
    . "object-src 'none'; "
    . "base-uri 'self'; "
    . "form-action 'self'; "
    . "frame-ancestors 'self'");

// -----------------------------------------------------------------------------
// Sesja
// -----------------------------------------------------------------------------

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

if ($isHttps) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// Odrzucaj identyfikatory sesji, których serwer nie wydał (ochrona przed session fixation).
ini_set('session.use_strict_mode', '1');

$lifetime = defined('SESSION_LIFETIME') ? (int) SESSION_LIFETIME : 0;

if ($lifetime > 0) {
    ini_set('session.gc_maxlifetime', (string) ($lifetime + 300));
}

session_set_cookie_params([
    'lifetime' => $lifetime,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => $isHttps,
]);

if (defined('SESSION_NAME') && SESSION_NAME !== '') {
    session_name(SESSION_NAME);
}

session_start();
