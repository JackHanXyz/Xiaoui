<?php

declare(strict_types=1);

namespace Xiaoui\Routing;

/**
 * A single route definition.
 */
class Route
{
    /**
     * @param list<string> $methods
     * @param list<class-string> $middleware
     * @param array<string, string> $parameters pattern names
     */
    public function __construct(
        public readonly array $methods,
        public readonly string $path,
        public readonly mixed $handler,
        public readonly array $middleware = [],
        public readonly array $parameters = [],
    ) {
    }

    /**
     * Build a regex from the path and extract parameter names.
     */
    public static function compile(string $path): array
    {
        $parameters = [];

        $pattern = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)(?::([^}]+))?\}/',
            static function (array $m) use (&$parameters): string {
                $parameters[] = $m[1];
                $sub = $m[2] ?? '[^/]+';

                return "(?P<{$m[1]}>{$sub})";
            },
            $path,
        );

        return ['~^' . $pattern . '$~', $parameters];
    }
}
