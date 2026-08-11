<?php

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Html;
use App\Core\View;
use App\Models\Post;

/**
 * @var array<string, mixed>             $post
 * @var bool                             $isOwner
 * @var bool                             $isPreview
 * @var array<int, array<string, mixed>> $related
 * @var array<string, int>               $counts
 */

$image     = trim((string) ($post['image_url'] ?? ''));
$category  = (string) $post['category'];
$subtitle  = trim((string) ($post['subtitle'] ?? ''));
$tags      = Post::tagList($post['tags'] ?? null);
$isDraft   = ($post['status'] ?? Post::STATUS_PUBLISHED) !== Post::STATUS_PUBLISHED;
$unlisted  = ($post['visibility'] ?? Post::VISIBILITY_PUBLIC) === Post::VISIBILITY_UNLISTED;
$postId    = (int) $post['id'];

// Articles written before the rich editor are stored as plain text, so they are
// escaped and wrapped here; newer ones hold markup the sanitiser already vetted.
$isHtml = ($post['content_format'] ?? 'text') === 'html';
$bodyHtml = $isHtml
    ? (string) $post['content']
    : Html::fromPlainText((string) $post['content']);

// Both counts are stored at save time; the fallbacks cover rows written before
// the columns existed.
$minutes = (int) ($post['reading_minutes'] ?? 0)
    ?: Html::readingMinutes(Html::wordCount($bodyHtml));

$shownAt = $post['published_at'] ?? $post['created_at'];
?>
<?php if ($isOwner && ($isDraft || $isPreview)): ?>
    <?php /* The author needs to know at a glance that nobody else can see this
             page, because otherwise a draft looks identical to a live article. */ ?>
    <div class="article-notice">
        <div>
            <strong><?= $isDraft ? 'This is a draft.' : 'Preview.' ?></strong>
            <?= $isDraft
                ? 'Only you can see it until you publish.'
                : 'This is the published article as readers see it.' ?>
        </div>
        <a class="btn btn-sm" href="<?= e(url('posts/' . $postId . '/edit')) ?>">Back to editing</a>
    </div>
<?php elseif ($isOwner && $unlisted): ?>
    <div class="article-notice">
        <div>
            <strong>Unlisted.</strong>
            Readable by anyone with the link, but kept out of the homepage, topics and search.
        </div>
        <a class="btn btn-sm" href="<?= e(url('posts/' . $postId . '/edit')) ?>">Article settings</a>
    </div>
<?php endif; ?>

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

            <?php if ($subtitle !== ''): ?>
                <p class="article-standfirst"><?= e($subtitle) ?></p>
            <?php endif; ?>

            <div class="byline">
                <span class="avatar"><?= e(mb_substr((string) $post['username'], 0, 2)) ?></span>
                <span class="author"><?= e($post['username']) ?></span>
                <span class="sep">&middot;</span>
                <span><?= e(format_date($shownAt)) ?></span>
                <span class="sep">&middot;</span>
                <span><?= $minutes ?> min read</span>
            </div>
        </div>

        <?php /* Already sanitised against an allowlist on the way into the
                 database, so it is printed as markup rather than escaped. */ ?>
        <div class="article-body"><?= $bodyHtml ?></div>

        <?php if ($tags !== []): ?>
            <div class="article-tags">
                <?php foreach ($tags as $tag): ?>
                    <a class="tag-chip" href="<?= e(url('search?q=' . urlencode($tag))) ?>"><?= e($tag) ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($isOwner): ?>
            <div class="owner-actions">
                <a href="<?= e(url('posts/' . $postId . '/edit')) ?>" class="btn">Edit article</a>

                <?php if (!$isDraft): ?>
                    <?php /* Taking an article down should not mean deleting it. */ ?>
                    <form method="POST" action="<?= e(url('posts/' . $postId . '/unpublish')) ?>">
                        <?= Csrf::field() ?>
                        <button type="submit" class="btn">Move to drafts</button>
                    </form>
                <?php endif; ?>

                <form method="POST"
                      action="<?= e(url('posts/' . $postId . '/delete')) ?>"
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
            <p><?= $isDraft ? 'Drafted' : 'Published' ?> <?= e(format_date($shownAt)) ?> under
                <a class="link" href="<?= e(url('topics/' . Post::slugFor($category))) ?>"><?= e($category) ?></a>.</p>
            <p class="mono article-meta"><?= (int) ($post['word_count'] ?? 0) ?> words &middot; <?= $minutes ?> min read</p>
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
