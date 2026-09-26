<?php

declare(strict_types=1);

namespace App\Middleware;

use Xiaoui\Http\MiddlewareInterface;
use Xiaoui\Http\Request;
use Xiaoui\Http\RequestHandlerInterface;
use Xiaoui\Http\Response;

/**
 * Example middleware: attaches an X-Framework header to every response.
 */
class FrameworkHeader implements MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $response = $handler->handle($request);

        return $response->header('X-Framework', 'Xiaoui');
    }
}
