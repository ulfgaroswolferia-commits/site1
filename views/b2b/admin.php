<?php
/**
 * Panel Hurtownika - Hurtownia Magdy
 */
$csrfToken = $view['csrfToken'] ?? '';
$base = $view['base'] ?? '/';
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <title><?= Tools::h($view['title']) ?></title>
    <script>
        const CSRF_TOKEN = '<?= $csrfToken ?>';
        const BASE_URL   = '<?= $base ?>';
    </script>
</head>
<body>
    <div id="admin-root">
        <h1>Panel Hurtownika — Hurtownia Magdy</h1>
    </div>
</body>
</html>
