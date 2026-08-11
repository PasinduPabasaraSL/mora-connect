<?php

use App\Core\Auth;
use App\Models\Post;

/**
 * Homepage sidebar.
 *
 * @var array<string, int>               $counts
 * @var array<int, array<string, mixed>> $recent
 */
?>
<aside>
    <?php if (!Auth::check()): ?>
        <div class="panel panel-cta">
            <h3>Publish your work</h3>
            <p>Share your build logs, debugging notes and project write-ups where other students will find them.</p>
            <a href="<?= e(url('register')) ?>" class="btn btn-primary btn-block">Create an account</a>
        </div>
    <?php endif; ?>

    <div class="panel">
        <h3>Browse topics</h3>
        <ul class="panel-list">
            <?php foreach ($counts as $topic => $total): ?>
                <li>
                    <a href="<?= e(url('topics/' . Post::slugFor($topic))) ?>">
                        <span><?= e($topic) ?></span>
                        <span class="count"><?= (int) $total ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <?php if ($recent !== []): ?>
        <div class="panel">
            <h3>Recently published</h3>
            <ul class="panel-list">
                <?php foreach ($recent as $post): ?>
                    <li>
                        <a class="stack" href="<?= e(url('posts/' . (int) $post['id'])) ?>">
                            <?= e($post['title']) ?>
                            <span><?= e(post_date($post)) ?> &middot; <?= e(post_minutes($post)) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</aside>
