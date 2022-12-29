<?php


use wizarphics\wizarframework\Application;
use wizarphics\wizarframework\exception\NotFoundException;

//Converst Application::$ROOT_DIR to constant if not defined
defined('ROOT_DIR') or define('ROOT_DIR', Application::$ROOT_DIR);
defined('CORE_DIR') or define('CORE_DIR', Application::$CORE_DIR);
defined('STOREPATH') or define('STOREPATH', ROOT_DIR . '/storage/');
defined('PUBLICPATH') or define('PUBLICPATH', ROOT_DIR . '/public/');
defined('VIEWPATH') or define('VIEWPATH', ROOT_DIR.'/views/');
defined('ERROR_PATH') or define('ERROR_PATH', VIEWPATH. '/errors/');

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
 * Load environment variables from.env file
 * ---------------------------------------------------------------
 */
$dotenv = Dotenv\Dotenv::createImmutable(ROOT_DIR);
$dotenv->load();
$dotenv->required(['DB_DSN', 'DB_PASSWORD', 'DB_USER', 'app.userClass']);


/*
 * ---------------------------------------------------------------
 * SET APPLICATION CONFIGURATION
 * ---------------------------------------------------------------
 */
$APP_CONFIGS = [
    'userClass' => $_ENV['app.userClass'],
    'db' => [
        'dsn' => $_ENV['DB_DSN'],
        'user' => $_ENV['DB_USER'],
        'password' => $_ENV['DB_PASSWORD']
    ]
];

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


/*
 * ---------------------------------------------------------------
 * Create Application Instance
 * ---------------------------------------------------------------
 */
$app = new Application(ROOT_DIR, $APP_CONFIGS);


setcookie(
    env('app.name') . '_id',
    uniqid(env('app.name'), true),
    time() + MINUTE,
    '/',
    '',
    false,
    true
);

/*
 * ---------------------------------------------------------------
 * Define $router variable
 * ---------------------------------------------------------------
 */
$router = $app->router;


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


return $app;