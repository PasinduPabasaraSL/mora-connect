<?php

use App\Core\View;
use App\Models\Post;
use App\Models\User;

/**
 * The header shared by the private profile page and the public author page: a
 * cover band, the avatar overlapping it, and the writer's details.
 *
 * Both pages show the same person, so they show it the same way; only the
 * actions differ, which arrive as $actions.
 *
 * @var array<string, mixed> $user
 * @var string               $actions  rendered buttons for the top right
 * @var list<array{num: string, label: string}> $tiles
 */

$actions = $actions ?? '';
$tiles   = $tiles ?? [];

$name      = User::nameFor($user);
$username  = (string) ($user['username'] ?? '');
$headline  = trim((string) ($user['headline'] ?? ''));
$bio       = trim((string) ($user['bio'] ?? ''));
$interests = User::interestsFor($user);

// Faculty, programme and year read as one line, with only the parts that are set
$academic = array_filter([
    trim((string) ($user['programme'] ?? '')),
    trim((string) ($user['faculty'] ?? '')),
    trim((string) ($user['study_year'] ?? '')),
], static fn (string $part): bool => $part !== '');

$links = array_filter([
    'website'  => trim((string) ($user['website'] ?? '')),
    'github'   => trim((string) ($user['github'] ?? '')),
    'linkedin' => trim((string) ($user['linkedin'] ?? '')),
], static fn (string $value): bool => $value !== '');
?>
<header class="profile-head">
    <?php /* Decorative band. Tinted from the accent rather than an uploaded
             cover image, so every profile has one from the moment it exists. */ ?>
    <div class="profile-cover" aria-hidden="true"></div>

    <div class="profile-identity">
        <?php View::partial('partials/_avatar', ['user' => $user, 'size' => 'xl', 'class' => 'profile-avatar']); ?>

        <div class="profile-details">
            <h1><?= e($name) ?></h1>

            <?php /* The handle is shown even when a display name is set, because
                     it is what the URL of this page uses. */ ?>
            <p class="profile-handle">@<?= e($username) ?></p>

            <?php if ($headline !== ''): ?>
                <p class="profile-headline"><?= e($headline) ?></p>
            <?php endif; ?>

            <?php if ($academic !== []): ?>
                <p class="profile-academic"><?= e(implode(' &middot; ', $academic)) ?></p>
            <?php endif; ?>

            <?php if ($bio !== ''): ?>
                <p class="profile-bio"><?= e($bio) ?></p>
            <?php endif; ?>

            <?php if ($links !== []): ?>
                <ul class="profile-links">
                    <?php if (isset($links['website'])): ?>
                        <li>
                            <a href="<?= e($links['website']) ?>" rel="me noopener noreferrer" target="_blank">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="1.9" stroke-linecap="round" aria-hidden="true">
                                    <circle cx="12" cy="12" r="9"></circle>
                                    <path d="M3.6 9h16.8M3.6 15h16.8M12 3c2.5 2.4 2.5 15.6 0 18M12 3c-2.5 2.4-2.5 15.6 0 18"></path>
                                </svg>
                                <?php /* The host alone, because a full URL in a
                                         list of links is mostly punctuation. */ ?>
                                <?= e(preg_replace('#^www\.#', '', (string) parse_url($links['website'], PHP_URL_HOST)) ?: $links['website']) ?>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (isset($links['github'])): ?>
                        <li>
                            <a href="https://github.com/<?= e($links['github']) ?>" rel="me noopener noreferrer" target="_blank">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M9 19c-4 1.2-4-2.2-5.5-2.8M15 21v-3.4a2.9 2.9 0 0 0-.8-2.3c2.7-.3 5.4-1.4 5.4-6a4.6 4.6 0 0 0-1.3-3.2 3.4 3.4 0 0 0-.1-3.2s-1.4-.4-4.2 1.6a10.6 10.6 0 0 0-5.6 0C4.6 2.5 3.2 2.9 3.2 2.9a3.4 3.4 0 0 0-.1 3.2A4.6 4.6 0 0 0 1.8 9.3c0 4.6 2.7 5.7 5.4 6a2.9 2.9 0 0 0-.8 2.3V21"></path>
                                </svg>
                                <?= e($links['github']) ?>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (isset($links['linkedin'])): ?>
                        <li>
                            <a href="<?= e($links['linkedin']) ?>" rel="me noopener noreferrer" target="_blank">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M4.5 9.5V20M4.5 4.6v.1"></path>
                                    <path d="M10 20v-6a3.5 3.5 0 0 1 7 0v6"></path>
                                    <path d="M10 9.5V20"></path>
                                </svg>
                                LinkedIn
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>

            <?php if ($interests !== []): ?>
                <div class="profile-interests">
                    <?php foreach ($interests as $topic): ?>
                        <a class="tag-chip" href="<?= e(url('topics/' . Post::slugFor($topic))) ?>"><?= e($topic) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($actions !== ''): ?>
            <div class="profile-actions"><?= $actions ?></div>
        <?php endif; ?>
    </div>

    <?php if ($tiles !== []): ?>
        <div class="stats profile-stats">
            <?php foreach ($tiles as $tile): ?>
                <div class="stat">
                    <div class="num"><?= e($tile['num']) ?></div>
                    <div class="label"><?= e($tile['label']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</header>
