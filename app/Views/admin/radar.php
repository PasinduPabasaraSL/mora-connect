<?php

use App\Core\View;

/**
 * @var array<string, mixed>      $summary
 * @var array<string, int>        $byCategory
 * @var array<string, mixed>      $span
 * @var list<array<string,mixed>> $sources
 * @var list<array<string,mixed>> $authors
 */

$fetched = $summary['updated'];

// Staleness is the number worth watching here: the collection is a snapshot, and
// a snapshot nobody has refreshed slowly stops representing anything.
$ageDays = $fetched === null ? null : (int) floor((time() - (int) strtotime((string) $fetched)) / 86400);
$tone = $ageDays === null || $ageDays > 30 ? 'warn' : '';
?>
<?php View::partial('admin/_tiles', ['tiles' => [
    ['label' => 'Entries',        'value' => (int) $summary['entries']],
    ['label' => 'Authors',        'value' => (int) $summary['authors']],
    ['label' => 'Sources',        'value' => (int) $summary['sources']],
    ['label' => 'Last refreshed', 'value' => $ageDays === null ? 'Never' : ($ageDays === 0 ? 'Today' : $ageDays . 'd ago'), 'tone' => $tone],
    ['label' => 'Average praise', 'value' => (int) $span['avg_reactions'], 'note' => 'reactions per entry'],
    ['label' => 'Average read',   'value' => $span['avg_minutes'], 'note' => 'minutes'],
]]); ?>

<?php if ($ageDays === null): ?>
    <div class="admin-callout">
        <strong>Radar is empty.</strong>
        Nothing has been collected on this server yet. The importer fills it, either
        from a terminal with <code>php import_radar.php</code> or from a browser using
        the key in <code>.env</code>.
    </div>
<?php elseif ($ageDays > 30): ?>
    <div class="admin-callout">
        <strong>This collection is <?= (int) $ageDays ?> days old.</strong>
        Re-running the importer replaces it with a fresh selection.
    </div>
<?php endif; ?>

<div class="admin-grid admin-grid-2">
    <section class="admin-card">
        <header class="admin-card-head">
            <h2>Balance across topics</h2>
            <p>The importer caps each topic so one popular tag cannot fill the page</p>
        </header>

        <?php
        $rows = [];

        foreach ($byCategory as $topic => $total) {
            $rows[] = ['label' => $topic, 'value' => (int) $total];
        }

        usort($rows, static fn (array $a, array $b): int => $b['value'] <=> $a['value']);
        ?>

        <?php View::partial('admin/_bars', [
            'rows'  => $rows,
            'empty' => 'Nothing collected yet.',
        ]); ?>
    </section>

    <section class="admin-card">
        <header class="admin-card-head">
            <h2>Where it comes from</h2>
            <p>Entries and total reactions per source</p>
        </header>

        <?php if ($sources === []): ?>
            <p class="admin-muted">Nothing collected yet.</p>
        <?php else: ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th scope="col">Source</th>
                            <th scope="col" class="numeric">Entries</th>
                            <th scope="col" class="numeric">Reactions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sources as $source): ?>
                            <tr>
                                <td><span class="admin-strong"><?= e($source['source']) ?></span></td>
                                <td class="numeric"><?= (int) $source['total'] ?></td>
                                <td class="numeric"><?= number_format((int) $source['reactions']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="admin-note">
            Oldest article <?= e($span['oldest'] === null ? '—' : format_date($span['oldest'])) ?>,
            newest <?= e($span['newest'] === null ? '—' : format_date($span['newest'])) ?>.
            Best received entry has <?= number_format((int) $span['top_reactions']) ?> reactions.
        </div>
    </section>
</div>

<section class="admin-card">
    <header class="admin-card-head">
        <h2>Most appreciated authors</h2>
        <p>Ranked by total reactions across the entries collected</p>
    </header>

    <?php if ($authors === []): ?>
        <p class="admin-muted">Nothing collected yet.</p>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col">Author</th>
                        <th scope="col" class="numeric">Entries</th>
                        <th scope="col" class="numeric">Reactions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($authors as $author): ?>
                        <tr>
                            <td>
                                <?php if (($author['author_url'] ?? '') !== ''): ?>
                                    <?php /* Someone else's site, so the link is given no
                                             reason to trust this one. */ ?>
                                    <a class="admin-strong" href="<?= e($author['author_url']) ?>"
                                       target="_blank" rel="noopener noreferrer">
                                        <?= e($author['author']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="admin-strong"><?= e($author['author']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="numeric"><?= (int) $author['total'] ?></td>
                            <td class="numeric"><?= number_format((int) $author['reactions']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
