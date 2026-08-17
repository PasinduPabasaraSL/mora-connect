<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;

final class Auth
{
    /**
     * The signed-in user's row, loaded at most once per request.
     *
     * Read from the database rather than the session so a profile edit shows up
     * immediately and everywhere, instead of leaving a stale copy in the session
     * until the next sign-in. false means "not looked up yet", distinct from
     * null meaning "looked up, nobody there".
     *
     * @var array<string, mixed>|null|false
     */
    private static array|null|false $current = false;

    public static function check(): bool
    {
        return Session::has('user_id');
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function user(): ?array
    {
        if (self::$current !== false) {
            return self::$current;
        }

        $id = self::id();

        self::$current = $id === null ? null : (new User())->find($id);

        return self::$current;
    }

    /**
     * Forgets the cached row, for when the request itself has just changed it.
     */
    public static function refresh(): void
    {
        self::$current = false;
    }

    public static function id(): ?int
    {
        $id = Session::get('user_id');

        return $id === null ? null : (int) $id;
    }

    public static function username(): ?string
    {
        $name = Session::get('username');

        return $name === null ? null : (string) $name;
    }

    public static function role(): string
    {
        return (string) Session::get('role', 'guest');
    }

    /**
     * @param array{id: int|string, username: string, role?: string} $user
     */
    public static function login(array $user): void
    {
        Session::regenerate();

        Session::put('user_id', (int) $user['id']);
        Session::put('username', $user['username']);
        Session::put('role', $user['role'] ?? 'student');
    }

    public static function logout(): void
    {
        self::$current = false;

        Session::destroy();
    }
}
