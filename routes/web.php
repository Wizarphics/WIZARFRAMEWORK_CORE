<?php

use app\controllers\AppController;
use app\controllers\AuthController;
use wizarphics\wizarframework\auth\Auth;
use wizarphics\wizarframework\auth\controllers\LoginController;
use wizarphics\wizarframework\auth\controllers\PwdResetController;
use wizarphics\wizarframework\auth\controllers\RegisterController;
use wizarphics\wizarframework\generators\Controller;
use wizarphics\wizarframework\generators\Migration;
use wizarphics\wizarframework\Router;

/**
 * @var Router $router
 */

$router->cli('migration:create/{name}', [Migration::class, 'create']);
// $router->cli('make:controller/{name}', [Controller::class, 'create']);
$router->cli('make:controller', [Controller::class, 'create']);

$router->get('/auth/login', [LoginController::class, "loadView"])->name('login');
$router->post('/auth/login', [LoginController::class, "loginUserIn"])->name('login');
$router->get('/auth/register', [RegisterController::class, "loadView"])->name('register');
$router->post('/auth/register', [RegisterController::class, "registerUser"]);
$router->get('/auth/forgot-password', [PwdResetController::class, "forgotPasswordView"])->name('forgot-password');
$router->post('/auth/forgot-password', [PwdResetController::class, "requestResetPassword"])->name('reset-password');
$router->get('/auth/reset-password', [PwdResetController::class, "resetPasswordView"])->name('reset-password');
$router->post('/auth/reset-password', [PwdResetController::class, "resetPassword"])->name('reset-password');
$router->get('/auth/pin', [AuthController::class, "pin"])->name('pin');
$router->get('/auth/logout', [LoginController::class, 'logout'])->name('logout');