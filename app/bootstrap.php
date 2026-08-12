<?php

declare(strict_types=1);

define('APP_PATH', __DIR__);
define('ROOT_PATH', dirname(__DIR__));

require APP_PATH . '/Core/Autoloader.php';

App\Core\Autoloader::register();

// Before the config, which reads credentials out of it
App\Core\Env::load(ROOT_PATH . '/.env');

App\Core\Config::load(APP_PATH . '/Config/config.php');

require APP_PATH . '/Core/helpers.php';

if (App\Core\Config::get('debug')) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
}

App\Core\Session::start();
