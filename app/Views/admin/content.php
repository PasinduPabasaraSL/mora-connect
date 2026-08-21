<?php

use App\Core\View;
use App\Models\Post;
use App\Models\User;

/**
 * @var array<string, int|float>  $breakdown
 * @var array<string, int>        $missing
 * @var list<array<string,mixed>> $topics
 * @var list<array<string,mixed>> $stale
 */
?>
<?php View::partial('admin/_tiles', ['tiles' => [
    ['label' => 'All articles',     'value' => $breakdown['total']],
    ['label' => 'Live',             'value' => $breakdown['live']],
    ['label' => 'Unlisted',         'value' => $breakdown['unlisted']],
    ['label' => 'Drafts',           'value' => $breakdown['drafts'], 'tone' => $breakdown['drafts'] > 0 ? 'warn' : ''],
    ['label' => 'Average length',   'value' => number_format($breakdown['avg_words']), 'note' => 'words, published only'],
    ['label' => 'Average read',     'value' => $breakdown['avg_minutes'], 'note' => 'minutes'],
]]); ?>

<div class="admin-grid admin-grid-2">
    <section class="admin-card">
        <header class="admin-card-head">
            <h2>Topics</h2>
            <p>Live articles per topic, with drafts in progress</p>
        </header>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col">Topic</th>
                        <th scope="col" class="numeric">Live</th>
                        <th scope="col" class="numeric">Drafts</th>
                        <th scope="col" class="numeric">Words</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topics as $row): ?>
                        <tr<?= $row['live'] === 0 && $row['drafts'] === 0 ? ' class="is-empty"' : '' ?>>
                            <td>
                                <span class="admin-dot" style="--dot-bg: <?= e(Post::colorsFor($row['topic'])['bg']) ?>"></span>
                                <a href="<?= e(url('topics/' . Post::slugFor($row['topic']))) ?>"><?= e($row['topic']) ?></a>
                            </td>
                            <td class="numeric"><?= (int) $row['live'] ?></td>
                            <td class="numeric"><?= (int) $row['drafts'] ?></td>
                            <td class="numeric"><?= number_format((int) $row['words']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="admin-card">
        <header class="admin-card-head">
            <h2>Room for improvement</h2>
            <p>Optional fields left blank on published articles</p>
        </header>

        <?php
        $total = max(1, $missing['total']);

        // Framed as what is missing rather than what is filled in, since the
        // point of the list is to be worked through.
        $gaps = [
            ['label' => 'No summary',      'value' => $missing['no_description']],
            ['label' => 'No cover image',  'value' => $missing['no_cover']],
            ['label' => 'No tags',         'value' => $missing['no_tags']],
            ['label' => 'No subtitle',     'value' => $missing['no_subtitle']],
            ['label' => 'No custom slug',  'value' => $missing['no_slug']],
        ];

        foreach ($gaps as $index => $gap) {
            $gaps[$index]['note'] = 'of ' . $missing['total'];
        }
        ?>

        <?php View::partial('admin/_bars', [
            'rows'  => $gaps,
            'empty' => 'Nothing published yet, so there is nothing to check.',
        ]); ?>

        <?php if ($missing['total'] > 0): ?>
            <p class="admin-muted admin-tight">
                Every one of these is optional. A summary and a cover image are the
                two that change how an article looks in a listing.
            </p>
        <?php endif; ?>
    </section>
</div>

<section class="admin-card">
    <header class="admin-card-head">
        <h2>Stalled drafts</h2>
        <p>Started, then untouched for more than a month</p>
    </header>

    <?php if ($stale === []): ?>
        <p class="admin-muted">No draft has been sitting for longer than a month.</p>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col">Draft</th>
                        <th scope="col">Author</th>
                        <th scope="col">Topic</th>
                        <th scope="col" class="numeric">Words</th>
                        <th scope="col">Last edited</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stale as $draft): ?>
                        <tr>
                            <td><span class="admin-strong"><?= e($draft['title'] ?: 'Untitled') ?></span></td>
                            <td>
                                <a href="<?= e(url('authors/' . rawurlencode((string) $draft['username']))) ?>">
                                    <?= e(User::nameFor($draft)) ?>
                                </a>
                            </td>
                            <td><?= e($draft['category']) ?></td>
                            <td class="numeric"><?= number_format((int) $draft['word_count']) ?></td>
                            <td><?= e(format_date($draft['updated_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
