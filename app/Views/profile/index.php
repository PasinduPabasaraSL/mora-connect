<?php

use App\Core\Auth;
use App\Core\View;
use App\Models\Post;

/** @var array<int, array<string, mixed>> $posts */
$drafts = array_values(array_filter(
    $posts,
    static fn (array $p): bool => ($p['status'] ?? Post::STATUS_PUBLISHED) !== Post::STATUS_PUBLISHED
));

$live = array_values(array_filter(
    $posts,
    static fn (array $p): bool => ($p['status'] ?? Post::STATUS_PUBLISHED) === Post::STATUS_PUBLISHED
));

$words = array_sum(array_map(
    static fn (array $p): int => (int) ($p['word_count'] ?? 0),
    $posts
));
?>
<section class="hero" style="padding-bottom: var(--s4);">
    <span class="eyebrow">Author</span>
    <h1><?= e(Auth::username()) ?></h1>

    <div class="stats">
        <div class="stat">
            <div class="num"><?= count($live) ?></div>
            <div class="label">Published</div>
        </div>
        <div class="stat">
            <div class="num"><?= count($drafts) ?></div>
            <div class="label">Drafts</div>
        </div>
        <div class="stat">
            <div class="num"><?= number_format($words) ?></div>
            <div class="label">Words written</div>
        </div>
        <div class="stat">
            <div class="num"><?= count(array_unique(array_column($live, 'category'))) ?></div>
            <div class="label">Topics</div>
        </div>
    </div>
</section>

<?php if ($drafts !== []): ?>
    <div class="section-head">
        <h2>Drafts</h2>
        <span class="meta">Only you can see these</span>
    </div>

    <div class="row-list">
        <?php foreach ($drafts as $i => $post): ?>
            <?php View::partial('posts/_row', [
                'post'     => $post,
                'index'    => $i + 1,
                'editable' => true,
            ]); ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="section-head"<?= $drafts === [] ? '' : ' style="margin-top: var(--s6);"' ?>>
    <h2>Published</h2>
    <a href="<?= e(url('posts/create')) ?>" class="btn btn-primary btn-sm">New article</a>
</div>

<?php if ($live === []): ?>
    <?php View::partial('partials/_empty', [
        'message'    => $drafts === []
            ? 'You have not written anything yet.'
            : 'Nothing published yet. Open a draft above when it is ready to go out.',
        'actionUrl'  => url('posts/create'),
        'actionText' => $drafts === [] ? 'Write your first article' : 'Start something new',
    ]); ?>
<?php else: ?>
    <div class="row-list">
        <?php foreach ($live as $i => $post): ?>
            <?php View::partial('posts/_row', [
                'post'     => $post,
                'index'    => $i + 1,
                'editable' => true,
            ]); ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
