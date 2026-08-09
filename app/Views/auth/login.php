<?php

use App\Core\Csrf;

/** @var list<string> $errors */

$errors = $errors ?? [];
?>
<div class="auth">
    <div class="auth-card">
        <span class="eyebrow">Sign in</span>
        <h1>Welcome back.</h1>
        <p class="lead">Pick up where you left off.</p>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endforeach; ?>

        <form method="POST" action="<?= e(url('login')) ?>">
            <?= Csrf::field() ?>

            <div class="form-group">
                <label for="identifier">Username or email</label>
                <input type="text" id="identifier" name="identifier" class="form-control"
                       value="<?= e($identifier ?? '') ?>" autocomplete="username" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control"
                       autocomplete="current-password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Sign in</button>
        </form>

        <p class="switch-link">No account? <a class="link" href="<?= e(url('register')) ?>">Create one</a></p>
    </div>
</div>
