<?php

use App\Core\View;
use App\Models\User;

/**
 * @var list<array<string,mixed>> $writers
 * @var array<string, int>        $figures
 */

$quiet = 0;

foreach ($writers as $writer) {
    if ((int) $writer['articles'] === 0) {
        $quiet++;
    }
}
?>
<?php View::partial('admin/_tiles', ['tiles' => [
    ['label' => 'Members',          'value' => count($writers)],
    ['label' => 'Have published',   'value' => $figures['writers']],
    ['label' => 'Yet to write',     'value' => $quiet, 'note' => 'Signed up, nothing started'],
    ['label' => 'Words published',  'value' => number_format($figures['words'])],
]]); ?>

<section class="admin-card">
    <header class="admin-card-head">
        <h2>Everyone with an account</h2>
        <p>Ranked by published articles. Members who have not written yet are listed too.</p>
    </header>

    <?php if ($writers === []): ?>
        <p class="admin-muted">Nobody has registered yet.</p>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col">Member</th>
                        <th scope="col">Faculty and year</th>
                        <th scope="col" class="numeric">Published</th>
                        <th scope="col" class="numeric">Drafts</th>
                        <th scope="col" class="numeric">Words</th>
                        <th scope="col">Last published</th>
                        <th scope="col">Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($writers as $writer): ?>
                        <?php
                        $academic = trim(
                            ($writer['study_year'] ?? '')
                            . (($writer['study_year'] ?? '') !== '' && ($writer['faculty'] ?? '') !== '' ? ', ' : '')
                            . ($writer['faculty'] ?? '')
                        );
                        ?>
                        <tr<?= (int) $writer['articles'] === 0 ? ' class="is-empty"' : '' ?>>
                            <td>
                                <a class="admin-person" href="<?= e(url('authors/' . rawurlencode((string) $writer['username']))) ?>">
                                    <?php View::partial('partials/_avatar', ['user' => $writer, 'size' => 'sm']); ?>
                                    <span>
                                        <span class="admin-strong"><?= e(User::nameFor($writer)) ?></span>
                                        <span class="admin-sub">@<?= e((string) $writer['username']) ?></span>
                                    </span>
                                </a>
                            </td>
                            <td><?= e($academic !== '' ? $academic : '—') ?></td>
                            <td class="numeric"><?= (int) $writer['published'] ?></td>
                            <td class="numeric"><?= (int) $writer['drafts'] ?></td>
                            <td class="numeric"><?= number_format((int) $writer['words']) ?></td>
                            <td><?= e($writer['last_published'] === null ? '—' : format_date($writer['last_published'])) ?></td>
                            <td><?= e(format_date($writer['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
