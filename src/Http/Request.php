<?php

declare(strict_types=1);

namespace Xiaoui\Http;

/**
 * Immutable wrapper around the incoming PSR-7 style request data.
 */
class Request
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     * @param array<string, mixed> $files
     * @param array<string, mixed> $cookies
     * @param array<string, string> $server
     * @param array<string, string> $headers
     * @param array<string, mixed> $attributes route parameters
     */
    public function __construct(
        private readonly array $query = [],
        private readonly array $post = [],
        private readonly array $files = [],
        private readonly array $cookies = [],
        private readonly array $server = [],
        private readonly array $headers = [],
        private readonly array $attributes = [],
        private readonly ?string $body = null,
    ) {
    }

    /**
     * Create a Request from PHP superglobals.
     */
    public static function fromGlobals(): self
    {
        $body = file_get_contents('php://input') ?: null;

        return new self(
            query: $_GET,
            post: $_POST,
            files: $_FILES,
            cookies: $_COOKIE,
            server: $_SERVER,
            headers: self::extractHeaders($_SERVER),
            body: $body,
        );
    }

    public function method(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function path(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);

        return is_string($path) ? $path : '/';
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key]
            ?? $this->query[$key]
            ?? $this->json()[$key]
            ?? $default;
    }

    /**
     * @return array<string, mixed> all inputs (query + post + JSON body).
     */
    public function all(): array
    {
        return array_merge($this->query, $this->json(), $this->post);
    }

    public function header(string $key, ?string $default = null): ?string
    {
        $key = strtolower($key);

        return $this->headers[$key] ?? $default;
    }

    public function attribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Return a copy of the request with an additional route attribute.
     */
    public function withAttribute(string $key, mixed $value): self
    {
        return new self(
            $this->query,
            $this->post,
            $this->files,
            $this->cookies,
            $this->server,
            $this->headers,
            [...$this->attributes, $key => $value],
            $this->body,
        );
    }

    /**
     * @var array<string, mixed>|null cached decoded JSON body
     */
    private ?array $jsonCache = null;

    public function json(): array
    {
        if ($this->jsonCache !== null) {
            return $this->jsonCache;
        }

        if ($this->body === null) {
            return $this->jsonCache = [];
        }

        $decoded = json_decode($this->body, true);

        return $this->jsonCache = is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, string> $server
     * @return array<string, string>
     */
    private static function extractHeaders(array $server): array
    {
        $headers = [];

        foreach ($server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $headers[strtolower(str_replace('_', '-', $key))] = $value;
            }
        }

        return $headers;
    }
}
