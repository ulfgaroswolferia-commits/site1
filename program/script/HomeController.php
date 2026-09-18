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
     * Jeśli użytkownik nie jest zalogowany, wyświetlamy ekran logowania.
     * Po zalogowaniu wchodzimy do prostego panelu.
     */
    public function actionIndex()
    {
        $this->layout = '';

        if ($this->isLoggedIn()) {
            $this->outputData['title'] = 'Panel użytkownika';
            $this->outputData['user']  = Tools::getSessionVar('app_login') ?: (defined('APP_LOGIN') ? APP_LOGIN : 'użytkownik');
            return 'launcher';
        }

        return $this->actionLogin();
    }

    /**
     * GET /home/dashboard
     *
     * Zachowany, rozbudowany dashboard dostępny po wybraniu go z launchera.
     */
    public function actionDashboard()
    {
        $this->requireAuth();
        $this->layout = '';

        $this->outputData['title'] = 'Panel użytkownika';
        $this->outputData['user']  = Tools::getSessionVar('app_login') ?: (defined('APP_LOGIN') ? APP_LOGIN : 'użytkownik');

        return 'dashboard';
    }

    /**
     * GET /home/login
     * POST /home/login
     */
    public function actionLogin()
    {
        $this->layout = '';
        $this->outputData['title']   = 'Zaloguj się';
        $this->outputData['error']   = '';
        $this->outputData['login']   = '';

        if (!Tools::isPost()) {
            return 'login';
        }

        $this->requireCsrf();

        $login    = trim((string) ($_POST['login'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($login === (defined('APP_LOGIN') ? APP_LOGIN : '') && $password === (defined('APP_PASSWORD') ? APP_PASSWORD : '')) {
            $this->startUserSession(1, ['app_login' => $login]);
            App::redirect('home/index');
        }

        $this->outputData['error'] = 'Nieprawidłowy login lub hasło.';
        $this->outputData['login'] = $login;

        return 'login';
    }

    /**
     * GET /home/logout
     */
    public function actionLogout()
    {
        $this->endUserSession();
        App::redirect('home/login');
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
