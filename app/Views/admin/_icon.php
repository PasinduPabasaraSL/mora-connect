<?php

/**
 * Sidebar icons, kept in one place so the nav loop stays readable.
 *
 * @var string $name
 */

$paths = [
    'overview'  => ['M3 13h6V3H3zM13 21h6V11h-6zM3 21h6v-5H3zM13 8h6V3h-6z'],
    'content'   => ['M5 3h11l4 4v14H5z', 'M9 9h7M9 13h7M9 17h4'],
    'writers'   => ['M16 20v-1.5a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4V20', 'M9.5 10.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z', 'M19 20v-1a3.5 3.5 0 0 0-2.5-3.35', 'M15.5 4a3.5 3.5 0 0 1 0 6.5'],
    'radar'     => ['M12 12a9 9 0 0 1 9 9', 'M12 12a5 5 0 0 1 5 5', 'M12 12v9H3a9 9 0 0 1 9-9z', 'M12 3a9 9 0 0 1 9 9'],
    'community' => ['M12 21s-7-4.35-7-9.5A4.5 4.5 0 0 1 12 8a4.5 4.5 0 0 1 7 3.5C19 16.65 12 21 12 21z'],
    'site'      => ['M3 12h18', 'M12 3a15 15 0 0 1 0 18', 'M12 3a15 15 0 0 0 0 18', 'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18z'],
];

$shape = $paths[$name] ?? $paths['overview'];
?>
<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor"
     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <?php foreach ($shape as $path): ?>
        <path d="<?= e($path) ?>"></path>
    <?php endforeach; ?>
</svg>
