<?php

declare(strict_types=1);

namespace Xiaoui\Routing;

use Xiaoui\Http\MiddlewareInterface;

/**
 * Collects route definitions and resolves an incoming request to a Route.
 */
class RouteCollection
{
    /** @var list<Route> */
    private array $routes = [];

    /** @var list<class-string<MiddlewareInterface>> */
    private array $globalMiddleware = [];

    /**
     * @param list<string> $methods
     */
    public function add(
        array $methods,
        string $path,
        mixed $handler,
        array $middleware = [],
    ): Route {
        [$pattern, $parameters] = Route::compile($path);

        $route = new Route($methods, $pattern, $handler, $middleware, $parameters);

        $this->routes[] = $route;

        return $route;
    }

    /**
     * @param class-string<MiddlewareInterface> $middleware
     */
    public function middleware(string $middleware): void
    {
        $this->globalMiddleware[] = $middleware;
    }

    /**
     * @return list<class-string<MiddlewareInterface>>
     */
    public function globalMiddleware(): array
    {
        return $this->globalMiddleware;
    }

    /**
     * Match a method + path against the registered routes.
     *
     * @return array{route: Route, params: array<string, string>}|null
     */
    public function match(string $method, string $path): ?array
    {
        foreach ($this->routes as $route) {
            if (!in_array($method, $route->methods, true)) {
                continue;
            }

            if (preg_match($route->path, $path, $matches) === 1) {
                $params = [];

                foreach ($route->parameters as $name) {
                    $params[$name] = $matches[$name];
                }

                return ['route' => $route, 'params' => $params];
            }
        }

        return null;
    }
}
