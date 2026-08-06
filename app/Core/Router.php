<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class Router
{
    /** @var array<int, array{method: string, pattern: string, handler: string}> */
    private array $routes = [];

    public function get(string $pattern, string $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, string $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    private function add(string $method, string $pattern, string $handler): void
    {
        $this->routes[] = [
            'method'  => $method,
            'pattern' => '/' . trim($pattern, '/'),
            'handler' => $handler,
        ];
    }

    public function dispatch(Request $request): void
    {
        foreach ($this->routes as $route) {
            $params = $this->match($route['pattern'], $request->path());

            if ($params === null) {
                continue;
            }

            if ($route['method'] !== $request->method()) {
                continue;
            }

            $this->run($route['handler'], $params, $request);

            return;
        }

        $this->notFound();
    }

    private function match(string $pattern, string $path): ?array
    {
        if ($pattern === $path) {
            return [];
        }

        if (!str_contains($pattern, '{')) {
            return null;
        }

        $token = '__SEGMENT__';
        $masked = preg_replace('#\{[a-zA-Z_]+\}#', $token, $pattern);
        $regex = str_replace($token, '([^/]+)', preg_quote((string) $masked, '#'));

        if (!preg_match('#^' . $regex . '$#', $path, $matches)) {
            return null;
        }

        array_shift($matches);

        return $matches;
    }

    private function run(string $handler, array $params, Request $request): void
    {
        [$controller, $action] = explode('@', $handler);

        $class = 'App\\Controllers\\' . $controller;

        if (!class_exists($class) || !method_exists($class, $action)) {
            throw new RuntimeException("Route handler {$handler} is not callable.");
        }

        $instance = new $class($request);
        $instance->{$action}(...$params);
    }

    private function notFound(): void
    {
        http_response_code(404);
        View::render('errors/404', ['title' => 'Page not found']);
    }
}
