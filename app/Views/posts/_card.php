<?php

use App\Core\View;
use App\Models\User;

/**
 * Article card for grids.
 *
 * @var array<string, mixed> $post
 */

// Author details ride along on listing queries, but not on every one, so the
// byline is only drawn when they are actually present.
$hasAuthor = isset($post['username']);
?>
<article class="card">
    <a href="<?= e(url('posts/' . (int) $post['id'])) ?>" aria-hidden="true" tabindex="-1">
        <?php View::partial('posts/_cover', ['post' => $post]); ?>
    </a>

    <div class="card-body">
        <h3><a href="<?= e(url('posts/' . (int) $post['id'])) ?>"><?= e($post['title']) ?></a></h3>
        <p class="excerpt"><?= e(post_summary($post, 110)) ?></p>

        <div class="card-meta">
            <?php if ($hasAuthor): ?>
                <a class="card-author" href="<?= e(url('authors/' . rawurlencode((string) $post['username']))) ?>">
                    <?php View::partial('partials/_avatar', ['user' => $post, 'size' => 'sm']); ?>
                    <span><?= e(User::nameFor($post)) ?></span>
                </a>
                <span class="sep">&middot;</span>
            <?php endif; ?>
            <span><?= e(post_date($post)) ?></span>
            <span class="sep">&middot;</span>
            <span><?= e(post_minutes($post)) ?></span>
        </div>
    </div>
</article>
