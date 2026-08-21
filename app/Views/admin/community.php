<?php

use App\Core\View;

/**
 * @var array<string, int>                        $figures
 * @var list<array{label: string, total: int}>    $faculties
 * @var list<array{label: string, total: int}>    $years
 * @var array<string, int>                        $signIn
 * @var array<string, int>                        $completeness
 * @var list<array{topic: string, total: int}>    $interests
 * @var array<string, int>                        $signups
 */

$members = max(1, $figures['members']);

/** Turns a count into "12 of 30 (40%)" without repeating the arithmetic. */
$share = static function (int $count) use ($members, $figures): string {
    if ($figures['members'] === 0) {
        return '0';
    }

    return $count . ' of ' . $figures['members'] . ' (' . round(($count / $members) * 100) . '%)';
};
?>
<?php View::partial('admin/_tiles', ['tiles' => [
    ['label' => 'Members',       'value' => $figures['members']],
    ['label' => 'Google only',   'value' => $signIn['google_only'], 'note' => 'No password set'],
    ['label' => 'Password only', 'value' => $signIn['password_only']],
    ['label' => 'Both methods',  'value' => $signIn['both_ways'], 'note' => 'Can sign in either way'],
]]); ?>

<section class="admin-card">
    <header class="admin-card-head">
        <h2>How the community grew</h2>
        <p>Accounts created, by month</p>
    </header>

    <?php View::partial('admin/_chart', [
        'series' => $signups,
        'label'  => 'Accounts created per month over the last year',
    ]); ?>
</section>

<div class="admin-grid admin-grid-2">
    <section class="admin-card">
        <header class="admin-card-head">
            <h2>Faculty</h2>
            <p>As given on profiles, which is optional</p>
        </header>

        <?php View::partial('admin/_bars', [
            'rows'  => array_map(
                static fn (array $row): array => ['label' => $row['label'], 'value' => $row['total']],
                $faculties
            ),
            'empty' => 'No accounts yet.',
        ]); ?>
    </section>

    <section class="admin-card">
        <header class="admin-card-head">
            <h2>Year of study</h2>
            <p>Chosen from a fixed list, or left blank</p>
        </header>

        <?php View::partial('admin/_bars', [
            'rows'  => array_map(
                static fn (array $row): array => ['label' => $row['label'], 'value' => $row['total']],
                $years
            ),
            'empty' => 'No accounts yet.',
        ]); ?>
    </section>
</div>

<div class="admin-grid admin-grid-2">
    <section class="admin-card">
        <header class="admin-card-head">
            <h2>Profiles filled in</h2>
            <p>Which optional fields people actually use</p>
        </header>

        <ul class="admin-spec">
            <li><span>Display name</span><span><?= e($share($completeness['display_name'])) ?></span></li>
            <li><span>Headline</span><span><?= e($share($completeness['headline'])) ?></span></li>
            <li><span>Bio</span><span><?= e($share($completeness['bio'])) ?></span></li>
            <li><span>Profile picture</span><span><?= e($share($completeness['avatar'])) ?></span></li>
            <li><span>Faculty</span><span><?= e($share($completeness['faculty'])) ?></span></li>
            <li><span>At least one link</span><span><?= e($share($completeness['links'])) ?></span></li>
            <li><span>Topics of interest</span><span><?= e($share($completeness['interests'])) ?></span></li>
        </ul>

        <p class="admin-muted admin-tight">
            A field nobody fills in is a field worth reconsidering.
        </p>
    </section>

    <section class="admin-card">
        <header class="admin-card-head">
            <h2>What people say they write about</h2>
            <p>Stated interests, which need not match what they publish</p>
        </header>

        <?php View::partial('admin/_bars', [
            'rows'  => array_map(
                static fn (array $row): array => ['label' => $row['topic'], 'value' => $row['total']],
                $interests
            ),
            'empty' => 'Nobody has chosen any topics yet.',
        ]); ?>
    </section>
</div>
