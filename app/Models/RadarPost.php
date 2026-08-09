<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Curated articles published elsewhere. Rows are written by import_radar.php;
 * nothing in the application creates or edits them.
 */
final class RadarPost extends Model
{
    public function all(): array
    {
        return $this->select(
            'SELECT * FROM radar_posts ORDER BY reactions DESC, published_at DESC'
        );
    }

    public function byCategory(string $category): array
    {
        return $this->select(
            'SELECT * FROM radar_posts WHERE category = ? ORDER BY reactions DESC, published_at DESC',
            [$category]
        );
    }

    /**
     * One page of entries, optionally limited to a topic.
     *
     * LIMIT and OFFSET are cast integers rather than bound parameters: with
     * emulated prepares turned off, MySQL rejects them when bound as strings.
     */
    public function page(?string $category, int $limit, int $offset): array
    {
        $where    = $category === null ? '' : ' WHERE category = ?';
        $bindings = $category === null ? [] : [$category];

        return $this->select(
            'SELECT * FROM radar_posts' . $where
            . ' ORDER BY reactions DESC, published_at DESC'
            . ' LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            $bindings
        );
    }

    public function total(?string $category = null): int
    {
        $row = $category === null
            ? $this->selectOne('SELECT COUNT(*) AS total FROM radar_posts')
            : $this->selectOne('SELECT COUNT(*) AS total FROM radar_posts WHERE category = ?', [$category]);

        return (int) ($row['total'] ?? 0);
    }

    /** @return array<string, int> topic => number of entries */
    public function countsByCategory(): array
    {
        $counts = array_fill_keys(Post::categories(), 0);

        foreach ($this->select('SELECT category, COUNT(*) AS total FROM radar_posts GROUP BY category') as $row) {
            $counts[$row['category']] = (int) $row['total'];
        }

        return $counts;
    }

    public function stats(): array
    {
        $row = $this->selectOne(
            'SELECT COUNT(*) AS entries,
                    COUNT(DISTINCT author) AS authors,
                    COUNT(DISTINCT source) AS sources,
                    MAX(fetched_at) AS updated
             FROM radar_posts'
        ) ?? [];

        return [
            'entries' => (int) ($row['entries'] ?? 0),
            'authors' => (int) ($row['authors'] ?? 0),
            'sources' => (int) ($row['sources'] ?? 0),
            'updated' => $row['updated'] ?? null,
        ];
    }
}
