<?php
define('BASE_PATH', getcwd());
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/core/BaseRouter.php';
require_once BASE_PATH . '/program/script/Router.php';
require_once BASE_PATH . '/program/config/autoload.php';

$router = new Router('/b2b/admin');
$controllerClass = ucfirst($router->getController()) . 'Controller';
$controllerMethod = strtolower($router->getMethodPrefix()) . ucfirst($router->getAction());

$routeIsRegistered = $router->getRoute() === 'b2b';
$adminActionExists = class_exists($controllerClass) && method_exists($controllerClass, $controllerMethod);

echo "Route /b2b/admin: " . ($routeIsRegistered && $adminActionExists ? "OK" : "BŁĄD") . "\n";
exit($routeIsRegistered && $adminActionExists ? 0 : 1);
