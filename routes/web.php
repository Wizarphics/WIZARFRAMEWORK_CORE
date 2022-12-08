<?php

use app\controllers\AppController;
use wizarphics\wizarframework\generators\Controller;
use wizarphics\wizarframework\generators\Migration;
use wizarphics\wizarframework\Router;

/**
 * @var Router $router
 */

$router->cli('migration:create/{name}', [Migration::class, 'create']);
// $router->cli('make:controller/{name}', [Controller::class, 'create']);
$router->cli('make:controller', [Controller::class, 'create']);
$router->get('/sab/{0:\d+}', [AppController::class, 'home']);