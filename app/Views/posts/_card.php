<?php

use App\Models\Post;

/** @var array<string, mixed> $post */

$link = url('posts/' . (int) $post['id']);
?>
<article class="article-card">
    <div class="card-thumb" style="background: <?= e(Post::gradientFor($post['category'])) ?>;">
        <span><?= e($post['category']) ?></span>
    </div>
    <span class="category-chip"><?= e($post['category']) ?></span>
    <h3><a href="<?= e($link) ?>"><?= e($post['title']) ?></a></h3>
    <p class="excerpt"><?= e(excerpt((string) $post['content'])) ?></p>
    <div class="article-meta ui-metadata">
        <?php if (isset($post['username'])): ?>
            <span><?= e($post['username']) ?></span>
            <span>&bull;</span>
        <?php endif; ?>
        <span><?= e(format_date($post['created_at'] ?? null)) ?></span>
        <span>&bull;</span>
        <span><?= e(reading_time((string) $post['content'])) ?></span>
    </div>
</article>
