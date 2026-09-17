<?php
/**
 * Kontroler startowy — jednocześnie żywy przykład konwencji frameworka.
 * Po postawieniu nowego projektu możesz go usunąć lub przerobić na właściwy ekran główny.
 *
 * Trasa 'home' jest zarejestrowana w Config::$routes (program/config/data.php).
 */
class HomeController extends AppController
{
    /**
     * GET /  albo  GET /home/index
     *
     * Akcja: pobiera dane z modelu, przekazuje do widoku, zwraca nazwę szablonu.
     * Żadnego SQL-a i żadnego HTML-a w tym miejscu — patrz docs/MVC.md.
     */
    public function actionIndex()
    {
        $this->outputData['title']   = defined('APP_NAME') ? APP_NAME : 'TwiiCoreF';
        $this->outputData['message'] = 'Szkielet działa. Zacznij od docs/MVC.md.';

        return 'index';
    }

    /**
     * GET /home/example/id/7
     * Przykład odczytu parametru ze ścieżki URL.
     */
    public function actionExample()
    {
        $this->outputData['title'] = 'Przykład';
        $this->outputData['id']    = (int) $this->param('id', 0);

        return 'example';
    }
}
