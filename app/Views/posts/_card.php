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
        <p class="excerpt"><?= e(post_summary($post, 110)) ?></p>

        <div class="card-meta">
            <?php if (isset($post['username'])): ?>
                <span><?= e($post['username']) ?></span>
                <span class="sep">&middot;</span>
            <?php endif; ?>
            <span><?= e(post_date($post)) ?></span>
            <span class="sep">&middot;</span>
            <span><?= e(post_minutes($post)) ?></span>
        </div>
    </div>
</article>
