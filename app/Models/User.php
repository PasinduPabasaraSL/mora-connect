<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class User extends Model
{
    public function findByLogin(string $identifier): ?array
    {
        return $this->selectOne(
            'SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1',
            [$identifier, $identifier]
        );
    }

    public function existsByUsernameOrEmail(string $username, string $email): bool
    {
        return $this->selectOne(
            'SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1',
            [$username, $email]
        ) !== null;
    }

    /**
     * Stores the bcrypt hash of the password, never the password itself.
     */
    public function create(string $username, string $email, string $password): int
    {
        $this->execute(
            "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'student')",
            [$username, $email, password_hash($password, PASSWORD_DEFAULT)]
        );

        return $this->lastInsertId();
    }

    public function verifyPassword(array $user, string $password): bool
    {
        $hash = $user['password'] ?? null;

        // A Google-only account has no hash. password_verify() would simply
        // return false, but being explicit keeps the intent obvious.
        return is_string($hash) && $hash !== '' && password_verify($password, $hash);
    }

    /**
     * Whether this account can be signed into with a password at all.
     *
     * @param array<string, mixed> $user
     */
    public static function hasPassword(array $user): bool
    {
        return is_string($user['password'] ?? null) && $user['password'] !== '';
    }

    public function findByGoogleId(string $googleId): ?array
    {
        return $this->selectOne('SELECT * FROM users WHERE google_id = ? LIMIT 1', [$googleId]);
    }

    public function findByEmail(string $email): ?array
    {
        return $this->selectOne('SELECT * FROM users WHERE email = ? LIMIT 1', [$email]);
    }

    /**
     * Records that an existing account is also reachable through Google, so the
     * next sign-in matches on the subject id instead of the email.
     */
    public function linkGoogle(int $userId, string $googleId): void
    {
        $this->execute('UPDATE users SET google_id = ? WHERE id = ?', [$googleId, $userId]);
    }

    /**
     * Creates an account for someone arriving from Google. There is no password
     * to store, so that column stays null and password sign-in is closed for
     * this account until they set one.
     */
    public function createFromGoogle(string $username, string $email, string $googleId): int
    {
        $this->execute(
            "INSERT INTO users (username, email, google_id, password, role)
             VALUES (?, ?, ?, NULL, 'student')",
            [$username, $email, $googleId]
        );

        return $this->lastInsertId();
    }

    /**
     * Turns a Google display name into a username nobody is using yet.
     *
     * The name is whatever the person put on their Google account, so it may be
     * empty, may collide, and may be nothing but punctuation — hence the
     * fallback to the local part of the email, and then to a plain word.
     */
    public function uniqueUsername(string $preferred, string $email): string
    {
        $base = strtolower(trim($preferred));
        $base = preg_replace('/[^a-z0-9._-]+/', '', str_replace(' ', '', $base)) ?? '';

        if (mb_strlen($base) < 3) {
            $base = preg_replace('/[^a-z0-9._-]+/', '', strtolower(explode('@', $email)[0])) ?? '';
        }

        if (mb_strlen($base) < 3) {
            $base = 'writer';
        }

        $base = mb_substr($base, 0, 90);
        $username = $base;

        for ($suffix = 2; $this->usernameTaken($username); $suffix++) {
            $username = $base . $suffix;
        }

        return $username;
    }

    private function usernameTaken(string $username): bool
    {
        return $this->selectOne('SELECT id FROM users WHERE username = ? LIMIT 1', [$username]) !== null;
    }
}
