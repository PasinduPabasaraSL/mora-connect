<?php

use App\Core\View;
use App\Models\Post;

/**
 * @var array<string, mixed>             $user
 * @var array<int, array<string, mixed>> $posts
 */

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

// Built here rather than in the partial so the public page can pass its own
$actions = '<a class="btn" href="' . e(url('settings')) . '">'
    . '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
    . ' stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
    . '<path d="M11 4H7a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3v-4"></path>'
    . '<path d="M18.4 3.6a2 2 0 0 1 2.8 2.8L12.8 14.8l-3.6.8.8-3.6z"></path>'
    . '</svg>Edit profile</a>'
    . '<a class="btn btn-primary" href="' . e(url('posts/create')) . '">New article</a>';

$publicUrl = url('authors/' . rawurlencode((string) $user['username']));
?>
<?php View::partial('profile/_identity', [
    'user'    => $user,
    'actions' => $actions,
    'tiles'   => [
        ['num' => (string) count($live), 'label' => 'Published'],
        ['num' => (string) count($drafts), 'label' => 'Drafts'],
        ['num' => number_format($words), 'label' => 'Words written'],
        ['num' => (string) count(array_unique(array_column($live, 'category'))), 'label' => 'Topics'],
    ],
]); ?>

<p class="profile-note">
    <?php /* Somebody editing their profile has no other way to find out what a
             reader actually sees, which is what this link is for. */ ?>
    Your public page is
    <a class="link" href="<?= e($publicUrl) ?>">/authors/<?= e((string) $user['username']) ?></a>.
    Drafts and unlisted articles never appear on it.
</p>

<?php /* Tabs rather than two stacked lists: drafts are private working state and
         published work is the point of the page, so they should not compete. */ ?>
<div class="tabs" role="tablist" aria-label="Your articles">
    <button type="button" class="tab is-active" role="tab" aria-selected="true"
            aria-controls="panel-published" id="tab-published" data-tab="published">
        Published <span class="tab-count"><?= count($live) ?></span>
    </button>
    <button type="button" class="tab" role="tab" aria-selected="false"
            aria-controls="panel-drafts" id="tab-drafts" data-tab="drafts">
        Drafts <span class="tab-count"><?= count($drafts) ?></span>
    </button>
</div>

<div class="tab-panel" id="panel-published" role="tabpanel" aria-labelledby="tab-published" data-panel="published">
    <?php if ($live === []): ?>
        <?php View::partial('partials/_empty', [
            'message'    => $drafts === []
                ? 'You have not published anything yet.'
                : 'Nothing published yet. Open a draft when it is ready to go out.',
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
</div>

<div class="tab-panel" id="panel-drafts" role="tabpanel" aria-labelledby="tab-drafts" data-panel="drafts" hidden>
    <?php if ($drafts === []): ?>
        <?php View::partial('partials/_empty', [
            'message'    => 'No drafts. Anything you start and do not publish waits here.',
            'actionUrl'  => url('posts/create'),
            'actionText' => 'Start writing',
        ]); ?>
    <?php else: ?>
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
</div>
