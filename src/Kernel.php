<?php

declare(strict_types=1);

namespace Xiaoui;

use Throwable;
use Xiaoui\Http\MiddlewareInterface;
use Xiaoui\Http\Request;
use Xiaoui\Http\RequestHandlerInterface;
use Xiaoui\Http\Response;
use Xiaoui\Routing\Route;
use Xiaoui\Routing\RouteCollection;

/**
 * The HTTP kernel. Wires routing, middleware and request handling together.
 */
class Kernel
{
    public function __construct(
        private readonly Application $app,
        private readonly RouteCollection $routes,
    ) {
    }

    public function handle(Request $request): Response
    {
        try {
            return $this->dispatch($request);
        } catch (Throwable $e) {
            return $this->renderException($e);
        }
    }

    private function dispatch(Request $request): Response
    {
        $method = $request->method();

        // Allow HEAD to fall back to GET.
        $matched = $this->routes->match($method, $request->path())
            ?? ($method === 'HEAD' ? $this->routes->match('GET', $request->path()) : null);

        if ($matched === null) {
            return Response::json(
                ['error' => 'Not Found'],
                Response::HTTP_NOT_FOUND,
            );
        }

        /** @var Route $route */
        $route = $matched['route'];

        foreach ($matched['params'] as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        $middleware = [
            ...$this->routes->globalMiddleware(),
            ...$route->middleware,
        ];

        $handler = new class($this->app, $route) implements RequestHandlerInterface {
            public function __construct(
                private readonly Application $app,
                private readonly Route $route,
            ) {
            }

            public function handle(Request $request): Response
            {
                $result = $this->app->call($this->route->handler, $request);

                return $this->app->respond($result);
            }
        };

        return $this->runMiddleware($middleware, $request, $handler);
    }

    /**
     * @param list<class-string<MiddlewareInterface>> $middleware
     */
    private function runMiddleware(
        array $middleware,
        Request $request,
        RequestHandlerInterface $core,
    ): Response {
        // Build the middleware stack from the inside out.
        $handler = $core;

        foreach (array_reverse($middleware) as $middlewareClass) {
            $instance = $this->app->get($middlewareClass);

            $next = $handler;
            $handler = new class($instance, $next) implements RequestHandlerInterface {
                public function __construct(
                    private readonly MiddlewareInterface $middleware,
                    private readonly RequestHandlerInterface $next,
                ) {
                }

                public function handle(Request $request): Response
                {
                    return $this->middleware->process($request, $this->next);
                }
            };
        }

        return $handler->handle($request);
    }

    private function renderException(Throwable $e): Response
    {
        $debug = filter_var(
            getenv('APP_DEBUG') ?: $this->app->config('app.debug', false),
            FILTER_VALIDATE_BOOL,
        );

        if ($debug) {
            return Response::json(
                [
                    'error' => $e::class,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }

        return Response::json(
            ['error' => 'Server Error'],
            Response::HTTP_INTERNAL_SERVER_ERROR,
        );
    }
}
