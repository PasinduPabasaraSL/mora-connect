<?php

/**
 * Page links for any paginated listing.
 *
 * @var int                   $page    current page, 1-based
 * @var int                   $pages   total number of pages
 * @var string                $baseUrl page target, e.g. url('radar')
 * @var array<string, string> $query   extra query parameters to keep (filters)
 */

$query = $query ?? [];

if ($pages < 2) {
    return;
}

$link = static function (int $number) use ($baseUrl, $query): string {
    return $baseUrl . '?' . http_build_query($query + ['page' => $number]);
};

/**
 * Always show the first and last page, plus the current page and its
 * neighbours. Gaps become a single ellipsis, so the row stays short no matter
 * how long the list grows.
 */
$numbers = [1, $pages];

for ($i = $page - 1; $i <= $page + 1; $i++) {
    if ($i > 1 && $i < $pages) {
        $numbers[] = $i;
    }
}

$numbers = array_values(array_unique($numbers));
sort($numbers);
?>
<nav class="pagination" aria-label="Pagination">
    <?php if ($page > 1): ?>
        <a class="page-btn" href="<?= e($link($page - 1)) ?>" rel="prev">&larr; Previous</a>
    <?php else: ?>
        <span class="page-btn is-disabled" aria-disabled="true">&larr; Previous</span>
    <?php endif; ?>

    <span class="page-numbers">
        <?php $previous = 0; ?>
        <?php foreach ($numbers as $number): ?>
            <?php if ($previous !== 0 && $number > $previous + 1): ?>
                <span class="page-gap" aria-hidden="true">&hellip;</span>
            <?php endif; ?>

            <?php if ($number === $page): ?>
                <span class="page-num is-current" aria-current="page"><?= $number ?></span>
            <?php else: ?>
                <a class="page-num" href="<?= e($link($number)) ?>" aria-label="Page <?= $number ?>"><?= $number ?></a>
            <?php endif; ?>

            <?php $previous = $number; ?>
        <?php endforeach; ?>
    </span>

    <?php if ($page < $pages): ?>
        <a class="page-btn" href="<?= e($link($page + 1)) ?>" rel="next">Next &rarr;</a>
    <?php else: ?>
        <span class="page-btn is-disabled" aria-disabled="true">Next &rarr;</span>
    <?php endif; ?>
</nav>
