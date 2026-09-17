<?php
/**
 * Parser URI. Wzorzec: /{trasa}/{akcja}/{klucz}/{wartosc}/{klucz}/{wartosc}...
 *
 *   /                       -> trasa domyślna, akcja domyślna
 *   /home                   -> trasa home, akcja domyślna
 *   /home/about             -> HomeController::actionAbout()
 *   /home/show/id/7/tab/faq -> HomeController::actionShow(), params = {id: "7", tab: "faq"}
 *
 * Segmenty nadmiarowe parowane są w klucz/wartość i dostępne przez getParams().
 * Query string (?a=b) NIE jest tu parsowany — czytaj go z $_GET.
 */
class BaseRouter
{
    protected $uri;
    protected $controller;
    protected $action;
    protected $params;
    protected $route;
    protected $method_prefix;

    public function getUri()          { return $this->uri; }
    public function getController()   { return $this->controller; }
    public function getAction()       { return $this->action; }
    public function getParams()       { return $this->params; }
    public function getRoute()        { return $this->route; }
    public function getMethodPrefix() { return $this->method_prefix; }

    /** Pojedynczy parametr ze ścieżki, z wartością domyślną. */
    public function param(string $key, $default = null)
    {
        return $this->params->$key ?? $default;
    }

    public function __construct(string $uri, array $routes, array $defaults)
    {
        $path = trim(explode('?', urldecode($uri))[0], '/');

        $this->uri           = $path;
        $this->route         = $defaults['route'];
        $this->controller    = $defaults['controller'];
        $this->action        = $defaults['action'];
        $this->method_prefix = $routes[$this->route] ?? '';

        $segments = $path !== '' ? explode('/', $path) : [];

        if (empty($segments)) {
            $this->params = (object) [];
            return;
        }

        $idx = 0;

        // Pierwszy segment: rozpoznana trasa z Config::$routes.
        if (isset($routes[strtolower($segments[$idx])])) {
            $this->route         = strtolower($segments[$idx]);
            $this->method_prefix = $routes[$this->route];
            $this->controller    = $this->route;
            $idx++;
        }

        // Kolejny segment: akcja.
        if (isset($segments[$idx]) && $segments[$idx] !== '') {
            $this->action = strtolower($segments[$idx]);
            $idx++;
        }

        // Reszta: pary klucz/wartość.
        $params = [];
        while (isset($segments[$idx])) {
            $key = $segments[$idx];
            if ($key !== '') {
                $params[$key] = $segments[$idx + 1] ?? null;
            }
            $idx += 2;
        }

        $this->params = (object) $params;
    }
}
