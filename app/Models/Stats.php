<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Aggregate figures for the admin panel.
 *
 * Read-only, and deliberately separate from Post and User: those two answer
 * questions the site itself asks while serving a page, whereas everything here
 * exists only to describe the state of the portal as a whole.
 *
 * Nothing in this class needs a column that does not already exist. There is no
 * view tracking anywhere in the application, so these figures describe what has
 * been written and by whom, never what has been read.
 *
 * Every count is computed on demand. At this size that is cheaper and simpler
 * than maintaining counter columns that could drift out of step with reality.
 */
final class Stats extends Model
{
    /** How many months of history the growth charts cover. */
    private const MONTHS = 12;

    /**
     * The headline figures, gathered in one round trip. Correlated subqueries
     * rather than joins, because these count rows in unrelated tables and a
     * join would multiply them together.
     *
     * @return array<string, int>
     */
    public function overview(): array
    {
        $row = $this->selectOne(
            "SELECT
                (SELECT COUNT(*) FROM users) AS members,
                (SELECT COUNT(*) FROM blogPost) AS articles,
                (SELECT COUNT(*) FROM blogPost
                  WHERE status = 'published' AND visibility = 'public') AS live,
                (SELECT COUNT(*) FROM blogPost
                  WHERE status = 'published' AND visibility = 'unlisted') AS unlisted,
                (SELECT COUNT(*) FROM blogPost WHERE status = 'draft') AS drafts,
                (SELECT COALESCE(SUM(word_count), 0) FROM blogPost
                  WHERE status = 'published') AS words,
                (SELECT COUNT(DISTINCT user_id) FROM blogPost
                  WHERE status = 'published') AS writers,
                (SELECT COUNT(*) FROM radar_posts) AS radar"
        ) ?? [];

        return array_map(static fn ($value): int => (int) $value, $row);
    }

    /**
     * Accounts created per month, oldest first, with empty months filled in.
     *
     * @return array<string, int> label => count
     */
    public function signupsByMonth(): array
    {
        return $this->fillMonths($this->select(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS total
               FROM users
              WHERE created_at >= ?
              GROUP BY DATE_FORMAT(created_at, '%Y-%m')",
            [$this->windowStart()]
        ));
    }

    /**
     * Articles published per month. Counted on published_at rather than
     * created_at, because when something went live is a different question from
     * when it was started.
     *
     * @return array<string, int>
     */
    public function publicationsByMonth(): array
    {
        return $this->fillMonths($this->select(
            "SELECT DATE_FORMAT(published_at, '%Y-%m') AS ym, COUNT(*) AS total
               FROM blogPost
              WHERE status = 'published' AND published_at >= ?
              GROUP BY DATE_FORMAT(published_at, '%Y-%m')",
            [$this->windowStart()]
        ));
    }

    /**
     * The most recently touched articles, whatever their state, so the panel
     * opens on what is actually happening rather than only on what is public.
     *
     * @return list<array<string, mixed>>
     */
    public function recentArticles(int $limit = 8): array
    {
        // Inlined rather than bound: prepared statements are not emulated here,
        // so a placeholder in LIMIT would be sent as a string and rejected.
        $limit = max(1, min(50, $limit));

        return $this->select(
            'SELECT blogPost.id, blogPost.title, blogPost.slug, blogPost.category,
                    blogPost.status, blogPost.visibility, blogPost.word_count,
                    blogPost.published_at, blogPost.updated_at,
                    users.username, users.display_name,
                    users.avatar_path, users.google_avatar, users.avatar_source
               FROM blogPost
               JOIN users ON blogPost.user_id = users.id
              ORDER BY blogPost.updated_at DESC
              LIMIT ' . $limit
        );
    }

    /**
     * Newest accounts, for the overview's second column.
     *
     * @return list<array<string, mixed>>
     */
    public function newestMembers(int $limit = 6): array
    {
        $limit = max(1, min(50, $limit));

        return $this->select(
            'SELECT id, username, display_name, headline, faculty, study_year, created_at,
                    avatar_path, google_avatar, avatar_source
               FROM users
              ORDER BY created_at DESC
              LIMIT ' . $limit
        );
    }

