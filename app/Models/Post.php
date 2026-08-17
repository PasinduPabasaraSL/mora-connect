<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Config;
use App\Core\Model;

final class Post extends Model
{
    public const STATUS_DRAFT     = 'draft';
    public const STATUS_PUBLISHED = 'published';

    public const VISIBILITY_PUBLIC   = 'public';
    public const VISIBILITY_UNLISTED = 'unlisted';

    private const LISTABLE = "blogPost.status = 'published' AND blogPost.visibility = 'public'";

    /**
     * The author columns every byline needs. Named once so adding something to a
     * byline is one edit rather than one per query.
     */
    private const AUTHOR = 'users.username, users.display_name,
                             users.avatar_path, users.google_avatar, users.avatar_source';

    /**
     * Columns the editor owns. Used to build INSERT and UPDATE statements from
     * one array so adding a field does not mean editing three method
     * signatures.
     *
     * @var list<string>
     */
    private const WRITABLE = [
        'title', 'subtitle', 'content', 'content_format', 'category', 'slug',
        'description', 'tags', 'status', 'visibility', 'comments_enabled',
        'word_count', 'reading_minutes', 'image_url', 'published_at',
    ];

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

    public static function slugify(string $text): string
    {
        $slug = strtolower(trim($text));
        $slug = preg_replace('/[^a-z0-9]+/u', '-', $slug) ?? '';

        return trim(mb_substr($slug, 0, 200), '-');
    }

    /**
     * Comma separated tags cleaned into a list, de-duplicated and capped so a
     * pasted wall of text cannot become 400 tags.
     *
     * @return list<string>
     */
    public static function parseTags(string $raw, int $max = 5): array
    {
        $tags = [];

        foreach (explode(',', $raw) as $tag) {
            $tag = trim(preg_replace('/\s+/', ' ', $tag) ?? '');

            if ($tag === '') {
                continue;
            }

            $tag = mb_substr($tag, 0, 30);

            // Case-insensitive de-dupe, keeping the author's capitalisation
            if (!in_array(mb_strtolower($tag), array_map('mb_strtolower', $tags), true)) {
                $tags[] = $tag;
            }

            if (count($tags) >= $max) {
                break;
            }
        }

        return $tags;
    }

