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
        return password_verify($password, $user['password'] ?? '');
    }
}
