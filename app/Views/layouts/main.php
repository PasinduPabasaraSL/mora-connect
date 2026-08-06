<?php

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Session;

/** @var string $content */
/** @var string $title */

$successFlash = Session::pullFlash('success');
$errorFlash   = Session::pullFlash('error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<body>
<header class="site-header">
    <div class="container">
        <a href="<?= e(url()) ?>" class="brand">MoraConnect</a>

        <div class="header-right">
            <nav class="main-nav">
                <a href="<?= e(url()) ?>" class="ui-nav">Explore</a>
                <?php if (Auth::check()): ?>
                    <a href="<?= e(url('posts/create')) ?>" class="ui-nav">Write</a>
                <?php endif; ?>
            </nav>

            <div class="header-actions">
                <?php if (Auth::check()): ?>
                    <a href="<?= e(url('profile')) ?>" class="ui-nav"><?= e(Auth::username()) ?></a>
                    <form method="POST" action="<?= e(url('logout')) ?>" class="inline-form">
                        <?= Csrf::field() ?>
                        <button type="submit" class="btn btn-secondary">Log Out</button>
                    </form>
                <?php else: ?>
                    <a href="<?= e(url('login')) ?>" class="btn btn-secondary">Log In</a>
                    <a href="<?= e(url('register')) ?>" class="btn btn-primary">Sign Up</a>
                <?php endif; ?>
            </div>

            <button class="nav-toggle" id="navToggle" aria-label="Menu" aria-expanded="false" aria-controls="mobileNav">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>

    <div class="mobile-nav" id="mobileNav">
        <a href="<?= e(url()) ?>" class="ui-nav">Explore</a>
        <?php if (Auth::check()): ?>
            <a href="<?= e(url('posts/create')) ?>" class="ui-nav">Write</a>
            <a href="<?= e(url('profile')) ?>" class="ui-nav"><?= e(Auth::username()) ?></a>
            <form method="POST" action="<?= e(url('logout')) ?>">
                <?= Csrf::field() ?>
                <button type="submit" class="btn btn-secondary">Log Out</button>
            </form>
        <?php else: ?>
            <a href="<?= e(url('login')) ?>" class="btn btn-secondary">Log In</a>
            <a href="<?= e(url('register')) ?>" class="btn btn-primary">Sign Up</a>
        <?php endif; ?>
    </div>
</header>

<main class="container page-main">
    <?php if ($successFlash !== null): ?>
        <div class="alert alert-success"><?= e($successFlash) ?></div>
    <?php endif; ?>
    <?php if ($errorFlash !== null): ?>
        <div class="alert alert-error"><?= e($errorFlash) ?></div>
    <?php endif; ?>

    <?= $content ?>
</main>

<footer class="site-footer">
    <div class="container">
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> MoraConnect. University Student Publishing.</p>
            <p>University of Moratuwa</p>
        </div>
    </div>
</footer>

<script src="<?= e(asset('js/script.js')) ?>"></script>
</body>
</html>
