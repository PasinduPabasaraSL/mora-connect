<?php

use App\Core\Auth;

/** @var array<int, array<string, mixed>> $posts */

$count = count($posts);
?>
<div class="reading-column">
    <h2><?= e(Auth::username()) ?></h2>
    <p class="ui-metadata"><?= $count ?> article<?= $count === 1 ? '' : 's' ?></p>

    <?php if ($posts === []): ?>
        <p>You have not written anything yet. <a href="<?= e(url('posts/create')) ?>">Start your first post</a>.</p>
    <?php else: ?>
        <div class="profile-posts">
            <?php foreach ($posts as $post): ?>
                <div class="profile-post">
                    <div>
                        <span class="category-chip"><?= e($post['category']) ?></span>
                        <h3><a href="<?= e(url('posts/' . (int) $post['id'])) ?>"><?= e($post['title']) ?></a></h3>
                        <div class="article-meta ui-metadata">
                            <span><?= e(format_date($post['created_at'])) ?></span>
                            <span>&bull;</span>
                            <span><?= e(reading_time((string) $post['content'])) ?></span>
                        </div>
                    </div>
                    <a href="<?= e(url('posts/' . (int) $post['id'] . '/edit')) ?>" class="btn btn-secondary">Edit</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
