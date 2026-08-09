<?php

use App\Core\Auth;
use App\Core\View;

/** @var array<int, array<string, mixed>> $posts */

$count = count($posts);
$words = array_sum(array_map(static fn (array $p): int => str_word_count(strip_tags((string) $p['content'])), $posts));
?>
<section class="hero" style="padding-bottom: var(--s4);">
    <span class="eyebrow">Author</span>
    <h1><?= e(Auth::username()) ?></h1>

    <div class="stats">
        <div class="stat">
            <div class="num"><?= $count ?></div>
            <div class="label">Articles</div>
        </div>
        <div class="stat">
            <div class="num"><?= number_format($words) ?></div>
            <div class="label">Words written</div>
        </div>
        <div class="stat">
            <div class="num"><?= count(array_unique(array_column($posts, 'category'))) ?></div>
            <div class="label">Topics</div>
        </div>
    </div>
</section>

<div class="section-head">
    <h2>Your articles</h2>
    <a href="<?= e(url('posts/create')) ?>" class="btn btn-primary btn-sm">New article</a>
</div>

<?php if ($posts === []): ?>
    <?php View::partial('partials/_empty', [
        'message'    => 'You have not published anything yet.',
        'actionUrl'  => url('posts/create'),
        'actionText' => 'Write your first article',
    ]); ?>
<?php else: ?>
    <div class="row-list">
        <?php foreach ($posts as $i => $post): ?>
            <?php View::partial('posts/_row', [
                'post'     => $post,
                'index'    => $i + 1,
                'editable' => true,
            ]); ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
