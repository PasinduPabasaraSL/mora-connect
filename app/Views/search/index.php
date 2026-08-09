<?php

use App\Core\View;

/**
 * Doubles as the Explore listing: with no search term, $posts is every article.
 *
 * @var string                           $term
 * @var array<int, array<string, mixed>> $posts
 */

$searching = $term !== '';
?>
<section class="hero" style="padding: var(--s4) 0;">
    <span class="eyebrow"><?= $searching ? 'Search' : 'Explore' ?></span>
    <h1><?= $searching ? e($term) : 'Every article' ?></h1>

    <form method="GET" action="<?= e(url('search')) ?>" class="search-page-form">
        <input type="search" name="q" class="form-control" value="<?= e($term) ?>"
               placeholder="Search titles and article text" aria-label="Search articles">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <p class="meta">
        <?php if ($searching): ?>
            <?= count($posts) ?> result<?= count($posts) === 1 ? '' : 's' ?> for &ldquo;<?= e($term) ?>&rdquo;
            &middot; <a class="link" href="<?= e(url('search')) ?>">Clear search</a>
        <?php else: ?>
            <?= count($posts) ?> article<?= count($posts) === 1 ? '' : 's' ?> published
        <?php endif; ?>
    </p>
</section>

<?php if ($posts === []): ?>
    <?php View::partial('partials/_empty', [
        'message'    => $searching
            ? 'No articles matched that search. Try a broader term.'
            : 'No articles have been published yet.',
        'actionUrl'  => url(),
        'actionText' => 'Back to the homepage',
    ]); ?>
<?php else: ?>
    <div class="grid">
        <?php foreach ($posts as $post): ?>
            <?php View::partial('posts/_card', ['post' => $post]); ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
