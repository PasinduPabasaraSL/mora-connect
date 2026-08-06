<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Config;
use App\Core\Model;

final class Post extends Model
{
    /**
     * @return list<string>
     */
    public static function categories(): array
    {
        return array_keys(Config::get('categories', []));
    }

    public static function isValidCategory(string $category): bool
    {
        return in_array($category, self::categories(), true);
    }

    public static function gradientFor(?string $category): string
    {
        $gradients = Config::get('categories', []);

        return $gradients[$category] ?? $gradients['Other'] ?? 'linear-gradient(135deg, #74777a, #c4c7c9)';
    }

    public function allWithAuthor(): array
    {
        return $this->select(
            'SELECT blogPost.*, users.username
             FROM blogPost
             JOIN users ON blogPost.user_id = users.id
             ORDER BY blogPost.created_at DESC'
        );
    }

    public function findWithAuthor(int $id): ?array
    {
        return $this->selectOne(
            'SELECT blogPost.*, users.username
             FROM blogPost
             JOIN users ON blogPost.user_id = users.id
             WHERE blogPost.id = ?',
            [$id]
        );
    }

    public function find(int $id): ?array
    {
        return $this->selectOne('SELECT * FROM blogPost WHERE id = ?', [$id]);
    }

    public function forUser(int $userId): array
    {
        return $this->select(
            'SELECT * FROM blogPost WHERE user_id = ? ORDER BY created_at DESC',
            [$userId]
        );
    }

    public function create(int $userId, string $title, string $content, string $category): int
    {
        $this->execute(
            'INSERT INTO blogPost (user_id, title, content, category) VALUES (?, ?, ?, ?)',
            [$userId, $title, $content, $category]
        );

        return $this->lastInsertId();
    }

    /**
     * The user_id condition is part of the query, so a tampered post id can
     * never update someone else's row even if a controller check is missed.
     */
    public function update(int $id, int $userId, string $title, string $content, string $category): bool
    {
        return $this->execute(
            'UPDATE blogPost SET title = ?, content = ?, category = ? WHERE id = ? AND user_id = ?',
            [$title, $content, $category, $id, $userId]
        ) > 0;
    }

    public function delete(int $id, int $userId): bool
    {
        return $this->execute(
            'DELETE FROM blogPost WHERE id = ? AND user_id = ?',
            [$id, $userId]
        ) > 0;
    }
}
