<?php

declare(strict_types=1);

use Xiaoui\Application;
use Xiaoui\Http\Request;
use Xiaoui\Kernel;
use Xiaoui\Support\Env;

require __DIR__ . '/../vendor/autoload.php';

$app = new Application(dirname(__DIR__));

Env::load($app->basePath('.env'));

$app->loadConfig($app->basePath('config'));

$routes = $app->routes($app->basePath('routes'));

$kernel = new Kernel($app, $routes);

$response = $kernel->handle(Request::fromGlobals());

$response->send();
