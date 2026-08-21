<?php

/**
 * A row of headline figures.
 *
 * @var list<array{label: string, value: string|int, note?: string, tone?: string}> $tiles
 */

$tiles = $tiles ?? [];
?>
<div class="admin-tiles">
    <?php foreach ($tiles as $tile): ?>
        <div class="admin-tile<?= isset($tile['tone']) ? ' tone-' . e($tile['tone']) : '' ?>">
            <span class="admin-tile-value"><?= e((string) $tile['value']) ?></span>
            <span class="admin-tile-label"><?= e($tile['label']) ?></span>
            <?php if (($tile['note'] ?? '') !== ''): ?>
                <span class="admin-tile-note"><?= e($tile['note']) ?></span>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
