<?php

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    public static function check(): bool
    {
        return Session::has('user_id');
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
        Session::destroy();
    }
}
