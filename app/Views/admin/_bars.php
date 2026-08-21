<?php

/**
 * A ranked list with a proportional bar behind each row. Used wherever the
 * question is "how do these compare", which a table of numbers answers slowly.
 *
 * @var list<array{label: string, value: int, note?: string}> $rows
 * @var string $empty message shown when there is nothing to rank
 */

$rows  = $rows ?? [];
$empty = $empty ?? 'Nothing to show yet.';

// The largest row sets the scale, so the longest bar always fills the track
$peak = 0;

foreach ($rows as $row) {
    $peak = max($peak, (int) $row['value']);
}
?>
<?php if ($rows === []): ?>
    <p class="admin-muted"><?= e($empty) ?></p>
<?php else: ?>
    <ul class="admin-bars">
        <?php foreach ($rows as $row): ?>
            <?php $share = $peak === 0 ? 0 : ((int) $row['value'] / $peak) * 100; ?>
            <li>
                <div class="admin-bar-head">
                    <span class="admin-bar-label"><?= e($row['label']) ?></span>
                    <span class="admin-bar-value">
                        <?= (int) $row['value'] ?>
                        <?php if (($row['note'] ?? '') !== ''): ?>
                            <em><?= e($row['note']) ?></em>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="admin-bar-track">
                    <span class="admin-bar-fill" style="width: <?= round($share, 1) ?>%"></span>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
