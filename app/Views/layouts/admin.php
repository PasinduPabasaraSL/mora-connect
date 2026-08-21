<?php

use App\Core\AdminAuth;
use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;

/** @var string $content */
/** @var string $title */
/** @var array<string, mixed> $data */

// The sign-in screen borrows the same head and tokens but none of the furniture
$chrome = ($data['chrome'] ?? true) !== false;
$section = (string) ($data['section'] ?? '');
$heading = (string) ($data['heading'] ?? '');

$successFlash = Session::pullFlash('success');
$errorFlash   = Session::pullFlash('error');

$nav = [
    'overview'  => ['label' => 'Overview',  'path' => 'admin'],
    'content'   => ['label' => 'Content',   'path' => 'admin/content'],
    'writers'   => ['label' => 'Writers',   'path' => 'admin/writers'],
    'radar'     => ['label' => 'Radar',     'path' => 'admin/radar'],
    'community' => ['label' => 'Community', 'path' => 'admin/community'],
];
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?></title>

    <?php /* An admin screen should never be indexed, whatever robots.txt says */ ?>
    <meta name="robots" content="noindex, nofollow">

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

    <?php /* style.css first, for the tokens and the shared buttons and badges
             the panel reuses; admin.css only adds the shell on top. */ ?>
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="admin-body<?= $chrome ? '' : ' admin-plain' ?>">

<?php if (!$chrome): ?>
    <main class="admin-gate">
        <?= $content ?>
    </main>
<?php else: ?>
    <div class="admin-shell">
        <?php /* Checked by CSS on small screens, which turns the sidebar into a
                 slide-over. No JavaScript needed for the panel to be usable. */ ?>
        <input type="checkbox" id="adminNavToggle" class="admin-nav-state" hidden>

        <aside class="admin-side">
            <a class="admin-brand" href="<?= e(url('admin')) ?>">
                <span class="brand-mark">MC</span>
                <span class="admin-brand-text">
                    <strong>MoraConnect</strong>
                    <em>Admin</em>
                </span>
            </a>

            <nav class="admin-nav" aria-label="Admin sections">
                <?php foreach ($nav as $key => $item): ?>
                    <a href="<?= e(url($item['path'])) ?>"
                       class="admin-nav-link<?= $section === $key ? ' is-active' : '' ?>"
                       <?= $section === $key ? 'aria-current="page"' : '' ?>>
                        <?php View::partial('admin/_icon', ['name' => $key]); ?>
                        <span><?= e($item['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="admin-side-foot">
                <a class="admin-nav-link" href="<?= e(url()) ?>">
                    <?php View::partial('admin/_icon', ['name' => 'site']); ?>
                    <span>View the site</span>
                </a>

                <div class="admin-who">
                    <span class="admin-who-dot" aria-hidden="true"></span>
                    <span><?= e(AdminAuth::user()) ?></span>
                </div>

                <form method="POST" action="<?= e(url('admin/logout')) ?>">
                    <?= Csrf::field() ?>
                    <button type="submit" class="btn btn-block">Sign out</button>
                </form>
            </div>
        </aside>

        <label for="adminNavToggle" class="admin-scrim" aria-hidden="true"></label>

        <div class="admin-main">
            <header class="admin-top">
                <label for="adminNavToggle" class="admin-burger" role="button"
                       tabindex="0" aria-label="Show sections">
                    <span></span><span></span><span></span>
                </label>

                <div class="admin-top-title">
                    <h1><?= e($heading) ?></h1>
                    <p><?= e(date('l j F Y')) ?></p>
                </div>

                <button type="button" class="icon-btn" id="themeToggle"
                        aria-label="Toggle colour theme" aria-pressed="false">
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
            </header>

            <main class="admin-content">
                <?= $content ?>
            </main>
        </div>
    </div>
<?php endif; ?>

<?php View::partial('partials/_toasts', [
    'success' => $successFlash,
    'error'   => $errorFlash,
]); ?>

<script src="<?= e(asset('js/script.js')) ?>"></script>
</body>
</html>
