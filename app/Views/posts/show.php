<?php

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\Post;

/**
 * @var array<string, mixed>             $post
 * @var bool                             $isOwner
 * @var array<int, array<string, mixed>> $related
 * @var array<string, int>               $counts
 */

$image = trim((string) ($post['image_url'] ?? ''));
$category = (string) $post['category'];
?>
<div class="article-layout">
    <article>
        <?php if ($image !== ''): ?>
            <div class="article-hero">
                <img src="<?= e($image) ?>" alt="">
            </div>
        <?php endif; ?>

        <div class="article-head">
            <?php View::partial('posts/_badge', ['category' => $category]); ?>
            <h1><?= e($post['title']) ?></h1>

            <div class="byline">
                <span class="avatar"><?= e(mb_substr((string) $post['username'], 0, 2)) ?></span>
                <span class="author"><?= e($post['username']) ?></span>
                <span class="sep">&middot;</span>
                <span><?= e(format_date($post['created_at'])) ?></span>
                <span class="sep">&middot;</span>
                <span><?= e(reading_time((string) $post['content'])) ?></span>
            </div>
        </div>

        <div class="article-body"><?= e($post['content']) ?></div>

        <?php if ($isOwner): ?>
            <div class="owner-actions">
                <a href="<?= e(url('posts/' . (int) $post['id'] . '/edit')) ?>" class="btn">Edit article</a>
                <form method="POST"
                      action="<?= e(url('posts/' . (int) $post['id'] . '/delete')) ?>"
                      onsubmit="return confirm('Delete this article? This cannot be undone.');">
                    <?= Csrf::field() ?>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        <?php endif; ?>
    </article>

    <?php /* Sticky rail: uses the width a single column of text cannot. */ ?>
    <aside class="article-rail">
        <div class="panel">
            <h3>Written by</h3>
            <div class="byline" style="margin-bottom: var(--s2);">
                <span class="avatar"><?= e(mb_substr((string) $post['username'], 0, 2)) ?></span>
                <span class="author"><?= e($post['username']) ?></span>
            </div>
            <p>Published <?= e(format_date($post['created_at'])) ?> under
                <a class="link" href="<?= e(url('topics/' . Post::slugFor($category))) ?>"><?= e($category) ?></a>.</p>
        </div>

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

        <?php if (!Auth::check()): ?>
            <div class="panel panel-cta">
                <h3>Write for MoraConnect</h3>
                <p>Publish your own build logs and engineering notes.</p>
                <a href="<?= e(url('register')) ?>" class="btn btn-primary btn-block">Create an account</a>
            </div>
        <?php endif; ?>
    </aside>
</div>

<?php if ($related !== []): ?>
    <section style="margin-top: var(--s6);">
        <div class="section-head">
            <h2>More in <?= e($category) ?></h2>
            <a class="link" href="<?= e(url('topics/' . Post::slugFor($category))) ?>">View all &rarr;</a>
        </div>
        <div class="grid">
            <?php foreach ($related as $item): ?>
                <?php View::partial('posts/_card', ['post' => $item]); ?>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
