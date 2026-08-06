<?php

use App\Core\Auth;

/** @var array<int, array<string, mixed>> $posts */
?>
<section class="hero-section">
    <div class="hero-inner">
        <h1>Ideas that matter, published by the next generation.</h1>
        <p class="text-lead">MoraConnect is the publishing platform for University of Moratuwa students.</p>
        <div class="hero-actions">
            <a href="#latest" class="btn btn-primary">Start Reading</a>
            <?php if (!Auth::check()): ?>
                <a href="<?= e(url('register')) ?>" class="btn btn-secondary">Become a Writer</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<h3 id="latest" class="ui-label section-heading">Latest Publications</h3>

<?php if ($posts === []): ?>
    <p>
        No articles published yet.
        <?php if (Auth::check()): ?>
            <a href="<?= e(url('posts/create')) ?>">Write the first one</a>.
        <?php endif; ?>
    </p>
<?php else: ?>
    <div class="articles-grid">
        <?php foreach ($posts as $post): ?>
            <?php App\Core\View::partial('posts/_card', ['post' => $post]); ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
