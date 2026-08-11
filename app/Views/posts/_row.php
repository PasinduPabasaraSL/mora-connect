<?php

use App\Core\Csrf;
use App\Core\View;
use App\Models\Post;

/**
 * Compact numbered article row. Used for the author's own articles.
 *
 * @var array<string, mixed> $post
 * @var int                  $index    1-based position
 * @var bool                 $editable show an Edit action
 */

$editable = $editable ?? false;
$isDraft  = ($post['status'] ?? Post::STATUS_PUBLISHED) !== Post::STATUS_PUBLISHED;
$unlisted = ($post['visibility'] ?? Post::VISIBILITY_PUBLIC) === Post::VISIBILITY_UNLISTED;

$target = $isDraft && $editable
    ? url('posts/' . (int) $post['id'] . '/edit')
    : url('posts/' . (int) $post['id']);
?>
<div class="row<?= $isDraft ? ' is-draft' : '' ?>">
    <span class="row-index"><?= (int) $index ?></span>

    <div class="row-body">
        <h3>
            <a href="<?= e($target) ?>">
                <?= e(trim((string) $post['title']) === '' ? 'Untitled draft' : $post['title']) ?>
            </a>
        </h3>
        <div class="meta">
            <?php if ($isDraft): ?>
                <span class="status-pill">Draft</span>
            <?php elseif ($unlisted): ?>
                <span class="status-pill">Unlisted</span>
            <?php endif; ?>
            <?php if (isset($post['username'])): ?><?= e($post['username']) ?> &middot; <?php endif; ?>
            <?= e(post_date($post)) ?> &middot; <?= e(post_minutes($post)) ?>
        </div>
    </div>

    <div class="row-actions">
        <?php View::partial('posts/_badge', ['category' => (string) $post['category']]); ?>
        <?php if ($editable): ?>
            <a href="<?= e(url('posts/' . (int) $post['id'] . '/edit')) ?>" class="btn btn-sm">
                <?= $isDraft ? 'Continue' : 'Edit' ?>
            </a>

            <?php /* Drafts are deleted from here because they have no reader page
                     to do it from — their title opens the editor instead. A
                     published article keeps its Delete on the article itself. */ ?>
            <?php if ($isDraft): ?>
                <form method="POST"
                      action="<?= e(url('posts/' . (int) $post['id'] . '/delete')) ?>"
                      class="inline-form"
                      data-confirm="This draft and everything written in it will be removed. This cannot be undone."
                      data-confirm-title="Delete this draft?"
                      data-confirm-accept="Delete draft">
                    <?= Csrf::field() ?>
                    <button type="submit" class="icon-btn icon-btn-danger" title="Delete this draft"
                            aria-label="Delete draft <?= e(trim((string) $post['title']) === '' ? 'Untitled draft' : $post['title']) ?>">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14"></path>
                            <path d="M10 11v6M14 11v6"></path>
                        </svg>
                    </button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
