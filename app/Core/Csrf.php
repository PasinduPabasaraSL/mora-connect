<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    private const KEY = '_csrf_token';

    public static function token(): string
    {
        if (!Session::has(self::KEY)) {
            Session::put(self::KEY, bin2hex(random_bytes(32)));
        }

        return (string) Session::get(self::KEY);
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function verify(?string $token): bool
    {
        if (!is_string($token) || $token === '' || !Session::has(self::KEY)) {
            return false;
        }

        return hash_equals((string) Session::get(self::KEY), $token);
    }
}
