<?php

use App\Models\Post;

/**
 * Topic badge, linking to that topic's archive.
 *
 * @var string $category
 */

$colors = Post::colorsFor($category);
?>
<a class="badge"
   style="--badge-bg: <?= e($colors['bg']) ?>; --badge-ink: <?= e($colors['ink']) ?>;"
   href="<?= e(url('topics/' . Post::slugFor($category))) ?>"><?= e($category) ?></a>
