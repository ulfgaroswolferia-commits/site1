<?php
/** Przykład odczytu parametru ze ścieżki: /home/example/id/7 */
?>
<section class="card">
    <h1><?= Tools::h($view['title']) ?></h1>
    <p>Parametr <code>id</code> ze ścieżki URL: <strong><?= Tools::h($view['id']) ?></strong></p>
    <p><a href="<?= App::baseUrl() ?>">Powrót</a></p>
</section>
