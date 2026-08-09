<?php

use App\Models\Post;

/**
 * Card for an article published elsewhere. The whole card links out to the
 * original, and the source is stated on the card so it is never mistaken for
 * a MoraConnect article.
 *
 * @var array<string, mixed> $post
 */

$colors = Post::colorsFor($post['category'] ?? null);
$image  = trim((string) ($post['image_url'] ?? ''));
?>
<article class="card radar-card">
    <a href="<?= e($post['url']) ?>" target="_blank" rel="noopener noreferrer nofollow"
       aria-label="<?= e($post['title']) ?> — opens on <?= e($post['source']) ?>">
        <div class="cover<?= $image === '' ? ' cover-fallback' : '' ?>"
             data-topic="<?= e($post['category']) ?>" style="--badge-bg: <?= e($colors['bg']) ?>;">
            <?php if ($image !== ''): ?>
                <img src="<?= e($image) ?>" alt="" loading="lazy" referrerpolicy="no-referrer">
            <?php else: ?>
                <span><?= e($post['category']) ?></span>
            <?php endif; ?>

            <span class="badge card-badge"
                  style="--badge-bg: <?= e($colors['bg']) ?>; --badge-ink: <?= e($colors['ink']) ?>;"><?= e($post['category']) ?></span>
        </div>
    </a>

    <div class="card-body">
        <span class="source-tag">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                <path d="M15 3h6v6M10 14L21 3"></path>
            </svg>
            <?= e($post['source']) ?>
        </span>

        <h3>
            <a href="<?= e($post['url']) ?>" target="_blank" rel="noopener noreferrer nofollow"><?= e($post['title']) ?></a>
        </h3>

        <p class="excerpt"><?= e($post['summary']) ?></p>

        <div class="card-meta">
            <span><?= e($post['author']) ?></span>
            <?php if (!empty($post['published_at'])): ?>
                <span class="sep">&middot;</span>
                <span><?= e(format_date((string) $post['published_at'])) ?></span>
            <?php endif; ?>
            <?php if ((int) $post['reading_minutes'] > 0): ?>
                <span class="sep">&middot;</span>
                <span><?= (int) $post['reading_minutes'] ?> min read</span>
            <?php endif; ?>
            <?php if ((int) $post['reactions'] > 0): ?>
                <span class="sep">&middot;</span>
                <span><?= (int) $post['reactions'] ?> reactions</span>
            <?php endif; ?>
        </div>
    </div>
</article>
