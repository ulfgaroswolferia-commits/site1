<?php
/**
 * Test TDD: Weryfikacja trybu SQLite WAL i busy_timeout w B2bRepository
 */
define('BASE_PATH', 'C:/laragon\www');
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';

use App\B2bRepository;

echo "=== TEST: Weryfikacja trybu SQLite WAL i busy_timeout ===\n";

$repo = new B2bRepository();
$pdo = $repo->getPdo();

$journalMode = strtolower((string)$pdo->query("PRAGMA journal_mode")->fetchColumn());
$busyTimeout = (int)$pdo->query("PRAGMA busy_timeout")->fetchColumn();

echo "1. SQLite journal_mode: {$journalMode} (oczekiwano: wal): ";
$walOk = ($journalMode === 'wal');
echo ($walOk ? "PASS" : "FAIL") . "\n";

echo "2. SQLite busy_timeout: {$busyTimeout} ms (oczekiwano: >= 5000): ";
$timeoutOk = ($busyTimeout >= 5000);
echo ($timeoutOk ? "PASS" : "FAIL") . "\n";

if (!$walOk || !$timeoutOk) {
    echo "=== TEST ZAKOŃCZONY BŁĘDEM (Stan RED) ===\n";
    exit(1);
}

echo "=== TEST ZAKOŃCZONY SUKCESEM (Stan GREEN) ===\n";
