<?php

use App\Core\View;

/**
 * @var string                           $term
 * @var array<int, array<string, mixed>> $posts
 */
?>
<section class="hero" style="padding: var(--s4) 0;">
    <span class="eyebrow">Search</span>
    <h1><?= $term === '' ? 'Search articles' : e($term) ?></h1>

    <form method="GET" action="<?= e(url('search')) ?>" class="search-page-form">
        <input type="search" name="q" class="form-control" value="<?= e($term) ?>"
               placeholder="Search titles and article text" aria-label="Search articles" autofocus>
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <?php if ($term !== ''): ?>
        <p class="meta"><?= count($posts) ?> result<?= count($posts) === 1 ? '' : 's' ?> for &ldquo;<?= e($term) ?>&rdquo;</p>
    <?php endif; ?>
</section>

<?php if ($term === ''): ?>
    <?php View::partial('partials/_empty', ['message' => 'Type something above to search every published article.']); ?>
<?php elseif ($posts === []): ?>
    <?php View::partial('partials/_empty', [
        'message'    => 'No articles matched that search. Try a broader term.',
        'actionUrl'  => url(),
        'actionText' => 'Browse all articles',
    ]); ?>
<?php else: ?>
    <div class="grid">
        <?php foreach ($posts as $post): ?>
            <?php View::partial('posts/_card', ['post' => $post]); ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
