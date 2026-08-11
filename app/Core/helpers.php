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
    /**
     * Appends the file's modification time so an edited stylesheet or script is
     * never served from the browser cache after a change.
     */
    function asset(string $path): string
    {
        $path = ltrim($path, '/');
        $file = ROOT_PATH . '/assets/' . $path;
        $url  = url('assets/' . $path);

        return is_file($file) ? $url . '?v=' . filemtime($file) : $url;
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

if (!function_exists('post_summary')) {
    /**
     * The line of text shown under an article's title in listings.
     *
     * An author-written summary is used when there is one; otherwise the body is
     * flattened to text first, because an excerpt taken straight from markup
     * would run words together where tags used to be.
     *
     * @param array<string, mixed> $post
     */
    function post_summary(array $post, int $length = 160): string
    {
        $description = trim((string) ($post['description'] ?? ''));

        if ($description !== '') {
            return excerpt($description, $length);
        }

        return excerpt(App\Core\Html::toText((string) ($post['content'] ?? '')), $length);
    }
}

if (!function_exists('post_minutes')) {
    /**
     * Reading time as stored when the article was saved, so every listing and
     * the article itself quote the same number.
     *
     * @param array<string, mixed> $post
     */
    function post_minutes(array $post): string
    {
        $minutes = (int) ($post['reading_minutes'] ?? 0);

        return ($minutes > 0 ? $minutes : App\Core\Html::readingMinutes(
            App\Core\Html::wordCount((string) ($post['content'] ?? ''))
        )) . ' min read';
    }
}

if (!function_exists('post_date')) {
    /**
     * When an article went live, falling back to when it was written for rows
     * that predate the published_at column.
     *
     * @param array<string, mixed> $post
     */
    function post_date(array $post): string
    {
        return format_date($post['published_at'] ?? $post['created_at'] ?? null);
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
