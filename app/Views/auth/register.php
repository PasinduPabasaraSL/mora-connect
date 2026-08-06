<?php

use App\Core\Csrf;

/** @var list<string> $errors */

$errors = $errors ?? [];
?>
<div class="auth-page">
    <div class="auth-card">
        <div class="brand-mark"><span>MC</span></div>
        <h2>Register</h2>
        <p class="subtitle">Join the academic conversation.</p>

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
                <p class="field-hint ui-metadata">At least 8 characters.</p>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                       minlength="8" autocomplete="new-password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Create Account</button>
        </form>

        <p class="switch-link">Already have an account? <a href="<?= e(url('login')) ?>">Sign in</a></p>
    </div>
</div>
