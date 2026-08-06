<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use App\Core\Request;
use App\Core\Router;

$router = new Router();
(require APP_PATH . '/Config/routes.php')($router);

$router->dispatch(Request::capture());
