<?php

use wizarphics\wizarframework\generators\Migration;
use wizarphics\wizarframework\Router;

/**
 * @var Router $router
 */

$router->cli('migration:create/(:num)', [Migration::class, 'create']);