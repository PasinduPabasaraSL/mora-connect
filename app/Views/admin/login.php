<?php

use App\Core\Csrf;

/**
 * Sign-in for the panel, served from /admin itself.
 *
 * @var int $locked seconds left on a lockout, 0 when guesses are accepted
 */

$locked  = (int) ($locked ?? 0);
$minutes = (int) ceil($locked / 60);
?>
<div class="admin-card admin-login">
    <div class="admin-login-head">
        <span class="brand-mark">MC</span>
        <h1>Admin panel</h1>
        <p>Sign in to see how the portal is doing.</p>
    </div>

    <?php if ($locked > 0): ?>
        <p class="admin-lockout">
            Too many failed attempts. Try again in
            <?= $minutes ?> minute<?= $minutes === 1 ? '' : 's' ?>.
        </p>
    <?php endif; ?>

    <form method="POST" action="<?= e(url('admin/login')) ?>" autocomplete="off">
        <?= Csrf::field() ?>

        <div class="form-group">
            <label class="field-label" for="adminUsername">Username</label>
            <input type="text" class="form-control" id="adminUsername" name="username"
                   required autofocus autocapitalize="none" spellcheck="false"
                   <?= $locked > 0 ? 'disabled' : '' ?>>
        </div>

        <div class="form-group">
            <label class="field-label" for="adminPassword">Password</label>
            <input type="password" class="form-control" id="adminPassword" name="password"
                   required <?= $locked > 0 ? 'disabled' : '' ?>>
        </div>

        <button type="submit" class="btn btn-primary btn-block"
                <?= $locked > 0 ? 'disabled' : '' ?>>Sign in</button>
    </form>

    <p class="admin-login-foot">
        <a class="link" href="<?= e(url()) ?>">Back to MoraConnect</a>
    </p>
</div>
