<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    private function __construct(
        private string $method,
        private string $path,
        private array $query,
        private array $body
    ) {
    }

    public static function capture(): self
    {
        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            self::resolvePath(),
            $_GET,
            $_POST
        );
    }

    private static function resolvePath(): string
    {
        if (isset($_GET['url'])) {
            return '/' . trim((string) $_GET['url'], '/');
        }

        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base = self::basePath();

        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }

        return '/' . trim($uri, '/');
    }

    public static function basePath(): string
    {
        $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));

        return $dir === '/' ? '' : rtrim($dir, '/');
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function input(string $key, string $default = ''): string
    {
        $value = $this->body[$key] ?? $this->query[$key] ?? $default;

        return is_string($value) ? trim($value) : $default;
    }

    public function raw(string $key, string $default = ''): string
    {
        $value = $this->body[$key] ?? $default;

        return is_string($value) ? $value : $default;
    }
}