    /**
     * @return list<string>
     */
    public static function tagList(?string $stored): array
    {
        return $stored === null || trim($stored) === '' ? [] : self::parseTags($stored, 10);
    }


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
            'SELECT blogPost.*, ' . self::AUTHOR . '
             FROM blogPost
             JOIN users ON blogPost.user_id = users.id
             WHERE ' . self::LISTABLE . '
             ORDER BY COALESCE(blogPost.published_at, blogPost.created_at) DESC'
        );
    }

    public function byCategory(string $category): array
    {
        return $this->select(
            'SELECT blogPost.*, ' . self::AUTHOR . '
             FROM blogPost
             JOIN users ON blogPost.user_id = users.id
             WHERE blogPost.category = ? AND ' . self::LISTABLE . '
             ORDER BY COALESCE(blogPost.published_at, blogPost.created_at) DESC',
            [$category]
        );
    }

    public function related(string $category, int $excludeId, int $limit = 3): array
    {
        return $this->select(
            'SELECT blogPost.*, ' . self::AUTHOR . '
             FROM blogPost
             JOIN users ON blogPost.user_id = users.id
             WHERE blogPost.category = ? AND blogPost.id <> ? AND ' . self::LISTABLE . '
             ORDER BY COALESCE(blogPost.published_at, blogPost.created_at) DESC
             LIMIT ' . $limit,
            [$category, $excludeId]
        );
    }

    public function search(string $term): array
    {
        $like = '%' . addcslashes($term, '%_\\') . '%';

        return $this->select(
            'SELECT blogPost.*, ' . self::AUTHOR . '
             FROM blogPost
             JOIN users ON blogPost.user_id = users.id
             WHERE (blogPost.title LIKE ? OR blogPost.subtitle LIKE ?
                    OR blogPost.content LIKE ? OR blogPost.tags LIKE ?)
               AND ' . self::LISTABLE . '
             ORDER BY COALESCE(blogPost.published_at, blogPost.created_at) DESC
             LIMIT 50',
            [$like, $like, $like, $like]
        );
    }

    public function findWithAuthor(int $id): ?array
    {
        return $this->selectOne(
            'SELECT blogPost.*, ' . self::AUTHOR . '
             FROM blogPost
             JOIN users ON blogPost.user_id = users.id
             WHERE blogPost.id = ?',
            [$id]
        );
    }

    public function findBySlugWithAuthor(string $slug): ?array
    {
        return $this->selectOne(
            'SELECT blogPost.*, ' . self::AUTHOR . '
             FROM blogPost
             JOIN users ON blogPost.user_id = users.id
             WHERE blogPost.slug = ?',
            [$slug]
        );
    }

    public function find(int $id): ?array
    {
        return $this->selectOne('SELECT * FROM blogPost WHERE id = ?', [$id]);
    }

    public function forUser(int $userId): array
    {
        return $this->select(
            "SELECT * FROM blogPost
              WHERE user_id = ?
              ORDER BY status = 'published', updated_at DESC",
            [$userId]
        );
    }

    /**
     * One author's articles as a visitor sees them: published, public, newest
     * first. Drafts and unlisted articles are the author's own business and are
     * only ever reachable from their profile.
     */
    public function publishedByUser(int $userId): array
    {
        return $this->select(
            'SELECT blogPost.*, ' . self::AUTHOR . '
             FROM blogPost
             JOIN users ON blogPost.user_id = users.id
             WHERE blogPost.user_id = ? AND ' . self::LISTABLE . '
             ORDER BY COALESCE(blogPost.published_at, blogPost.created_at) DESC',
            [$userId]
        );
    }

    /**
     * Totals for one author's public work, for the header of their page.
     *
     * @return array{articles: int, words: int, topics: int}
     */
    public function statsForUser(int $userId): array
    {
        $row = $this->selectOne(
            'SELECT COUNT(*) AS articles,
                    COALESCE(SUM(word_count), 0) AS words,
                    COUNT(DISTINCT category) AS topics
             FROM blogPost
             WHERE user_id = ? AND ' . self::LISTABLE,
            [$userId]
        ) ?? [];

        return [
            'articles' => (int) ($row['articles'] ?? 0),
            'words'    => (int) ($row['words'] ?? 0),
            'topics'   => (int) ($row['topics'] ?? 0),
        ];
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

        $rows = $this->select(
            'SELECT category, COUNT(*) AS total FROM blogPost
              WHERE ' . self::LISTABLE . '
              GROUP BY category'
        );

        foreach ($rows as $row) {
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
             FROM blogPost
             WHERE ' . self::LISTABLE
        ) ?? [];

        return [
            'articles' => (int) ($row['articles'] ?? 0),
            'writers'  => (int) ($row['writers'] ?? 0),
            'topics'   => (int) ($row['topics'] ?? 0),
        ];
    }

    /**
     * @param array<string, mixed> $fields
     */
    public function create(int $userId, array $fields): int
    {
        $fields  = $this->writableOnly($fields);
        $columns = array_keys($fields);

        $this->execute(
            'INSERT INTO blogPost (user_id, ' . implode(', ', $columns) . ')
             VALUES (?, ' . implode(', ', array_fill(0, count($columns), '?')) . ')',
            array_merge([$userId], array_values($fields))
        );

        return $this->lastInsertId();
    }

    /**
     * The user_id condition is part of the query, so a tampered post id can
     * never update someone else's row even if a controller check is missed.
     *
     * @param array<string, mixed> $fields
     */
    public function update(int $id, int $userId, array $fields): bool
    {
        $fields = $this->writableOnly($fields);

        if ($fields === []) {
            return false;
        }

        $assignments = implode(', ', array_map(
            static fn (string $column): string => $column . ' = ?',
            array_keys($fields)
        ));

        // rowCount() is 0 when a save changes nothing, which is not a failure,
        // so success is measured by the row existing rather than by rows hit.
        $this->execute(
            'UPDATE blogPost SET ' . $assignments . ' WHERE id = ? AND user_id = ?',
            array_merge(array_values($fields), [$id, $userId])
        );

        return $this->ownedBy($id, $userId);
    }

    public function ownedBy(int $id, int $userId): bool
    {
        return $this->selectOne(
            'SELECT id FROM blogPost WHERE id = ? AND user_id = ?',
            [$id, $userId]
        ) !== null;
    }

    /**
     * Drops anything the editor is not allowed to write, so a crafted form
     * field can never reach a column such as user_id or created_at.
     *
     * @param  array<string, mixed> $fields
     * @return array<string, mixed>
     */
    private function writableOnly(array $fields): array
    {
        return array_intersect_key($fields, array_flip(self::WRITABLE));
    }

    /**
     * A slug that no other article is using, by appending -2, -3 and so on.
     * $ignoreId lets an article keep its own slug while being edited.
     */
    public function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $base = $base === '' ? 'article' : $base;
        $slug = $base;

        for ($suffix = 2; $this->slugTaken($slug, $ignoreId); $suffix++) {
            $slug = $base . '-' . $suffix;
        }

        return $slug;
    }

    private function slugTaken(string $slug, ?int $ignoreId): bool
    {
        $row = $ignoreId === null
            ? $this->selectOne('SELECT id FROM blogPost WHERE slug = ?', [$slug])
            : $this->selectOne('SELECT id FROM blogPost WHERE slug = ? AND id <> ?', [$slug, $ignoreId]);

        return $row !== null;
    }

    public function delete(int $id, int $userId): bool
    {
        return $this->execute(
            'DELETE FROM blogPost WHERE id = ? AND user_id = ?',
            [$id, $userId]
        ) > 0;
    }
}
