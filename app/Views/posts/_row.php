<?php

use App\Core\View;

/**
 * Compact numbered article row. Used for the author's own articles.
 *
 * @var array<string, mixed> $post
 * @var int                  $index    1-based position
 * @var bool                 $editable show an Edit action
 */

$editable = $editable ?? false;
?>
<div class="row">
    <span class="row-index"><?= (int) $index ?></span>

    <div class="row-body">
        <h3><a href="<?= e(url('posts/' . (int) $post['id'])) ?>"><?= e($post['title']) ?></a></h3>
        <div class="meta">
            <?php if (isset($post['username'])): ?><?= e($post['username']) ?> &middot; <?php endif; ?>
            <?= e(format_date($post['created_at'] ?? null)) ?> &middot; <?= e(reading_time((string) $post['content'])) ?>
        </div>
    </div>

    <div class="row-actions">
        <?php View::partial('posts/_badge', ['category' => (string) $post['category']]); ?>
        <?php if ($editable): ?>
            <a href="<?= e(url('posts/' . (int) $post['id'] . '/edit')) ?>" class="btn btn-sm">Edit</a>
        <?php endif; ?>
    </div>
</div>
