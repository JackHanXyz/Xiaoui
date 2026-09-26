<?php

declare(strict_types=1);

namespace Xiaoui\Support;

/**
 * Loads environment variables from a .env file into the process.
 */
class Env
{
    public static function load(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (!str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Strip optional quotes.
            if (strlen($value) >= 2 && in_array($value[0], ['"', "'"], true)) {
                $value = substr($value, 1, -1);
            }

            if (getenv($key) === false) {
                putenv("{$key}={$value}");
            }

            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}
