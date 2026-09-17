<?php
/**
 * Widok startowy. Dane przychodzą w $view (= $outputData kontrolera).
 * W widoku: tylko prezentacja. Żadnego SQL-a, żadnych reguł biznesowych.
 * Każdą wartość wypisuj przez Tools::h() — patrz docs/MVC.md.
 */
?>
<section class="card">
    <h1><?= Tools::h($view['title']) ?></h1>
    <p><?= Tools::h($view['message']) ?></p>

    <ul>
        <li><code>program/script/</code> — kontrolery (sterowanie)</li>
        <li><code>program/model/</code> — modele (dane i logika)</li>
        <li><code>views/</code> — widoki (prezentacja)</li>
        <li><code>docs/MVC.md</code> — obowiązujące zasady</li>
    </ul>

    <p><a href="<?= App::baseUrl() ?>home/example/id/7">Przykład parametru w URL-u</a></p>
</section>
