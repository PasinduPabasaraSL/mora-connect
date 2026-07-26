<?php

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoraConnect</title>
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container">
        <a href="index.php" class="brand">MoraConnect</a>

        <nav class="main-nav">
            <a href="index.php" class="ui-nav">Explore</a>
            <?php if (isLoggedIn()): ?>
                <a href="create-post.php" class="ui-nav">Write</a>
            <?php endif; ?>
        </nav>

        <div class="header-actions">
            <?php if (isLoggedIn()): ?>
                <a href="profile.php" class="ui-nav"><?= htmlspecialchars($_SESSION['username']) ?></a>
                <a href="logout.php" class="btn btn-secondary">Log Out</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-secondary">Log In</a>
                <a href="register.php" class="btn btn-primary">Sign Up</a>
            <?php endif; ?>
            <button class="nav-toggle" id="navToggle" aria-label="Menu">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>

    <div class="mobile-nav" id="mobileNav">
        <a href="index.php" class="ui-nav">Explore</a>
        <?php if (isLoggedIn()): ?>
            <a href="create-post.php" class="ui-nav">Write</a>
            <a href="profile.php" class="ui-nav"><?= htmlspecialchars($_SESSION['username']) ?></a>
            <a href="logout.php" class="btn btn-secondary">Log Out</a>
        <?php else: ?>
            <a href="login.php" class="btn btn-secondary">Log In</a>
            <a href="register.php" class="btn btn-primary">Sign Up</a>
        <?php endif; ?>
    </div>
</header>
<main class="container" style="padding-top: var(--space-lg); padding-bottom: var(--space-lg);">