<?php

declare(strict_types=1);

namespace Xiaoui\Routing;

use Closure;

/**
 * Fluent route registrar. Provides the ergonomic API used in route files.
 */
class Router
{
    /**
     * @var array{prefix: string, middleware: list<class-string>}
     */
    private array $group = [
        'prefix' => '',
        'middleware' => [],
    ];

    public function __construct(private readonly RouteCollection $collection)
    {
    }

    /**
     * Register a route for one or more HTTP verbs.
     *
     * @param string|list<string> $methods
     */
    public function add(string|array $methods, string $path, mixed $handler): Route
    {
        $methods = array_map('strtoupper', (array) $methods);

        return $this->collection->add(
            $methods,
            $this->group['prefix'] . $path,
            $handler,
            $this->group['middleware'],
        );
    }

    public function get(string $path, mixed $handler): Route
    {
        return $this->add('GET', $path, $handler);
    }

    public function post(string $path, mixed $handler): Route
    {
        return $this->add('POST', $path, $handler);
    }

    public function put(string $path, mixed $handler): Route
    {
        return $this->add('PUT', $path, $handler);
    }

    public function patch(string $path, mixed $handler): Route
    {
        return $this->add('PATCH', $path, $handler);
    }

    public function delete(string $path, mixed $handler): Route
    {
        return $this->add('DELETE', $path, $handler);
    }

    public function any(string $path, mixed $handler): Route
    {
        return $this->add(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $path, $handler);
    }

    /**
     * Register a global middleware applied to every route.
     *
     * @param class-string $middleware
     */
    public function middleware(string $middleware): void
    {
        $this->collection->middleware($middleware);
    }

    /**
     * Group routes under a common prefix and middleware stack.
     *
     * @param array{prefix?: string, middleware?: list<class-string>} $attributes
     */
    public function group(array $attributes, Closure $callback): void
    {
        $previous = $this->group;

        $this->group = [
            'prefix' => $previous['prefix'] . ($attributes['prefix'] ?? ''),
            'middleware' => [
                ...$previous['middleware'],
                ...($attributes['middleware'] ?? []),
            ],
        ];

        try {
            $callback($this);
        } finally {
            $this->group = $previous;
        }
    }
}
