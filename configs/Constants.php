<?php

defined('DEBUG_LOG_PATH') or define('DEBUG_LOG_PATH', ROOT_DIR.'/runtime/logs/debbug_logger/');
defined('MIGRATION_PATH') or define('MIGRATION_PATH', ROOT_DIR.'/migrations/');
define('FRAME_WORK_URL', 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);


/*
 |--------------------------------------------------------------------------
 | Timing Constants
 |--------------------------------------------------------------------------
 |
 | Provide simple ways to work with the myriad of PHP functions that
 | require information to be in seconds.
 */
defined('SECOND') || define('SECOND', 1);
defined('MINUTE') || define('MINUTE', 60);
defined('HOUR')   || define('HOUR', 3600);
defined('DAY')    || define('DAY', 86400);
defined('WEEK')   || define('WEEK', 604800);
defined('MONTH')  || define('MONTH', 2_592_000);
defined('YEAR')   || define('YEAR', 31_536_000);
defined('DECADE') || define('DECADE', 315_360_000);