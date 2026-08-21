<?php

use App\Core\View;
use App\Models\Post;
use App\Models\User;

/**
 * @var array<string, int>        $figures
 * @var array<string, int>        $signups
 * @var array<string, int>        $publications
 * @var list<array<string,mixed>> $recent
 * @var list<array<string,mixed>> $members
 * @var array<string, mixed>      $radar
 */

$published = $figures['live'] + $figures['unlisted'];

// Rounded down to thousands once it gets long, because the exact word count of
// the whole site is not a number anybody reads digit by digit.
$words = $figures['words'] >= 10000
    ? round($figures['words'] / 1000, 1) . 'k'
    : number_format($figures['words']);
?>
<?php View::partial('admin/_tiles', ['tiles' => [
    ['label' => 'Members',        'value' => $figures['members'], 'note' => $figures['writers'] . ' have published'],
    ['label' => 'Live articles',  'value' => $figures['live'],    'note' => 'Public on the site'],
    ['label' => 'Drafts',         'value' => $figures['drafts'],  'note' => 'Not yet published', 'tone' => $figures['drafts'] > 0 ? 'warn' : ''],
    ['label' => 'Unlisted',       'value' => $figures['unlisted'],'note' => 'Reachable by link'],
    ['label' => 'Words published','value' => $words,              'note' => 'Across ' . $published . ' articles'],
    ['label' => 'Radar entries',  'value' => $figures['radar'],   'note' => 'Collected elsewhere'],
]]); ?>

<div class="admin-grid admin-grid-2">
    <section class="admin-card">
        <header class="admin-card-head">
            <h2>New members</h2>
            <p>Accounts created, by month</p>
        </header>

        <?php View::partial('admin/_chart', [
            'series' => $signups,
            'label'  => 'Accounts created per month over the last year',
        ]); ?>
    </section>

    <section class="admin-card">
        <header class="admin-card-head">
            <h2>Articles published</h2>
            <p>By the month they went live</p>
        </header>

        <?php View::partial('admin/_chart', [
            'series' => $publications,
            'label'  => 'Articles published per month over the last year',
        ]); ?>
    </section>
</div>

<div class="admin-grid admin-grid-wide">
    <section class="admin-card">
        <header class="admin-card-head">
            <h2>Latest activity</h2>
            <p>Every article, newest edit first</p>
        </header>

        <?php if ($recent === []): ?>
            <p class="admin-muted">Nothing has been written yet.</p>
        <?php else: ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th scope="col">Article</th>
                            <th scope="col">Author</th>
                            <th scope="col">State</th>
                            <th scope="col" class="numeric">Words</th>
                            <th scope="col">Edited</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent as $post): ?>
                            <?php
                            $isDraft = $post['status'] !== Post::STATUS_PUBLISHED;
                            $isUnlisted = !$isDraft && $post['visibility'] === Post::VISIBILITY_UNLISTED;
                            ?>
                            <tr>
                                <td>
                                    <?php /* Drafts have no public address, so only the
                                             published ones become links. */ ?>
                                    <?php if ($isDraft): ?>
                                        <span class="admin-strong"><?= e($post['title']) ?></span>
                                    <?php else: ?>
                                        <a class="admin-strong" href="<?= e(url('posts/' . ($post['slug'] ?: $post['id']))) ?>">
                                            <?= e($post['title']) ?>
                                        </a>
                                    <?php endif; ?>
                                    <span class="admin-sub"><?= e($post['category']) ?></span>
                                </td>
                                <td>
                                    <a class="admin-person" href="<?= e(url('authors/' . rawurlencode((string) $post['username']))) ?>">
                                        <?php View::partial('partials/_avatar', ['user' => $post, 'size' => 'sm']); ?>
                                        <span><?= e(User::nameFor($post)) ?></span>
                                    </a>
                                </td>
                                <td>
                                    <?php if ($isDraft): ?>
                                        <span class="admin-pill pill-draft">Draft</span>
                                    <?php elseif ($isUnlisted): ?>
                                        <span class="admin-pill pill-unlisted">Unlisted</span>
                                    <?php else: ?>
                                        <span class="admin-pill pill-live">Live</span>
                                    <?php endif; ?>
                                </td>
                                <td class="numeric"><?= number_format((int) $post['word_count']) ?></td>
                                <td><?= e(format_date($post['updated_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="admin-card">
        <header class="admin-card-head">
            <h2>Newest members</h2>
            <p>Most recent sign-ups</p>
        </header>

        <?php if ($members === []): ?>
            <p class="admin-muted">No accounts yet.</p>
        <?php else: ?>
            <ul class="admin-people">
                <?php foreach ($members as $member): ?>
                    <li>
                        <?php View::partial('partials/_avatar', ['user' => $member, 'size' => 'md']); ?>
                        <div>
                            <a class="admin-strong" href="<?= e(url('authors/' . rawurlencode((string) $member['username']))) ?>">
                                <?= e(User::nameFor($member)) ?>
                            </a>
                            <span class="admin-sub">
                                <?php
                                // Whatever they told us, in order of usefulness
                                $detail = $member['headline']
                                    ?: trim(($member['study_year'] ?? '') . ' ' . ($member['faculty'] ?? ''));
                                ?>
                                <?= e($detail !== '' ? $detail : '@' . $member['username']) ?>
                            </span>
                        </div>
                        <span class="admin-when"><?= e(format_date($member['created_at'])) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <div class="admin-note">
            <strong>Radar</strong> last refreshed
            <?= e($radar['updated'] === null ? 'never' : format_date($radar['updated'])) ?>,
            holding <?= (int) $radar['entries'] ?> entries from
            <?= (int) $radar['sources'] ?> source<?= (int) $radar['sources'] === 1 ? '' : 's' ?>.
        </div>
    </section>
</div>
