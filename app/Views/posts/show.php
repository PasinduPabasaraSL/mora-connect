<?php

use App\Core\Csrf;

/** @var array<string, mixed> $post */
/** @var bool $isOwner */
?>
<article class="reading-column">
    <span class="category-chip"><?= e($post['category']) ?></span>
    <h1><?= e($post['title']) ?></h1>

    <div class="article-meta ui-metadata article-byline">
        <span>By <?= e($post['username']) ?></span>
        <span>&bull;</span>
        <span><?= e(format_date($post['created_at'])) ?></span>
        <span>&bull;</span>
        <span><?= e(reading_time((string) $post['content'])) ?></span>
    </div>

    <?php // pre-wrap in .article-body preserves the author's line breaks, so no nl2br ?>
    <div class="article-body"><?= e($post['content']) ?></div>

    <?php if ($isOwner): ?>
        <div class="owner-actions">
            <a href="<?= e(url('posts/' . (int) $post['id'] . '/edit')) ?>" class="btn btn-secondary">Edit</a>
            <form method="POST"
                  action="<?= e(url('posts/' . (int) $post['id'] . '/delete')) ?>"
                  onsubmit="return confirm('Delete this article? This cannot be undone.');">
                <?= Csrf::field() ?>
                <button type="submit" class="btn btn-danger">Delete</button>
            </form>
        </div>
    <?php endif; ?>
</article>
