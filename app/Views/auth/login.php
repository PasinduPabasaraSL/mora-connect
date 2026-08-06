<?php

use App\Core\Csrf;

/** @var list<string> $errors */

$errors = $errors ?? [];
?>
<div class="auth-page">
    <div class="auth-card">
        <div class="brand-mark"><span>MC</span></div>
        <h2>Sign In</h2>
        <p class="subtitle">Welcome back to MoraConnect.</p>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endforeach; ?>

        <form method="POST" action="<?= e(url('login')) ?>">
            <?= Csrf::field() ?>

            <div class="form-group">
                <label for="identifier">Username or Email</label>
                <input type="text" id="identifier" name="identifier" class="form-control"
                       value="<?= e($identifier ?? '') ?>" autocomplete="username" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control"
                       autocomplete="current-password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Sign In</button>
        </form>

        <p class="switch-link">Don't have an account? <a href="<?= e(url('register')) ?>">Create one</a></p>
    </div>
</div>
