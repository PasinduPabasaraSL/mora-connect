<?php

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Session;
use App\Models\Post;

/** @var string $content */
/** @var string $title */

$successFlash = Session::pullFlash('success');
$errorFlash   = Session::pullFlash('error');

$footerTopics = array_slice(Post::categories(), 0, 5);
$topicGroups  = Post::groupedCategories();

// Highlights the current section in the header
$path = $_SERVER['REQUEST_URI'] ?? '';
$isTopics = str_contains($path, '/topics/');
$isRadar  = str_contains($path, '/radar');
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?></title>
    <meta name="description" content="Technical writing, build logs and engineering notes by University of Moratuwa students.">

    <?php /* Runs before the stylesheet so the saved theme is applied on the
             first paint. In an external file this would flash light-then-dark. */ ?>
    <script>
        (function () {
            var saved = localStorage.getItem('theme');
            var dark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.dataset.theme = saved || (dark ? 'dark' : 'light');
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<body>
<header class="site-header">
    <div class="container">
        <a href="<?= e(url()) ?>" class="brand">
            <span class="brand-mark">MC</span>
            <span>MoraConnect</span>
        </a>

        <nav class="nav-links">
            <a href="<?= e(url()) ?>">Home</a>

            <?php /* data-dropdown pairs the button with the panel that follows it;
                     one handler in script.js drives every menu in the header. */ ?>
            <div class="dropdown">
                <button type="button" class="nav-link-btn<?= $isTopics ? ' is-active' : '' ?>"
                        data-dropdown aria-expanded="false" aria-haspopup="true">
                    Topics
                    <svg class="chevron" width="12" height="12" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M6 9l6 6 6-6"></path>
                    </svg>
                </button>

                <div class="dropdown-panel mega">
                    <?php foreach ($topicGroups as $heading => $topics): ?>
                        <div class="mega-col">
                            <h4><?= e($heading) ?></h4>
                            <?php foreach ($topics as $topic): ?>
                                <a href="<?= e(url('topics/' . Post::slugFor($topic))) ?>"
                                   style="--badge-bg: <?= e(Post::colorsFor($topic)['bg']) ?>;">
                                    <span class="dot"></span><?= e($topic) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>

                    <div class="mega-foot">
                        <span>Pick a topic to see its articles</span>
                        <a class="link" href="<?= e(url('search')) ?>">Browse everything &rarr;</a>
                    </div>
                </div>
            </div>

            <a href="<?= e(url('search')) ?>">Explore</a>
            <a href="<?= e(url('radar')) ?>"<?= $isRadar ? ' class="is-current"' : '' ?>>Radar</a>
            <a href="<?= e(url('about')) ?>">About</a>
        </nav>

        <div class="nav">
            <div class="nav-actions">
                <form class="search-inline" method="GET" action="<?= e(url('search')) ?>" role="search">
                    <button type="button" class="icon-btn" id="searchToggle" aria-label="Search articles" aria-expanded="false">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" aria-hidden="true">
                            <circle cx="11" cy="11" r="7"></circle><path d="M20 20l-3.5-3.5"></path>
                        </svg>
                    </button>
                    <input type="search" name="q" id="searchInput" class="search-field"
                           placeholder="Search articles" aria-label="Search articles" tabindex="-1">
                </form>

                <?php /* Both icons ship in the markup; CSS shows the one that matches
                         the active theme, so no icon markup lives in the JS. */ ?>
                <button type="button" class="icon-btn" id="themeToggle" aria-label="Toggle colour theme" aria-pressed="false">
                    <svg class="icon-moon" width="17" height="17" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"></path>
                    </svg>
                    <svg class="icon-sun" width="17" height="17" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="4.2"></circle>
                        <path d="M12 2v2M12 20v2M2 12h2M20 12h2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M19.1 4.9l-1.4 1.4M6.3 17.7l-1.4 1.4"></path>
                    </svg>
                </button>

                <?php if (Auth::check()): ?>
                    <a href="<?= e(url('posts/create')) ?>" class="btn btn-primary hide-sm">Write</a>

                    <div class="dropdown hide-sm">
                        <button type="button" class="avatar-btn" data-dropdown aria-expanded="false" aria-haspopup="true"
                                aria-label="Account menu">
                            <span class="avatar"><?= e(mb_substr((string) Auth::username(), 0, 2)) ?></span>
                            <svg class="chevron" width="12" height="12" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M6 9l6 6 6-6"></path>
                            </svg>
                        </button>

                        <div class="dropdown-panel menu">
                            <div class="menu-head">
                                <strong><?= e(Auth::username()) ?></strong>
                                <span>Signed in</span>
                            </div>
                            <a href="<?= e(url('profile')) ?>">Your articles</a>
                            <a href="<?= e(url('posts/create')) ?>">Write an article</a>
                            <div class="menu-sep"></div>
                            <form method="POST" action="<?= e(url('logout')) ?>">
                                <?= Csrf::field() ?>
                                <button type="submit" class="menu-danger">Log out</button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?= e(url('login')) ?>" class="btn hide-sm">Log in</a>
                    <a href="<?= e(url('register')) ?>" class="btn btn-primary hide-sm">Start writing</a>
                <?php endif; ?>
            </div>

            <button class="nav-toggle" id="navToggle" aria-label="Menu" aria-expanded="false" aria-controls="mobileNav">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>

    <?php /* The same destinations as the desktop bar, laid out vertically —
             the mega menu becomes a plain list of topics here. */ ?>
    <div class="mobile-nav" id="mobileNav">
        <form method="GET" action="<?= e(url('search')) ?>" class="mobile-search" role="search">
            <input type="search" name="q" class="form-control" placeholder="Search articles" aria-label="Search articles">
        </form>

        <a href="<?= e(url()) ?>" class="btn">Home</a>
        <a href="<?= e(url('radar')) ?>" class="btn">Radar</a>
        <a href="<?= e(url('about')) ?>" class="btn">About</a>

        <div class="mobile-topics">
            <h4>Topics</h4>
            <?php foreach (Post::categories() as $topic): ?>
                <a href="<?= e(url('topics/' . Post::slugFor($topic))) ?>"
                   style="--badge-bg: <?= e(Post::colorsFor($topic)['bg']) ?>;">
                    <span class="dot"></span><?= e($topic) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (Auth::check()): ?>
            <a href="<?= e(url('posts/create')) ?>" class="btn btn-primary">Write an article</a>
            <a href="<?= e(url('profile')) ?>" class="btn">Your articles</a>
            <form method="POST" action="<?= e(url('logout')) ?>">
                <?= Csrf::field() ?>
                <button type="submit" class="btn btn-block">Log out</button>
            </form>
        <?php else: ?>
            <a href="<?= e(url('login')) ?>" class="btn">Log in</a>
            <a href="<?= e(url('register')) ?>" class="btn btn-primary">Start writing</a>
        <?php endif; ?>
    </div>
</header>

<main class="container page">
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
        <div class="footer-grid">
            <div class="footer-col">
                <span class="footer-brand"><span class="brand-mark">MC</span> MoraConnect</span>
                <p>Technical writing, build logs and engineering notes by University of Moratuwa students.</p>
            </div>

            <div class="footer-col">
                <h4>Topics</h4>
                <ul>
                    <?php foreach ($footerTopics as $topic): ?>
                        <li><a href="<?= e(url('topics/' . Post::slugFor($topic))) ?>"><?= e($topic) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Platform</h4>
                <ul>
                    <li><a href="<?= e(url()) ?>">All articles</a></li>
                    <li><a href="<?= e(url('radar')) ?>">Radar</a></li>
                    <?php if (Auth::check()): ?>
                        <li><a href="<?= e(url('posts/create')) ?>">Write an article</a></li>
                        <li><a href="<?= e(url('profile')) ?>">Your articles</a></li>
                    <?php else: ?>
                        <li><a href="<?= e(url('register')) ?>">Create an account</a></li>
                        <li><a href="<?= e(url('login')) ?>">Sign in</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="footer-col">
                <h4>University</h4>
                <ul>
                    <li><a href="https://uom.lk" rel="noopener">University of Moratuwa</a></li>
                    <li><a href="https://uom.lk/itfac" rel="noopener">Faculty Of Information Technology</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <span>&copy; <?= date('Y') ?> MoraConnect &middot; University of Moratuwa</span>
            <span>Built with PHP <?= e(PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION) ?></span>
        </div>
    </div>
</footer>

<script src="<?= e(asset('js/script.js')) ?>"></script>
</body>
</html>
