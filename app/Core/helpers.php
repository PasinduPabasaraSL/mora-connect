<?php

declare(strict_types=1);

use App\Core\Request;

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = Request::basePath();
        $path = trim($path, '/');

        return $path === '' ? ($base ?: '/') : $base . '/' . $path;
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('reading_time')) {
    function reading_time(string $content): string
    {
        $words = str_word_count(strip_tags($content));

        return max(1, (int) round($words / 200)) . ' min read';
    }
}

if (!function_exists('excerpt')) {
    function excerpt(string $content, int $length = 160): string
    {
        $plain = trim(preg_replace('/\s+/', ' ', strip_tags($content)) ?? '');

        if (mb_strlen($plain) <= $length) {
            return $plain;
        }

        // Trim trailing punctuation so a cut landing after a full stop does
        // not read as "...."
        return rtrim(mb_substr($plain, 0, $length), ' .,;:!?-') . '...';
    }
}

if (!function_exists('format_date')) {
    function format_date(?string $timestamp): string
    {
        if ($timestamp === null) {
            return '';
        }

        $time = strtotime($timestamp);

        return $time === false ? '' : date('M j, Y', $time);
    }
}
