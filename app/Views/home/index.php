<?php

use App\Core\Auth;
use App\Core\View;
use App\Models\User;

/**
 * @var array<int, array<string, mixed>> $posts
 * @var array<string, int>               $counts
 * @var array{articles: int, writers: int, topics: int} $stats
 */

$lead = $posts[0] ?? null;
$rest = array_slice($posts, 1);
?>
<section class="hero hero-split">
    <div class="hero-copy">
        <span class="eyebrow">University of Moratuwa</span>
        <h1>Engineering notes, written by students.</h1>
        <p class="lead">
            Build logs, debugging stories and technical write-ups from the people
            actually doing the work - published openly so the next student does not
            have to start from scratch.
        </p>
        <div class="hero-actions">
            <a href="#articles" class="btn btn-primary">Read articles</a>
            <?php if (!Auth::check()): ?>
                <a href="<?= e(url('register')) ?>" class="btn">Start writing</a>
            <?php endif; ?>
        </div>
    </div>

    <?php View::partial('partials/_hero_diagram'); ?>

    <div class="stats">
        <div class="stat">
            <div class="num"><?= (int) $stats['articles'] ?></div>
            <div class="label">Articles published</div>
        </div>
        <div class="stat">
            <div class="num"><?= (int) $stats['writers'] ?></div>
            <div class="label">Student writers</div>
        </div>
        <div class="stat">
            <div class="num"><?= (int) $stats['topics'] ?></div>
            <div class="label">Topics covered</div>
        </div>
        <?php /* Clickable, since the count is only useful if you can go and read them */ ?>
        <a class="stat" href="<?= e(url('radar')) ?>">
            <div class="num"><?= (int) $radarCount ?></div>
            <div class="label">Discovered on Radar</div>
        </a>
    </div>
</section>

<section style="margin-bottom: var(--s5);">
    <div class="section-head">
        <h2>Browse by topic</h2>
    </div>
    <?php View::partial('partials/_topics', ['counts' => $counts]); ?>
</section>

<div class="split" id="articles">
    <div>
        <?php if ($lead === null): ?>
            <?php View::partial('partials/_empty', [
                'message'    => 'No articles have been published yet.',
                'actionUrl'  => Auth::check() ? url('posts/create') : url('register'),
                'actionText' => Auth::check() ? 'Write the first one' : 'Create an account',
            ]); ?>
        <?php else: ?>
            <div class="section-head">
                <h2>Featured</h2>
                <span class="meta"><?= e(post_date($lead)) ?></span>
            </div>

            <article class="lead-card">
                <a href="<?= e(url('posts/' . (int) $lead['id'])) ?>" aria-hidden="true" tabindex="-1">
                    <?php View::partial('posts/_cover', ['post' => $lead, 'showBadge' => false]); ?>
                </a>

                <div class="lead-card-body">
                    <?php View::partial('posts/_badge', ['category' => (string) $lead['category']]); ?>
                    <h2><a href="<?= e(url('posts/' . (int) $lead['id'])) ?>"><?= e($lead['title']) ?></a></h2>
                    <p class="excerpt"><?= e(post_summary($lead, 200)) ?></p>

                    <div class="byline">
                        <a class="byline-author" href="<?= e(url('authors/' . rawurlencode((string) $lead['username']))) ?>">
                            <?php View::partial('partials/_avatar', ['user' => $lead, 'size' => 'md']); ?>
                            <span class="author"><?= e(User::nameFor($lead)) ?></span>
                        </a>
                        <span class="sep">&middot;</span>
                        <span><?= e(post_minutes($lead)) ?></span>
                    </div>
                </div>
            </article>

            <?php if ($rest !== []): ?>
                <div class="section-head">
                    <h2>Latest articles</h2>
                    <span class="meta"><?= count($rest) ?> more</span>
                </div>
                <div class="grid">
                    <?php foreach ($rest as $post): ?>
                        <?php View::partial('posts/_card', ['post' => $post]); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php View::partial('partials/_sidebar', [
        'counts' => $counts,
        'recent' => array_slice($posts, 0, 4),
    ]); ?>
</div>

<?php if ($radarPicks !== []): ?>
    <?php /* Keeps the homepage worth visiting even before anyone has published
             here. Ordered by reactions, so "Popular" rather than "Latest". */ ?>
    <section class="radar-strip">
        <div class="section-head">
            <h2>Popular on Radar</h2>
            <a class="link" href="<?= e(url('radar')) ?>">All <?= (int) $radarCount ?> picks &rarr;</a>
        </div>

        <div class="grid">
            <?php foreach ($radarPicks as $pick): ?>
                <?php View::partial('radar/_card', ['post' => $pick]); ?>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
