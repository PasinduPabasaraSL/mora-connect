<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    public function __construct(protected Request $request)
    {
    }

    protected function view(string $template, array $data = []): void
    {
        View::render($template, $data);
    }

    /**
     * Redirect to an app-relative path, e.g. redirect('posts/5').
     */
    protected function redirect(string $path = ''): never
    {
        header('Location: ' . url($path));
        exit;
    }

    /**
     * Ends the request with a JSON body. Used by the editor's autosave, which
     * needs a machine-readable answer rather than a rendered page.
     *
     * @param array<string, mixed> $payload
     */
    protected function json(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        // Autosave replies must never be reused by a proxy or the back button
        header('Cache-Control: no-store');

        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        exit;
    }

    protected function requireLogin(): void
    {
        if (!Auth::check()) {
            Session::flash('error', 'Please sign in to continue.');
            $this->redirect('login');
        }
    }

    protected function requireCsrf(): void
    {
        if (!Csrf::verify($this->request->raw('_token'))) {
            $this->abort(403, 'Your session has expired or the form was not submitted from this site. Please go back and try again.');
        }
    }

    protected function abort(int $status, string $message = ''): never
    {
        http_response_code($status);

        $template = match ($status) {
            403 => 'errors/403',
            404 => 'errors/404',
            default => 'errors/generic',
        };

        View::render($template, [
            'title'   => 'Error ' . $status,
            'status'  => $status,
            'message' => $message,
        ]);

        exit;
    }
}
