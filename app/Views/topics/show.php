<?php

use App\Core\Auth;
use App\Core\View;

/**
 * @var string                           $category
 * @var array<int, array<string, mixed>> $posts
 * @var array<string, int>               $counts
 */
?>
<section class="hero" style="padding-bottom: var(--s4);">
    <span class="eyebrow">Topic</span>
    <h1><?= e($category) ?></h1>
    <p class="lead">
        <?= count($posts) ?> article<?= count($posts) === 1 ? '' : 's' ?> published under this topic.
    </p>
</section>

<section style="margin-bottom: var(--s5);">
    <div class="section-head">
        <h2>All topics</h2>
    </div>
    <?php View::partial('partials/_topics', ['counts' => $counts, 'active' => $category]); ?>
</section>

<?php if ($posts === []): ?>
    <?php View::partial('partials/_empty', [
        'message'    => 'Nothing has been published under ' . $category . ' yet.',
        'actionUrl'  => Auth::check() ? url('posts/create') : url('register'),
        'actionText' => Auth::check() ? 'Write about it' : 'Create an account',
    ]); ?>
<?php else: ?>
    <div class="grid">
        <?php foreach ($posts as $post): ?>
            <?php View::partial('posts/_card', ['post' => $post]); ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
