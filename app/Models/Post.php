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

    /**
     * Badge colours for a topic: ['bg' => '#hex', 'ink' => '#hex'].
     *
     * @return array{bg: string, ink: string}
     */
    public static function colorsFor(?string $category): array
    {
        $all = Config::get('categories', []);

        return $all[$category] ?? $all['Other'] ?? ['bg' => '#64748b', 'ink' => '#ffffff'];
    }

    /**
     * Categories arranged into the header menu's columns. Categories not listed
     * in any group are collected into a trailing "More" column so nothing is
     * silently dropped from the menu.
     *
     * @return array<string, list<string>>
     */
    public static function groupedCategories(): array
    {
        $groups = [];
        $placed = [];

        foreach (Config::get('topic_groups', []) as $heading => $topics) {
            $valid = array_values(array_filter($topics, [self::class, 'isValidCategory']));

            if ($valid !== []) {
                $groups[$heading] = $valid;
                $placed = array_merge($placed, $valid);
            }
        }

        $leftover = array_values(array_diff(self::categories(), $placed));

        if ($leftover !== []) {
            $groups['More'] = $leftover;
        }

        return $groups;
    }

    public static function slugFor(string $category): string
    {
        return strtolower(str_replace(' ', '-', $category));
    }

    /**
     * Reverses slugFor(), so /topics/web-development finds "Web Development".
     */
    public static function categoryFromSlug(string $slug): ?string
    {
        foreach (self::categories() as $category) {
            if (self::slugFor($category) === $slug) {
                return $category;
            }
        }

        return null;
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

    public function byCategory(string $category): array
    {
        return $this->select(
            'SELECT blogPost.*, users.username
             FROM blogPost
             JOIN users ON blogPost.user_id = users.id
             WHERE blogPost.category = ?
             ORDER BY blogPost.created_at DESC',
            [$category]
        );
    }

    /**
     * Other articles on the same topic, used to keep the article page from
     * ending in a dead stop.
     */
    public function related(string $category, int $excludeId, int $limit = 3): array
    {
        return $this->select(
            'SELECT blogPost.*, users.username
             FROM blogPost
             JOIN users ON blogPost.user_id = users.id
             WHERE blogPost.category = ? AND blogPost.id <> ?
             ORDER BY blogPost.created_at DESC
             LIMIT ' . $limit,
            [$category, $excludeId]
        );
    }

    /**
     * Title and body search. The term is escaped for LIKE as well as bound,
     * so a user typing % or _ searches for those characters literally.
     */
    public function search(string $term): array
    {
        $like = '%' . addcslashes($term, '%_\\') . '%';

        return $this->select(
            'SELECT blogPost.*, users.username
             FROM blogPost
             JOIN users ON blogPost.user_id = users.id
             WHERE blogPost.title LIKE ? OR blogPost.content LIKE ?
             ORDER BY blogPost.created_at DESC
             LIMIT 50',
            [$like, $like]
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

    /**
     * Article count per topic, keyed by category name. Topics with no articles
     * are included with a zero so the topic strip stays a stable width.
     *
     * @return array<string, int>
     */
    public function countsByCategory(): array
    {
        $counts = array_fill_keys(self::categories(), 0);

        foreach ($this->select('SELECT category, COUNT(*) AS total FROM blogPost GROUP BY category') as $row) {
            $counts[$row['category']] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * @return array{articles: int, writers: int, topics: int}
     */
    public function stats(): array
    {
        $row = $this->selectOne(
            'SELECT COUNT(*) AS articles,
                    COUNT(DISTINCT user_id) AS writers,
                    COUNT(DISTINCT category) AS topics
             FROM blogPost'
        ) ?? [];

        return [
            'articles' => (int) ($row['articles'] ?? 0),
            'writers'  => (int) ($row['writers'] ?? 0),
            'topics'   => (int) ($row['topics'] ?? 0),
        ];
    }

    public function create(int $userId, string $title, string $content, string $category, ?string $imageUrl = null): int
    {
        $this->execute(
            'INSERT INTO blogPost (user_id, title, content, category, image_url) VALUES (?, ?, ?, ?, ?)',
            [$userId, $title, $content, $category, $imageUrl]
        );

        return $this->lastInsertId();
    }

    /**
     * The user_id condition is part of the query, so a tampered post id can
     * never update someone else's row even if a controller check is missed.
     */
    public function update(int $id, int $userId, string $title, string $content, string $category, ?string $imageUrl = null): bool
    {
        return $this->execute(
            'UPDATE blogPost SET title = ?, content = ?, category = ?, image_url = ? WHERE id = ? AND user_id = ?',
            [$title, $content, $category, $imageUrl, $id, $userId]
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
