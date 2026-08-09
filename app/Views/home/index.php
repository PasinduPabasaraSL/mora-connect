<?php

use App\Core\Auth;
use App\Core\View;

/**
 * @var array<int, array<string, mixed>> $posts
 * @var array<string, int>               $counts
 * @var array{articles: int, writers: int, topics: int} $stats
 */

// The newest article is featured; the rest fill the grid below it.
$lead = $posts[0] ?? null;
$rest = array_slice($posts, 1);
?>
<section class="hero">
    <span class="eyebrow">University of Moratuwa</span>
    <h1>Engineering notes, written by students.</h1>
    <p class="lead">
        Build logs, debugging stories and technical write-ups from the people
        actually doing the work — published openly so the next student does not
        have to start from scratch.
    </p>
    <div class="hero-actions">
        <a href="#articles" class="btn btn-primary">Read articles</a>
        <?php if (!Auth::check()): ?>
            <a href="<?= e(url('register')) ?>" class="btn">Start writing</a>
        <?php endif; ?>
    </div>

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
                <span class="meta"><?= e(format_date($lead['created_at'])) ?></span>
            </div>

            <article class="lead-card">
                <a href="<?= e(url('posts/' . (int) $lead['id'])) ?>" aria-hidden="true" tabindex="-1">
                    <?php View::partial('posts/_cover', ['post' => $lead, 'showBadge' => false]); ?>
                </a>

                <div class="lead-card-body">
                    <?php View::partial('posts/_badge', ['category' => (string) $lead['category']]); ?>
                    <h2><a href="<?= e(url('posts/' . (int) $lead['id'])) ?>"><?= e($lead['title']) ?></a></h2>
                    <p class="excerpt"><?= e(excerpt((string) $lead['content'], 200)) ?></p>

                    <div class="byline">
                        <span class="avatar"><?= e(mb_substr((string) $lead['username'], 0, 2)) ?></span>
                        <span class="author"><?= e($lead['username']) ?></span>
                        <span class="sep">&middot;</span>
                        <span><?= e(reading_time((string) $lead['content'])) ?></span>
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
