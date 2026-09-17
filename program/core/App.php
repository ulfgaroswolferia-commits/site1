<?php
/**
 * Rdzeń aplikacji: parsuje URI, wywołuje kontroler, renderuje widok i layout.
 *
 * Cykl żądania:
 *   index.php -> App::run() -> Router -> {Controller}Controller::{prefix}{Action}()
 *             -> View(widok) -> View(layout) -> echo
 *
 * Metoda kontrolera zwraca nazwę szablonu widoku (bez .php), szukanego w
 * views/{trasa}/{szablon}.php. Layout wybiera kontroler przez $this->layout.
 */
class App
{
    /** @var BaseRouter */
    protected static $router;

    public static function getRouter()
    {
        return self::$router;
    }

    public static function run(): void
    {
        Tools::sanitizeFlashMsg();

        // Ścieżka aplikacji względem document rootu (obsługuje instalację w podkatalogu).
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        $uri  = substr($_SERVER['REQUEST_URI'], strlen($base));

        self::$router = new Router($uri);

        $controllerClass  = ucfirst(self::$router->getController()) . 'Controller';
        $controllerMethod = strtolower(self::$router->getMethodPrefix()) . ucfirst(self::$router->getAction());

        if (!class_exists($controllerClass) || !method_exists($controllerClass, $controllerMethod)) {
            self::notFound();
            return;
        }

        $controller = new $controllerClass();
        $viewName   = $controller->$controllerMethod();

        // Akcja może sama wysłać odpowiedź (JSON, plik, redirect) i zwrócić null/''.
        if ($viewName === null || $viewName === '') {
            return;
        }

        $content = (new View(
            $controller->getData(),
            BASE_PATH . '/views/' . self::$router->getController() . '/' . $viewName . '.php'
        ))->render();

        $layoutName = $controller->getLayout();
        if ($layoutName === '') {
            echo $content;
            return;
        }

        echo (new View(compact('content'), BASE_PATH . '/views/layout/' . $layoutName . '.php'))->render();
    }

    /** Odpowiedź 404 — wspólny widok views/home/404.php w layoucie domyślnym. */
    public static function notFound(): void
    {
        http_response_code(404);

        $content = (new View([], BASE_PATH . '/views/home/404.php'))->render();
        $layout  = BASE_PATH . '/views/layout/main.php';

        echo is_file($layout)
            ? (new View(compact('content'), $layout))->render()
            : $content;

        exit;
    }

    /**
     * Przekierowanie względem katalogu aplikacji (303 See Other — poprawne po POST).
     * Podaj trasę bez wiodącego slasha: App::redirect('home/index').
     *
     * URL absolutny (http://, https://) przekazywany jest bez zmian — używane przy
     * powrocie na zweryfikowany referer (patrz AppController::safeReferer()).
     */
    public static function redirect(string $uri): void
    {
        $isAbsolute = (bool) preg_match('~^https?://~i', $uri);
        $fullUri    = $isAbsolute ? $uri : self::baseUrl() . ltrim($uri, '/');

        if (!headers_sent()) {
            header('Location: ' . $fullUri, true, 303);
        } else {
            $json = json_encode($fullUri, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            echo '<script>window.location.href=' . $json . ';</script>';
        }

        exit;
    }

    /** Bazowy URL aplikacji, zawsze zakończony slashem. Używaj w widokach do budowy linków. */
    public static function baseUrl(): string
    {
        $path = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        return rtrim($path, '/') . '/';
    }

    /** Wysyła odpowiedź JSON i kończy żądanie. Do użycia w akcjach AJAX. */
    public static function json($data, int $status = 200): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
