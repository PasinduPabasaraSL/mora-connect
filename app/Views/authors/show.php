<?php

use App\Core\View;

/**
 * A writer's public page. Published, public articles only.
 *
 * @var array<string, mixed>                  $author
 * @var array<int, array<string, mixed>>      $posts
 * @var array{articles: int, words: int, topics: int} $stats
 * @var bool                                  $isSelf
 */

$actions = $isSelf
    ? '<a class="btn" href="' . e(url('settings')) . '">Edit profile</a>'
    : '';

$joined = $author['created_at'] ?? null;
$joined = $joined === null ? '—' : date('Y', (int) strtotime((string) $joined));
?>
<?php View::partial('profile/_identity', [
    'user'    => $author,
    'actions' => $actions,
    'tiles'   => [
        ['num' => (string) $stats['articles'], 'label' => $stats['articles'] === 1 ? 'Article' : 'Articles'],
        ['num' => number_format($stats['words']), 'label' => 'Words published'],
        ['num' => (string) $stats['topics'], 'label' => 'Topics'],
        ['num' => $joined, 'label' => 'Joined'],
    ],
]); ?>

<div class="section-head author-articles">
    <h2>Articles</h2>
    <?php if ($isSelf): ?>
        <span class="meta">Drafts and unlisted articles are not shown here</span>
    <?php endif; ?>
</div>

<?php if ($posts === []): ?>
    <?php View::partial('partials/_empty', [
        'message' => $isSelf
            ? 'You have not published anything yet, so this page is empty for visitors.'
            : 'This writer has not published anything yet.',
    ]); ?>
<?php else: ?>
    <div class="grid">
        <?php foreach ($posts as $post): ?>
            <?php View::partial('posts/_card', ['post' => $post]); ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
