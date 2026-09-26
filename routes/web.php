<?php

declare(strict_types=1);

use App\Controllers\HomeController;
use App\Middleware\FrameworkHeader;
use Xiaoui\Routing\Router;

/** @var Router $router */

$router->middleware(FrameworkHeader::class);

$router->get('/', [HomeController::class, 'index']);
$router->get('/users/{id}', [HomeController::class, 'show']);
$router->post('/users', [HomeController::class, 'store']);

$router->group(['prefix' => '/api', 'middleware' => [FrameworkHeader::class]], function (Router $router) {
    $router->get('/status', fn () => ['status' => 'ok', 'framework' => 'Xiaoui']);
});
