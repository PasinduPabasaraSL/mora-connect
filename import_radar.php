<?php

declare(strict_types=1);

/**
 * Fills the Radar tab with technical articles from dev.to.
 *
 * Uses the public dev.to API (no key required) rather than scraping HTML, and
 * stores only what is needed to credit and link back to each article: title,
 * the author's own summary, cover image, author and canonical URL. The article
 * text is never copied.
 *
 * Run from a terminal:
 *     php import_radar.php
 *
 * Safe to run repeatedly: each run replaces the stored selection with a fresh
 * one, so the table never accumulates duplicates or stale picks.
 */

require __DIR__ . '/app/bootstrap.php';

use App\Core\Database;

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This importer is a command line tool. Run: php import_radar.php\n");
}

/** Total articles wanted across all topics. */
const TARGET_TOTAL = 50;

/** Ceiling per topic, so one popular tag cannot fill the whole page. */
const PER_CATEGORY = 7;

/**
 * dev.to tags mapped onto the topics this site already uses, so Radar entries
 * carry the same colour coding as local articles.
 */
const TAG_MAP = [
    'Web Development'  => ['webdev', 'php', 'javascript'],
    'DevOps'           => ['devops', 'docker', 'kubernetes'],
    'Machine Learning' => ['machinelearning', 'ai'],
    'Databases'        => ['database', 'sql', 'postgres'],
    'Security'         => ['security', 'cybersecurity'],
    'Systems'          => ['linux', 'rust'],
    'Mobile'           => ['flutter', 'android'],
    'Other'            => ['programming', 'testing'],
];

/**
 * Requests one page of articles for a tag. `top=365` asks for the best received
 * articles of the past year, which are far more likely to carry a cover image
 * and a written summary than the newest ones.
 */
function fetchTag(string $tag, int $perPage = 12): array
{
    $url = 'https://dev.to/api/articles?' . http_build_query([
        'tag'      => $tag,
        'top'      => 365,
        'per_page' => $perPage,
    ]);

    $handle = curl_init($url);
    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT      => 'MoraConnect/1.0 (student project; +http://localhost/Blog)',
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    ]);

    $body   = curl_exec($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
    $error  = curl_error($handle);
    curl_close($handle);

    if ($body === false || $status !== 200) {
        fwrite(STDERR, sprintf("  ! %s failed (HTTP %d) %s\n", $tag, $status, $error));

        return [];
    }

    $decoded = json_decode((string) $body, true);

    return is_array($decoded) ? $decoded : [];
}

/**
 * Keeps only articles usable on a card: a cover image, a summary and a link.
 */
function isUsable(array $article): bool
{
    return trim((string) ($article['cover_image'] ?? '')) !== ''
        && trim((string) ($article['description'] ?? '')) !== ''
        && trim((string) ($article['url'] ?? '')) !== '';
}

function tidySummary(string $summary, int $limit = 300): string
{
    $clean = trim(preg_replace('/\s+/', ' ', strip_tags($summary)) ?? '');

    if (mb_strlen($clean) <= $limit) {
        return $clean;
    }

    return rtrim(mb_substr($clean, 0, $limit), ' .,;:!?-') . '...';
}

echo "Fetching articles from the dev.to API\n\n";

$byCategory = [];
$seenUrls   = [];

foreach (TAG_MAP as $category => $tags) {
    $kept = 0;

    foreach ($tags as $tag) {
        if ($kept >= PER_CATEGORY) {
            break;
        }

        foreach (fetchTag($tag) as $article) {
            if ($kept >= PER_CATEGORY) {
                break;
            }

            $url = trim((string) ($article['url'] ?? ''));

            if (!isUsable($article) || isset($seenUrls[$url])) {
                continue;
            }

            $seenUrls[$url] = true;
            $published = strtotime((string) ($article['published_at'] ?? '')) ?: null;

            $byCategory[$category][] = [
                'title'           => mb_substr(trim((string) $article['title']), 0, 300),
                'summary'         => tidySummary((string) $article['description']),
                'url'             => mb_substr($url, 0, 500),
                'url_hash'        => sha1($url),
                'image_url'       => mb_substr(trim((string) $article['cover_image']), 0, 500),
                'author'          => mb_substr(trim((string) ($article['user']['name'] ?? 'Unknown')), 0, 150),
                'author_url'      => isset($article['user']['username'])
                    ? 'https://dev.to/' . $article['user']['username']
                    : null,
                'source'          => 'dev.to',
                'category'        => $category,
                'tags'            => mb_substr(implode(', ', array_slice((array) ($article['tag_list'] ?? []), 0, 4)), 0, 255),
                'reading_minutes' => (int) ($article['reading_time_minutes'] ?? 0),
                'reactions'       => (int) ($article['public_reactions_count'] ?? 0),
                'published_at'    => $published === null ? null : date('Y-m-d H:i:s', $published),
            ];

            $kept++;
        }
    }

    printf("  %-18s %d article%s\n", $category, $kept, $kept === 1 ? '' : 's');
}

if ($byCategory === []) {
    exit("\nNothing was collected. Check your internet connection and try again.\n");
}

// Best received first within each topic...
foreach ($byCategory as &$items) {
    usort($items, static fn (array $a, array $b): int => $b['reactions'] <=> $a['reactions']);
}
unset($items);

// ...then take one topic at a time until the target is reached. Sorting the
// whole pool by reactions instead would let two popular tags crowd out every
// other topic, which defeats the point of the tag map.
$collected = [];

for ($round = 0; count($collected) < TARGET_TOTAL; $round++) {
    $addedThisRound = false;

    foreach ($byCategory as $items) {
        if (!isset($items[$round])) {
            continue;
        }

        $collected[] = $items[$round];
        $addedThisRound = true;

        if (count($collected) >= TARGET_TOTAL) {
            break;
        }
    }

    if (!$addedThisRound) {
        break;
    }
}

$db = Database::connection();

// The table is a curated snapshot, so a re-run replaces it rather than piling
// new articles on top of an older selection.
$db->beginTransaction();
$db->exec('DELETE FROM radar_posts');

$statement = $db->prepare(
    'INSERT INTO radar_posts
        (title, summary, url, url_hash, image_url, author, author_url, source,
         category, tags, reading_minutes, reactions, published_at)
     VALUES
        (:title, :summary, :url, :url_hash, :image_url, :author, :author_url, :source,
         :category, :tags, :reading_minutes, :reactions, :published_at)
     ON DUPLICATE KEY UPDATE
        title = VALUES(title),
        summary = VALUES(summary),
        image_url = VALUES(image_url),
        reactions = VALUES(reactions),
        fetched_at = CURRENT_TIMESTAMP'
);

foreach ($collected as $row) {
    $statement->execute($row);
}

$db->commit();

printf("\nStored %d articles. Open /Blog/radar to see them.\n", count($collected));
