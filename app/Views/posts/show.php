<?php

use App\Core\Csrf;
use App\Core\View;
use App\Models\Post;

/**
 * @var array<string, mixed>             $post
 * @var bool                             $isOwner
 * @var array<int, array<string, mixed>> $related
 */

$image = trim((string) ($post['image_url'] ?? ''));
?>
<article class="reading">
    <?php if ($image !== ''): ?>
        <div class="article-hero">
            <img src="<?= e($image) ?>" alt="">
        </div>
    <?php endif; ?>

    <div class="article-head">
        <?php View::partial('posts/_badge', ['category' => (string) $post['category']]); ?>
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

<?php if ($related !== []): ?>
    <section style="margin-top: var(--s6);">
        <div class="section-head">
            <h2>More in <?= e($post['category']) ?></h2>
            <a class="link" href="<?= e(url('topics/' . Post::slugFor((string) $post['category']))) ?>">View all &rarr;</a>
        </div>
        <div class="grid">
            <?php foreach ($related as $item): ?>
                <?php View::partial('posts/_card', ['post' => $item]); ?>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
