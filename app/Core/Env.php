<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Reads settings from a .env file.
 *
 * Secrets do not belong in config.php, because that file is committed. This
 * keeps them in a file git ignores, and falls back to real environment
 * variables so the same code works if Apache or a host supplies them instead.
 *
 * Deliberately small: enough for KEY=value with comments and quotes, and none
 * of the variable interpolation or multi-line syntax a full parser handles.
 */
final class Env
{
    /** @var array<string, string> */
    private static array $values = [];

    private static bool $loaded = false;

    public static function load(string $file): void
    {
        self::$loaded = true;

        if (!is_readable($file)) {
            // A missing file is normal on a machine that sets real environment
            // variables instead, so this is not an error.
            return;
        }

        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);

            self::$values[trim($key)] = self::clean($value);
        }
    }

    /**
     * Strips the quotes a value may be wrapped in, and any trailing comment on
     * an unquoted value. A quoted value keeps its # so a password containing
     * one survives.
     */
    private static function clean(string $value): string
    {
        $value = trim($value);

        foreach (['"', "'"] as $quote) {
            if (strlen($value) > 1 && str_starts_with($value, $quote) && str_ends_with($value, $quote)) {
                return substr($value, 1, -1);
            }
        }

        $hash = strpos($value, ' #');

        return $hash === false ? $value : rtrim(substr($value, 0, $hash));
    }

    public static function get(string $key, string $default = ''): string
    {
        if (isset(self::$values[$key])) {
            return self::$values[$key];
        }

        $fromServer = getenv($key);

        return $fromServer === false ? $default : $fromServer;
    }

    /**
     * Whether a value is present and not blank. Used to decide if a feature is
     * configured at all — Google sign-in hides itself rather than offering a
     * button that leads to an error page.
     */
    public static function has(string $key): bool
    {
        return trim(self::get($key)) !== '';
    }

    public static function loaded(): bool
    {
        return self::$loaded;
    }
}
