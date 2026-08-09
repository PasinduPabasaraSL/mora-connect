<?php

use App\Models\Post;

/**
 * Article cover. Falls back to a block tinted with the topic colour when the
 * article has no image, so text-only articles still look intentional.
 *
 * @var array<string, mixed> $post
 * @var bool                 $showBadge overlay the topic badge on the image
 */

$showBadge = $showBadge ?? true;
$colors = Post::colorsFor($post['category'] ?? null);
$image = trim((string) ($post['image_url'] ?? ''));
?>
<div class="cover<?= $image === '' ? ' cover-fallback' : '' ?>"
     style="--badge-bg: <?= e($colors['bg']) ?>;">
    <?php if ($image !== ''): ?>
        <img src="<?= e($image) ?>" alt="" loading="lazy">
    <?php else: ?>
        <span><?= e($post['category']) ?></span>
    <?php endif; ?>

    <?php if ($showBadge && $image !== ''): ?>
        <span class="badge card-badge"
              style="--badge-bg: <?= e($colors['bg']) ?>; --badge-ink: <?= e($colors['ink']) ?>;"><?= e($post['category']) ?></span>
    <?php endif; ?>
</div>
