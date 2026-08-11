<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class View
{
    public static function render(string $template, array $data = [], string $layout = 'main'): void
    {
        $content = self::capture($template, $data);

        $layoutFile = APP_PATH . '/Views/layouts/' . $layout . '.php';

        if (!is_file($layoutFile)) {
            throw new RuntimeException("Layout [{$layout}] not found.");
        }

        $title = $data['title'] ?? Config::get('name');

        $scripts   = $data['scripts'] ?? [];
        $bodyClass = $data['bodyClass'] ?? '';

        require $layoutFile;
    }

    public static function capture(string $template, array $data = []): string
    {
        $file = APP_PATH . '/Views/' . $template . '.php';

        if (!is_file($file)) {
            throw new RuntimeException("View [{$template}] not found.");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $file;

        return (string) ob_get_clean();
    }

    public static function partial(string $template, array $data = []): void
    {
        echo self::capture($template, $data);
    }
}
