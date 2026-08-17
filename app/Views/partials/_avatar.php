<?php

use App\Models\User;

/**
 * Somebody's profile picture, or their initials when there is none.
 *
 * The single place that decides what a user looks like, so the header, the
 * bylines, the author page and the settings preview can never disagree.
 *
 * @var array<string, mixed> $user  a users row, or any post row carrying the
 *                                  author columns Post::AUTHOR selects
 * @var string               $size  sm | md | lg | xl
 * @var string               $class extra classes for the wrapper
 */

$size  = $size ?? 'md';
$class = $class ?? '';

$picture = User::avatarFor($user);
$name    = User::nameFor($user);

// Two letters from the name, which reads better than one and still fits
$initials = mb_strtoupper(mb_substr($name, 0, 2));
?>
<span class="avatar avatar-<?= e($size) ?><?= $class === '' ? '' : ' ' . e($class) ?>">
    <?php if ($picture !== null): ?>
        <?php /* Decorative: the name is always written next to it, and repeating
                 it here would have a screen reader say it twice. A picture that
                 fails to load falls back to the initials underneath. */ ?>
        <img src="<?= e($picture) ?>" alt="" loading="lazy" decoding="async"
             width="96" height="96" data-avatar-img>
    <?php endif; ?>
    <span class="avatar-initials" aria-hidden="true"><?= e($initials) ?></span>
</span>
