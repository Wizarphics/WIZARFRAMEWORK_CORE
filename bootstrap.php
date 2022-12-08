<?php


use wizarphics\wizarframework\Application;
use wizarphics\wizarframework\exception\NotFoundException;

//Converst Application::$ROOT_DIR to constant if not defined
defined('ROOT_DIR') or define('ROOT_DIR', Application::$ROOT_DIR);
defined('CORE_DIR') or define('CORE_DIR', Application::$CORE_DIR);
defined('STOREPATH') or define('STOREPATH', ROOT_DIR . '/storage/');
defined('PUBLICPATH') or define('PUBLICPATH', ROOT_DIR . '/public/');
require_once CORE_DIR . '/vendor/autoload.php';


/*
 * ---------------------------------------------------------------
 * SET PREFERENCE FOR SAGE
 * ---------------------------------------------------------------
 */

Sage::$theme = Sage::THEME_LIGHT;
Sage::$appRootDirs = [
    $_SERVER['DOCUMENT_ROOT'] => 'ROOT_DIR',
    PUBLICPATH => 'PUBLICPATH',
    STOREPATH => 'STOREPATH',
    ROOT_DIR => 'ROOT_DIR',
    CORE_DIR => 'CORE_DIR'
];

Sage::$cliDetection = true;

// saged(Application::$app); // dump any number of parameters

/*
 * ---------------------------------------------------------------
 * GRAB OUR CONSTANTS & COMMON
 * ---------------------------------------------------------------
 */
// Require app configs/Constants.php file if exists.
if (file_exists(ROOT_DIR . '/configs/Constants.php'))
    require_once ROOT_DIR . '/configs/Constants.php';

// Require app configs/Common.php file if exists.
if (file_exists(ROOT_DIR . '/configs/Common.php'))
    require_once ROOT_DIR . '/configs/Common.php';

// Require core configs/Constants.php file if exists.
if (file_exists(CORE_DIR . '/configs/Constants.php'))
    require_once CORE_DIR . '/configs/Constants.php';

// Require core configs/Common.php file if exists.
if (file_exists(CORE_DIR . '/configs/Common.php'))
    require_once CORE_DIR . '/configs/Common.php';


setcookie(
    env('app.name') . '_id',
    uniqid(env('app.name'), true),
    time()+MINUTE,
    '/',
    '',
    false,
    true
);
/*
 * ---------------------------------------------------------------
 * GRAB OUR ROUTES
 * ---------------------------------------------------------------
 */

if (file_exists(CORE_DIR . '/routes/web.php'))
    require_once CORE_DIR . '/routes/web.php';
// Require app routes web.php file if exists.
if (file_exists(ROOT_DIR . '/routes/web.php'))
    require_once ROOT_DIR . '/routes/web.php';
else
    throw new NotFoundException(ROOT_DIR . '/routes/web.php is missing.');
