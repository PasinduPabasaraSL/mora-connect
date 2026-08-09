<?php

use App\Models\Post;

/**
 * Clickable topic pills with article counts.
 *
 * @var array<string, int> $counts
 * @var string|null        $active currently viewed topic, highlighted
 */

$active = $active ?? null;
?>
<div class="topic-strip">
    <?php foreach ($counts as $topic => $total): ?>
        <a class="topic<?= $topic === $active ? ' is-active' : '' ?>"
           style="--badge-bg: <?= e(Post::colorsFor($topic)['bg']) ?>;"
           href="<?= e(url('topics/' . Post::slugFor($topic))) ?>">
            <span class="dot"></span>
            <span><?= e($topic) ?></span>
            <span class="count"><?= (int) $total ?></span>
        </a>
    <?php endforeach; ?>
</div>