    /**
     * Article counts and averages in one row. The averages ignore drafts, since
     * an unfinished piece would drag them down without meaning anything.
     *
     * @return array<string, int|float>
     */
    public function contentBreakdown(): array
    {
        $row = $this->selectOne(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN status = 'published' AND visibility = 'public'
                             THEN 1 ELSE 0 END) AS live,
                    SUM(CASE WHEN status = 'published' AND visibility = 'unlisted'
                             THEN 1 ELSE 0 END) AS unlisted,
                    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) AS drafts,
                    COALESCE(ROUND(AVG(CASE WHEN status = 'published'
                             THEN word_count END)), 0) AS avg_words,
                    COALESCE(MAX(CASE WHEN status = 'published'
                             THEN word_count END), 0) AS longest,
                    COALESCE(SUM(CASE WHEN content_format = 'text' THEN 1 ELSE 0 END), 0) AS plain
               FROM blogPost"
        ) ?? [];

        $out = array_map(static fn ($value): int => (int) $value, $row);

        // Kept as a float, because 4.5 minutes is a more honest answer than 5
        $minutes = $this->selectOne(
            "SELECT COALESCE(ROUND(AVG(reading_minutes), 1), 0) AS avg_minutes
               FROM blogPost WHERE status = 'published'"
        ) ?? [];

        $out['avg_minutes'] = (float) ($minutes['avg_minutes'] ?? 0);

        return $out;
    }

    /**
     * How many published articles are missing each optional field. Every one of
     * these is allowed to be empty, so this is a list of things worth improving
     * rather than a list of faults.
     *
     * @return array<string, int>
     */
    public function missingMetadata(): array
    {
        $row = $this->selectOne(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN description IS NULL OR description = ''
                             THEN 1 ELSE 0 END) AS no_description,
                    SUM(CASE WHEN image_url IS NULL OR image_url = ''
                             THEN 1 ELSE 0 END) AS no_cover,
                    SUM(CASE WHEN slug IS NULL OR slug = ''
                             THEN 1 ELSE 0 END) AS no_slug,
                    SUM(CASE WHEN subtitle IS NULL OR subtitle = ''
                             THEN 1 ELSE 0 END) AS no_subtitle,
                    SUM(CASE WHEN tags IS NULL OR tags = ''
                             THEN 1 ELSE 0 END) AS no_tags
               FROM blogPost
              WHERE status = 'published'"
        ) ?? [];

        return array_map(static fn ($value): int => (int) $value, $row);
    }

    /**
     * Drafts nobody has touched in a while. The interesting number on a quiet
     * site: work that was started and then stalled.
     *
     * @return list<array<string, mixed>>
     */
    public function staleDrafts(int $days = 30, int $limit = 10): array
    {
        $days  = max(1, min(3650, $days));
        $limit = max(1, min(50, $limit));

        return $this->select(
            'SELECT blogPost.id, blogPost.title, blogPost.category, blogPost.word_count,
                    blogPost.updated_at, users.username, users.display_name
               FROM blogPost
               JOIN users ON blogPost.user_id = users.id
              WHERE blogPost.status = \'draft\'
                AND blogPost.updated_at < DATE_SUB(NOW(), INTERVAL ' . $days . ' DAY)
              ORDER BY blogPost.updated_at ASC
              LIMIT ' . $limit
        );
    }

    /**
     * Live, draft and word totals for every configured topic, including the ones
     * nobody has written for yet — a gap is as worth seeing as a total.
     *
     * @return list<array{topic: string, live: int, drafts: int, words: int}>
     */
    public function byTopic(): array
    {
        $rows = [];

        foreach ($this->select(
            "SELECT category,
                    SUM(CASE WHEN status = 'published' AND visibility = 'public'
                             THEN 1 ELSE 0 END) AS live,
                    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) AS drafts,
                    COALESCE(SUM(CASE WHEN status = 'published'
                             THEN word_count ELSE 0 END), 0) AS words
               FROM blogPost
              GROUP BY category"
        ) as $row) {
            $rows[(string) $row['category']] = $row;
        }

        $out = [];

        foreach (Post::categories() as $topic) {
            $row = $rows[$topic] ?? [];

            $out[] = [
                'topic'  => $topic,
                'live'   => (int) ($row['live'] ?? 0),
                'drafts' => (int) ($row['drafts'] ?? 0),
                'words'  => (int) ($row['words'] ?? 0),
            ];
        }

        usort($out, static fn (array $a, array $b): int => $b['live'] <=> $a['live']);

        return $out;
    }

    /**
     * Every account with its writing record. A LEFT JOIN, so members who have
     * not written anything appear with zeros instead of vanishing — on a portal
     * this new, who signed up and stayed quiet is the more useful figure.
     *
     * @return list<array<string, mixed>>
     */
    public function writers(): array
    {
        return $this->select(
            "SELECT users.id, users.username, users.display_name, users.headline,
                    users.faculty, users.programme, users.study_year, users.created_at,
                    users.avatar_path, users.google_avatar, users.avatar_source,
                    COUNT(blogPost.id) AS articles,
                    SUM(CASE WHEN blogPost.status = 'published' THEN 1 ELSE 0 END) AS published,
                    SUM(CASE WHEN blogPost.status = 'draft' THEN 1 ELSE 0 END) AS drafts,
                    COALESCE(SUM(CASE WHEN blogPost.status = 'published'
                             THEN blogPost.word_count ELSE 0 END), 0) AS words,
                    MAX(blogPost.published_at) AS last_published
               FROM users
               LEFT JOIN blogPost ON blogPost.user_id = users.id
              GROUP BY users.id
              ORDER BY published DESC, words DESC, users.username ASC"
        );
    }

    /**
     * Where the Radar entries come from, and how well received they were.
     *
     * @return list<array<string, mixed>>
     */
    public function radarSources(): array
    {
        return $this->select(
            'SELECT source, COUNT(*) AS total,
                    COALESCE(SUM(reactions), 0) AS reactions
               FROM radar_posts
              GROUP BY source
              ORDER BY total DESC'
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function radarAuthors(int $limit = 10): array
    {
        $limit = max(1, min(50, $limit));

        return $this->select(
            'SELECT author, author_url, COUNT(*) AS total,
                    COALESCE(SUM(reactions), 0) AS reactions
               FROM radar_posts
              GROUP BY author, author_url
              ORDER BY reactions DESC, total DESC
              LIMIT ' . $limit
        );
    }

    /**
     * The shape of the collected set: how old it is, how fresh the import is,
     * and how it compares on length and reception.
     *
     * @return array<string, mixed>
     */
    public function radarSpan(): array
    {
        $row = $this->selectOne(
            'SELECT MIN(published_at) AS oldest,
                    MAX(published_at) AS newest,
                    MAX(fetched_at) AS fetched,
                    COALESCE(ROUND(AVG(reactions)), 0) AS avg_reactions,
                    COALESCE(MAX(reactions), 0) AS top_reactions,
                    COALESCE(ROUND(AVG(reading_minutes), 1), 0) AS avg_minutes
               FROM radar_posts'
        ) ?? [];

        return [
            'oldest'        => $row['oldest'] ?? null,
            'newest'        => $row['newest'] ?? null,
            'fetched'       => $row['fetched'] ?? null,
            'avg_reactions' => (int) ($row['avg_reactions'] ?? 0),
            'top_reactions' => (int) ($row['top_reactions'] ?? 0),
            'avg_minutes'   => (float) ($row['avg_minutes'] ?? 0),
        ];
    }

    /**
     * Faculty spread. Blank faculties are grouped rather than dropped, because
     * how many people skipped the field is itself worth knowing.
     *
     * @return list<array{label: string, total: int}>
     */
    public function byFaculty(): array
    {
        return $this->labelled('faculty');
    }

    /**
     * @return list<array{label: string, total: int}>
     */
    public function byStudyYear(): array
    {
        return $this->labelled('study_year');
    }

    /**
     * How people sign in. The three groups are exclusive, so they add up to the
     * member count.
     *
     * @return array<string, int>
     */
    public function signInMethods(): array
    {
        $row = $this->selectOne(
            'SELECT COUNT(*) AS total,
                    SUM(CASE WHEN google_id IS NOT NULL AND password IS NOT NULL
                             THEN 1 ELSE 0 END) AS both_ways,
                    SUM(CASE WHEN google_id IS NOT NULL AND password IS NULL
                             THEN 1 ELSE 0 END) AS google_only,
                    SUM(CASE WHEN google_id IS NULL AND password IS NOT NULL
                             THEN 1 ELSE 0 END) AS password_only
               FROM users'
        ) ?? [];

        return array_map(static fn ($value): int => (int) $value, $row);
    }

    /**
     * How much of the optional profile people actually fill in. Useful for
     * deciding which fields are worth keeping.
     *
     * @return array<string, int>
     */
    public function profileCompleteness(): array
    {
        $row = $this->selectOne(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN display_name IS NOT NULL AND display_name <> ''
                             THEN 1 ELSE 0 END) AS display_name,
                    SUM(CASE WHEN headline IS NOT NULL AND headline <> ''
                             THEN 1 ELSE 0 END) AS headline,
                    SUM(CASE WHEN bio IS NOT NULL AND bio <> ''
                             THEN 1 ELSE 0 END) AS bio,
                    SUM(CASE WHEN avatar_source <> 'initials'
                             THEN 1 ELSE 0 END) AS avatar,
                    SUM(CASE WHEN faculty IS NOT NULL AND faculty <> ''
                             THEN 1 ELSE 0 END) AS faculty,
                    SUM(CASE WHEN interests IS NOT NULL AND interests <> ''
                             THEN 1 ELSE 0 END) AS interests,
                    SUM(CASE WHEN (website IS NOT NULL AND website <> '')
                              OR (github IS NOT NULL AND github <> '')
                              OR (linkedin IS NOT NULL AND linkedin <> '')
                             THEN 1 ELSE 0 END) AS links
               FROM users"
        ) ?? [];

        return array_map(static fn ($value): int => (int) $value, $row);
    }

    /**
     * Which topics members say they write about. Counted in PHP because the
     * column holds a comma-separated list; splitting it in SQL would need a
     * join table that this short fixed vocabulary does not justify.
     *
     * @return list<array{topic: string, total: int}>
     */
    public function statedInterests(): array
    {
        $counts = array_fill_keys(Post::categories(), 0);

        foreach ($this->select("SELECT interests FROM users WHERE interests <> ''") as $row) {
            foreach (explode(',', (string) $row['interests']) as $interest) {
                $interest = trim($interest);

                if ($interest !== '' && array_key_exists($interest, $counts)) {
                    $counts[$interest]++;
                }
            }
        }

        arsort($counts);

        $out = [];

        foreach ($counts as $topic => $total) {
            $out[] = ['topic' => $topic, 'total' => $total];
        }

        return $out;
    }

    /**
     * Groups members by one profile column, newest label first by size.
     *
     * The column name is interpolated, which is only safe because every caller
     * passes a literal from this class — it is never reachable from a request.
     *
     * @return list<array{label: string, total: int}>
     */
    private function labelled(string $column): array
    {
        $rows = $this->select(
            "SELECT COALESCE(NULLIF(TRIM({$column}), ''), 'Not given') AS label,
                    COUNT(*) AS total
               FROM users
              GROUP BY label
              ORDER BY total DESC, label ASC"
        );

        return array_map(
            static fn (array $row): array => [
                'label' => (string) $row['label'],
                'total' => (int) $row['total'],
            ],
            $rows
        );
    }

    /**
     * The first day of the earliest month a chart covers.
     */
    private function windowStart(): string
    {
        return date('Y-m-01 00:00:00', (int) strtotime('-' . (self::MONTHS - 1) . ' months'));
    }

    /**
     * Turns sparse month rows into a complete run of months, so a chart shows
     * quiet periods as gaps rather than skipping over them.
     *
     * @param  list<array<string, mixed>> $rows
     * @return array<string, int>
     */
    private function fillMonths(array $rows): array
    {
        $found = [];

        foreach ($rows as $row) {
            $found[(string) $row['ym']] = (int) $row['total'];
        }

        $series = [];

        for ($back = self::MONTHS - 1; $back >= 0; $back--) {
            $month = date('Y-m', (int) strtotime('first day of -' . $back . ' months'));

            $series[$month] = $found[$month] ?? 0;
        }

        return $series;
    }
}
