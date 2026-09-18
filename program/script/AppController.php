<?php
/**
 * Bazowy kontroler aplikacji — po nim dziedziczą WSZYSTKIE kontrolery projektu.
 *
 * Dokłada do rdzenia to, co i tak pisze się w każdym projekcie: wymuszenie
 * logowania, walidację CSRF, bezpieczny powrót na poprzednią stronę i wykrywanie AJAX-a.
 *
 *   class OrderController extends AppController
 *   {
 *       public function actionIndex()
 *       {
 *           $this->requireAuth();
 *           $this->outputData['orders'] = (new \App\Order())->forUser($this->uid());
 *           return 'index';
 *       }
 *   }
 */
class AppController extends Controller
{
    /** Layout domyślny dla całej aplikacji. Nadpisz w kontrolerze, gdy potrzeba innego. */
    public $layout = 'main';

    // -------------------------------------------------------------------------
    // Autoryzacja
    // -------------------------------------------------------------------------

    /**
     * Wymusza zalogowanie. Brak sesji -> flash + przekierowanie na AUTH_LOGIN_ROUTE.
     * Wywołuj na początku każdej akcji wymagającej logowania.
     */
    protected function requireAuth(): void
    {
        if (!$this->isLoggedIn()) {
            if ($this->isAjax()) {
                App::json(['ok' => false, 'error' => 'Sesja wygasła. Zaloguj się ponownie.'], 401);
            }
            Tools::setFlashMsg('error', 'Zaloguj się, aby kontynuować.');
            App::redirect(defined('AUTH_LOGIN_ROUTE') ? AUTH_LOGIN_ROUTE : 'home/index');
        }
    }

    protected function isLoggedIn(): bool
    {
        return (bool) Tools::getSessionVar($this->authKey());
    }

    /** ID zalogowanego użytkownika (0 gdy niezalogowany). */
    protected function uid(): int
    {
        return (int) Tools::getSessionVar($this->authKey());
    }

    /**
     * Zapisuje sesję logowania. Regeneracja ID chroni przed session fixation —
     * wywołaj to zamiast ręcznego ustawiania $_SESSION po poprawnym haśle.
     */
    protected function startUserSession(int $userId, array $extra = []): void
    {
        session_regenerate_id(true);

        Tools::setSessionVar($this->authKey(), $userId);
        foreach ($extra as $key => $value) {
            Tools::setSessionVar($key, $value);
        }
    }

    /** Kasuje sesję logowania wraz z ciasteczkiem sesyjnym. */
    protected function endUserSession(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }

        session_destroy();
    }

    private function authKey(): string
    {
        return defined('AUTH_SESSION_KEY') ? AUTH_SESSION_KEY : 'app_uid';
    }

    // -------------------------------------------------------------------------
    // CSRF
    // -------------------------------------------------------------------------

    /**
     * Wymusza poprawny token CSRF przy POST. Niepoprawny -> 400 i koniec żądania.
     * Wywołuj w KAŻDEJ akcji zmieniającej stan.
     */
    protected function requireCsrf(): void
    {
        if (!Tools::isPost() || !Tools::csrfValidate()) {
            http_response_code(400);
            if ($this->isAjax()) {
                App::json(['ok' => false, 'error' => 'Nieprawidłowy token bezpieczeństwa.'], 400);
            }
            Tools::setFlashMsg('error', 'Nieprawidłowy token bezpieczeństwa. Spróbuj ponownie.');
            App::redirect($this->backUrl());
        }
    }

    // -------------------------------------------------------------------------
    // Żądanie
    // -------------------------------------------------------------------------

    /** Czy żądanie przyszło przez fetch/XHR (nagłówek X-Requested-With lub Accept/Content-Type JSON). */
    protected function isAjax(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
            || stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false
            || stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false;
    }

    /**
     * Referer, ale tylko gdy wskazuje na ten sam host co aplikacja.
     * Chroni przed open redirectem. Pusty string = brak bezpiecznego referera.
     */
    protected function safeReferer(): string
    {
        $ref = $_SERVER['HTTP_REFERER'] ?? '';
        if ($ref === '') {
            return '';
        }
        $refHost = parse_url($ref, PHP_URL_HOST);
        return ($refHost !== null && $refHost === ($_SERVER['HTTP_HOST'] ?? null)) ? $ref : '';
    }

    /** Adres powrotu: bezpieczny referer, a w razie jego braku trasa domyślna. */
    protected function backUrl(): string
    {
        $ref = $this->safeReferer();
        return $ref !== '' ? $ref : (Config::get('default_route') . '/' . Config::get('default_action'));
    }

    /** Parametr ze ścieżki URL (/home/show/id/7 -> $this->param('id')). */
    protected function param(string $key, $default = null)
    {
        return App::getRouter()->param($key, $default);
    }
}
