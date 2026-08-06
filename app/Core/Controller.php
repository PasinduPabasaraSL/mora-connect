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
