<?php
/**
 * Layout domyślny. Otrzymuje wyrenderowany widok w $view['content'].
 * Wybierany przez $this->layout w kontrolerze (nazwa pliku bez .php).
 *
 * Zasoby frontowe: assets/ w katalogu projektu. Jeśli projekt ma używać Bootstrapa
 * lub innej biblioteki, dołóż ją tutaj (lokalnie w assets/ albo z CDN).
 */
$base  = App::baseUrl();
$title = $view['title'] ?? (defined('APP_NAME') ? APP_NAME : 'TwiiCoreF');
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= Tools::h($title) ?></title>
    <link rel="stylesheet" href="<?= $base ?>assets/css/app.css">
</head>
<body>

<header class="app-header">
    <a class="app-brand" href="<?= $base ?>"><?= Tools::h(defined('APP_NAME') ? APP_NAME : 'TwiiCoreF') ?></a>
    <nav class="app-nav">
        <!-- Nawigacja projektu -->
    </nav>
</header>

<main class="app-main">

    <?php foreach (Tools::getAllFlashMsg() as $name => $msg): ?>
        <?php if ($msg['msg'] !== ''): ?>
            <div class="flash flash-<?= Tools::h($msg['class']) ?>"><?= Tools::h($msg['msg']) ?></div>
        <?php endif; ?>
    <?php endforeach; ?>

    <?= $view['content'] ?>

</main>

<footer class="app-footer">
    <span><?= Tools::h(defined('APP_NAME') ? APP_NAME : 'TwiiCoreF') ?></span>
</footer>

<script src="<?= $base ?>assets/js/app.js"></script>
</body>
</html>
