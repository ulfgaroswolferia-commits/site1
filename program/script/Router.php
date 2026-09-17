<?php
/**
 * Router aplikacji — wstrzykuje tablicę tras i wartości domyślne z Config (data.php)
 * do generycznego BaseRouter z rdzenia.
 *
 * Jeśli projekt potrzebuje nietypowego parsowania URL-i (np. slugi zamiast par
 * klucz/wartość), nadpisz tu __construct() albo dodaj własne metody — rdzeń zostaje nietknięty.
 */
class Router extends BaseRouter
{
    public function __construct(string $uri)
    {
        parent::__construct(
            $uri,
            Config::get('routes'),
            [
                'route'      => Config::get('default_route'),
                'controller' => Config::get('default_controller'),
                'action'     => Config::get('default_action'),
            ]
        );
    }
}
