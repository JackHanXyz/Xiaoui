<?php

declare(strict_types=1);

namespace Xiaoui;

use Closure;
use ReflectionFunction;
use ReflectionMethod;
use Xiaoui\Container\Container;
use Xiaoui\Http\Request;
use Xiaoui\Http\Response;
use Xiaoui\Routing\RouteCollection;
use Xiaoui\Routing\Router;
use Xiaoui\Support\Config;

/**
 * The framework application. Binds core services and resolves the router.
 */
class Application extends Container
{
    /**
     * @param string $basePath absolute path to the project root
     */
    public function __construct(private readonly string $basePath)
    {
        $this->registerBaseBindings();
    }

    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }

    private function registerBaseBindings(): void
    {
        $this->instance(Container::class, $this);
        $this->instance(self::class, $this);

        $this->singleton(RouteCollection::class);
        $this->singleton(Router::class, fn (Container $c) => new Router($c->get(RouteCollection::class)));
        $this->singleton(Config::class, fn () => new Config());
    }

    /**
     * Load all config files from the given directory into the Config instance.
     */
    public function loadConfig(string $configPath): Config
    {
        $config = $this->get(Config::class);

        foreach (glob($configPath . '/*.php') ?: [] as $file) {
            $key = basename($file, '.php');
            $items = require $file;

            if (is_array($items)) {
                $config->set($key, $items);
            }
        }

        return $config;
    }

    /**
     * Read a configuration value using dot notation.
     */
    public function config(string $key, mixed $default = null): mixed
    {
        return $this->get(Config::class)->get($key, $default);
    }

    /**
     * Resolve the router, load route files and return the collection.
     */
    public function routes(string $routesPath): RouteCollection
    {
        $router = $this->get(Router::class);

        foreach (glob($routesPath . '/*.php') ?: [] as $file) {
            require $file;
        }

        return $this->get(RouteCollection::class);
    }

    /**
     * Invoke a route handler (closure, [Class, method] or class string) with
     * dependency injection on its parameters.
     */
    public function call(mixed $handler, Request $request): mixed
    {
        // [Controller::class, 'method'] tuple.
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $instance = $this->get($class);

            return $this->invoke([$instance, $method], $request);
        }

        // "Controller@method" string shorthand.
        if (is_string($handler) && str_contains($handler, '@')) {
            [$class, $method] = explode('@', $handler, 2);

            return $this->invoke([$this->get($class), $method], $request);
        }

        // Class string implementing __invoke.
        if (is_string($handler) && class_exists($handler)) {
            return $this->invoke([$this->get($handler), '__invoke'], $request);
        }

        // Plain closure.
        if ($handler instanceof Closure) {
            return $this->invoke($handler, $request);
        }

        return $handler;
    }

    /**
     * @param callable $callable
     */
    private function invoke(callable $callable, Request $request): mixed
    {
        if (is_array($callable)) {
            $reflector = new ReflectionMethod($callable[0], $callable[1]);
        } else {
            $reflector = new ReflectionFunction($callable);
        }

        $args = [];

        foreach ($reflector->getParameters() as $parameter) {
            $args[] = $this->resolveArgument($parameter, $request);
        }

        if (is_array($callable)) {
            return $reflector->invokeArgs($callable[0], $args);
        }

        return $reflector->invokeArgs($args);
    }

    private function resolveArgument(\ReflectionParameter $parameter, Request $request): mixed
    {
        $type = $parameter->getType();

        if ($type !== null && !$type->isBuiltin()) {
            $name = $type->getName();

            if ($name === Request::class) {
                return $request;
            }

            if ($this->has($name)) {
                return $this->get($name);
            }
        }

        if ($parameter->getName() === 'request') {
            return $request;
        }

        // Route parameter by name.
        if ($request->attribute($parameter->getName()) !== null) {
            return $request->attribute($parameter->getName());
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        return null;
    }

    /**
     * A convenience factory to build a response.
     */
    public function respond(mixed $data, int $status = Response::HTTP_OK): Response
    {
        if ($data instanceof Response) {
            return $data;
        }

        if (is_array($data) || is_object($data)) {
            return Response::json($data, $status);
        }

        return Response::text((string) $data, $status);
    }
}
