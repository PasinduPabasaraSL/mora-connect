<?php

use App\Core\Csrf;
use App\Core\View;

/** @var list<string> $errors */

$errors = $errors ?? [];
?>
<div class="auth">
    <div class="auth-card">
        <span class="eyebrow">Create account</span>
        <h1>Start writing.</h1>
        <p class="lead">Publish your technical work where other students will find it.</p>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endforeach; ?>

        <form method="POST" action="<?= e(url('register')) ?>">
            <?= Csrf::field() ?>

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control"
                       value="<?= e($username ?? '') ?>" minlength="3" maxlength="100"
                       autocomplete="username" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control"
                       value="<?= e($email ?? '') ?>" maxlength="150" autocomplete="email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control"
                       minlength="8" autocomplete="new-password" required>
                <p class="hint">At least 8 characters.</p>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                       minlength="8" autocomplete="new-password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Create account</button>
        </form>

        <?php View::partial('partials/_google_button', ['label' => 'Sign up with Google']); ?>

        <p class="switch-link">Already registered? <a class="link" href="<?= e(url('login')) ?>">Sign in</a></p>
    </div>
</div>
