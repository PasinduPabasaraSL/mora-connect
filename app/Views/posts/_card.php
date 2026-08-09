<?php

use App\Core\View;

/**
 * Article card for grids.
 *
 * @var array<string, mixed> $post
 */
?>
<article class="card">
    <a href="<?= e(url('posts/' . (int) $post['id'])) ?>" aria-hidden="true" tabindex="-1">
        <?php View::partial('posts/_cover', ['post' => $post]); ?>
    </a>

    <div class="card-body">
        <h3><a href="<?= e(url('posts/' . (int) $post['id'])) ?>"><?= e($post['title']) ?></a></h3>
        <p class="excerpt"><?= e(excerpt((string) $post['content'], 110)) ?></p>

        <div class="card-meta">
            <?php if (isset($post['username'])): ?>
                <span><?= e($post['username']) ?></span>
                <span class="sep">&middot;</span>
            <?php endif; ?>
            <span><?= e(format_date($post['created_at'] ?? null)) ?></span>
            <span class="sep">&middot;</span>
            <span><?= e(reading_time((string) $post['content'])) ?></span>
        </div>
    </div>
</article>
