<?php

use App\Core\View;
use App\Models\Post;

/**
 * @var array<int, array<string, mixed>> $posts
 * @var array<string, int>              $counts
 * @var array{entries:int, authors:int, sources:int, updated:?string} $stats
 * @var string|null                     $category active topic filter
 */
?>
<section class="hero" style="padding: var(--s5) 0 var(--s4);">
    <span class="eyebrow">Radar</span>
    <h1>What the wider industry is writing.</h1>
    <p class="lead">
        A curated set of technical articles published elsewhere, collected so you
        have something worth reading when you are not writing. Every card links
        straight to the original - nothing is republished here.
    </p>

    <div class="stats">
        <div class="stat">
            <div class="num"><?= (int) $stats['entries'] ?></div>
            <div class="label">Articles tracked</div>
        </div>
        <div class="stat">
            <div class="num"><?= (int) $stats['authors'] ?></div>
            <div class="label">Authors</div>
        </div>
        <div class="stat">
            <div class="num"><?= count(array_filter($counts)) ?></div>
            <div class="label">Topics</div>
        </div>
        <?php if (!empty($stats['updated'])): ?>
            <div class="stat">
                <div class="num" style="font-size: 17px; padding-top: 5px;"><?= e(format_date((string) $stats['updated'])) ?></div>
                <div class="label">Last updated</div>
            </div>
        <?php endif; ?>
    </div>
</section>

<section style="margin-bottom: var(--s5);">
    <div class="section-head">
        <h2>Filter by topic</h2>
        <?php if ($category !== null): ?>
            <a class="link" href="<?= e(url('radar')) ?>">Show all</a>
        <?php endif; ?>
    </div>

    <div class="topic-strip">
        <a class="topic<?= $category === null ? ' is-active' : '' ?>" href="<?= e(url('radar')) ?>">
            <span>All</span>
            <span class="count"><?= (int) $stats['entries'] ?></span>
        </a>
        <?php foreach ($counts as $topic => $total): ?>
            <?php if ($total === 0) { continue; } ?>
            <a class="topic<?= $topic === $category ? ' is-active' : '' ?>"
               style="--badge-bg: <?= e(Post::colorsFor($topic)['bg']) ?>;"
               href="<?= e(url('radar') . '?topic=' . Post::slugFor($topic)) ?>">
                <span class="dot"></span>
                <span><?= e($topic) ?></span>
                <span class="count"><?= (int) $total ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<?php if ($posts === []): ?>
    <?php View::partial('partials/_empty', [
        'message'    => 'Nothing has been collected yet. Run "php import_radar.php" from the project folder to fill this page.',
        'actionUrl'  => url(),
        'actionText' => 'Back to the homepage',
    ]); ?>
<?php else: ?>
    <div class="grid">
        <?php foreach ($posts as $post): ?>
            <?php View::partial('radar/_card', ['post' => $post]); ?>
        <?php endforeach; ?>
    </div>

    <p class="attribution">
        Titles, summaries and cover images belong to their original authors and are
        shown here for reference only. Links open the original article on its own
        site. Collected through the public dev.to API.
    </p>
<?php endif; ?>
