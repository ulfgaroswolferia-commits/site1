<?php
/**
 * Wariant headers.php dla endpointów zwracających JSON (AJAX, webhooki, cron po HTTP).
 * Dołącz go zamiast headers.php w osobnym punkcie wejścia, np. api.php.
 */

ini_set('display_errors', (defined('APP_ENV') && APP_ENV === 'development') ? '1' : '0');
ini_set('log_errors', '1');

date_default_timezone_set('Europe/Warsaw');

header('Content-Type: application/json; charset=utf-8');
header('Expires: Tue, 03 Jul 2001 06:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

if (defined('SESSION_NAME') && SESSION_NAME !== '') {
    session_name(SESSION_NAME);
}

session_start();
