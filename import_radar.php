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
 * Or, on hosting with no shell access, from a browser:
 *     https://your-site/import_radar.php?key=RADAR_IMPORT_KEY
 *
 * The browser route needs RADAR_IMPORT_KEY and RADAR_IMPORT_USER set in .env,
 * and requires both the key in the URL and a signed-in session belonging to
 * that username. Leave either setting empty and the browser route is closed.
 *
 * Safe to run repeatedly: each run replaces the stored selection with a fresh
 * one, so the table never accumulates duplicates or stale picks.
 */

require __DIR__ . '/app/bootstrap.php';

use App\Core\Auth;
use App\Core\Config;
use App\Core\Database;

$viaCli = PHP_SAPI === 'cli';

/**
 * Progress, flushed as it happens so a browser run shows its work rather than
 * hanging on a blank page for the length of a dozen API calls.
 */
function report(string $line): void
{
    echo $line;

    if (PHP_SAPI !== 'cli') {
        flush();
    }
}

/**
 * A failed tag: worth saying, but not worth stopping for. Kept off stdout under
 * CLI so piping the output stays clean; STDERR does not exist over HTTP.
 */
function problem(string $line): void
{
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $line);

        return;
    }

    echo $line;
    flush();
}

if (!$viaCli) {
    header('Content-Type: text/plain; charset=utf-8');

    $key   = (string) Config::get('radar.import_key', '');
    $owner = (string) Config::get('radar.import_user', '');

    if ($key === '' || $owner === '') {
        http_response_code(404);
        exit("The browser importer is switched off. Set RADAR_IMPORT_KEY and RADAR_IMPORT_USER in .env to use it.\n");
    }

    $given = isset($_GET['key']) ? (string) $_GET['key'] : '';

    // Both guards, so a key that leaks out of a browser history or a server log
    // is not enough on its own, and neither is someone else's open session.
    // hash_equals compares in constant time, giving nothing away by how long a
    // wrong key takes to reject.
    if ($given === '' || !hash_equals($key, $given)) {
        http_response_code(403);
        exit("Not authorised.\n");
    }

    // Past the key, so whoever this is holds the operator secret. Being specific
    // now costs nothing and saves guessing which of the two guards refused.
    if (!Auth::check()) {
        http_response_code(403);
        exit("The key is correct, but nobody is signed in.\n\n"
            . "Sign in as \"" . $owner . "\" in this same browser, then reload this URL.\n");
    }

    if (Auth::username() !== $owner) {
        http_response_code(403);
        exit("The key is correct, but you are signed in as \"" . Auth::username() . "\".\n\n"
            . "The importer expects \"" . $owner . "\". Either sign in as that account, or set\n"
            . "RADAR_IMPORT_USER in .env to the username you actually use.\n");
    }

    // A dozen API calls take longer than a page view is normally allowed, and
    // the run should finish even if the tab is closed part way through.
    @set_time_limit(180);
    ignore_user_abort(true);
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
 *
 * Timeouts are deliberately short. Shared hosting caps how long a request may
 * run in total, so one slow tag must not eat the budget for the rest.
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
        CURLOPT_CONNECTTIMEOUT => 6,
        CURLOPT_TIMEOUT        => 12,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT      => 'MoraConnect/1.0 (student project; +https://moraconnect.dev)',
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    ]);

    $body   = curl_exec($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
    $error  = curl_error($handle);
    curl_close($handle);

    if ($body === false || $status !== 200) {
        problem(sprintf("  ! %s failed (HTTP %d) %s\n", $tag, $status, $error));

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

report("Fetching articles from the dev.to API\n\n");

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

    report(sprintf("  %-18s %d article%s\n", $category, $kept, $kept === 1 ? '' : 's'));
}

if ($byCategory === []) {
    // Nothing was written, so whatever the page showed before it still shows.
    if (!$viaCli) {
        http_response_code(502);
    }

    exit("\nNothing was collected, so the Radar table is untouched. dev.to could not be reached.\n");
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
// new articles on top of an older selection. Both steps share one transaction,
// so a run cut short by a timeout leaves the previous selection in place
// instead of emptying the page.
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

report(sprintf("\nStored %d articles. Open the Radar page to see them.\n", count($collected)));
