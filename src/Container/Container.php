<?php

declare(strict_types=1);

namespace Xiaoui\Container;

use Closure;
use ReflectionClass;
use ReflectionException;
use RuntimeException;

/**
 * A minimal dependency injection container.
 *
 * Exposes the PSR-11 shaped get()/has() API without requiring the
 * psr/container package, keeping the framework dependency-free.
 *
 * Supports auto-wiring via reflection, explicit bindings and singletons.
 */
class Container
{
    /**
     * @var array<string, mixed> resolved singleton instances
     */
    private array $instances = [];

    /**
     * @var array<string, Closure|string|object> bindings
     */
    private array $bindings = [];
    /**
     * Bind an abstract to a concrete factory or class name.
     */
    public function bind(string $abstract, object|string $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
    }

    /**
     * Bind an abstract and share a single instance.
     */
    public function singleton(string $abstract, object|string|null $concrete = null): void
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }

        $this->bind($abstract, $concrete);
        $this->instances[$abstract] = null;
    }

    /**
     * Register an already-instantiated object.
     */
    public function instance(string $abstract, object $instance): object
    {
        $this->instances[$abstract] = $instance;

        return $instance;
    }

    public function get(string $id): mixed
    {
        if ($this->has($id) && $this->instances[$id] !== null) {
            return $this->instances[$id];
        }

        $resolved = $this->resolve($id);

        // If it was registered as a singleton, cache the result.
        if (array_key_exists($id, $this->instances)) {
            $this->instances[$id] = $resolved;
        }

        return $resolved;
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->instances)
            || array_key_exists($id, $this->bindings);
    }

    /**
     * Resolve a concrete definition, auto-wiring constructor dependencies.
     *
     * @throws RuntimeException when the abstract cannot be resolved.
     */
    protected function resolve(string $abstract): mixed
    {
        $concrete = $this->bindings[$abstract] ?? $abstract;

        // A closure factory is invoked directly.
        if ($concrete instanceof Closure) {
            return $concrete($this);
        }

        // A concrete instance is returned as-is.
        if (is_object($concrete)) {
            return $concrete;
        }

        if (!is_string($concrete) || !class_exists($concrete)) {
            throw new RuntimeException("Unable to resolve [{$abstract}].");
        }

        return $this->build($concrete);
    }

    /**
     * Build a class through reflection-based auto-wiring.
     *
     * @throws ReflectionException
     */
    protected function build(string $concrete): object
    {
        $reflector = new ReflectionClass($concrete);

        if (!$reflector->isInstantiable()) {
            throw new RuntimeException("Target [{$concrete}] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $concrete();
        }

        $dependencies = [];

        foreach ($constructor->getParameters() as $parameter) {
            $dependencies[] = $this->resolveParameter($parameter);
        }

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * @throws ReflectionException
     */
    protected function resolveParameter(\ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        if ($type === null || $type->isBuiltin()) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }

            throw new RuntimeException(
                "Unable to resolve parameter [\${$parameter->getName()}]."
            );
        }

        $name = $type->getName();

        // Try an explicit binding first, then fall back to the class name.
        return $this->get($name);
    }
}
