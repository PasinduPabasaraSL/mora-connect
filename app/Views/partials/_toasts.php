<?php

/**
 * Flash messages, shown as toasts in the corner.
 *
 * These sit outside <main> on purpose: a banner in the content flow pushed the
 * whole page down for one load and then let it snap back on the next. Floating
 * them means the layout never moves, and the script fades them out once they
 * have been read.
 *
 * Validation errors are deliberately not routed through here — they belong
 * beside the field that caused them and must not disappear on a timer.
 *
 * @var string|null $success
 * @var string|null $error
 */

$toasts = [];

if ($success !== null && trim($success) !== '') {
    $toasts[] = ['kind' => 'success', 'message' => $success];
}

if ($error !== null && trim($error) !== '') {
    $toasts[] = ['kind' => 'error', 'message' => $error];
}

if ($toasts === []) {
    return;
}
?>
<div class="toast-stack" id="toastStack">
    <?php foreach ($toasts as $toast): ?>
        <?php /* A failure is announced immediately; a confirmation waits for a
                 pause in whatever the screen reader is already saying. */ ?>
        <div class="toast toast-<?= e($toast['kind']) ?>"
             role="<?= $toast['kind'] === 'error' ? 'alert' : 'status' ?>"
             aria-live="<?= $toast['kind'] === 'error' ? 'assertive' : 'polite' ?>">
            <span class="toast-icon" aria-hidden="true">
                <?php if ($toast['kind'] === 'success'): ?>
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 6 9 17l-5-5"></path>
                    </svg>
                <?php else: ?>
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 8v4M12 16h.01"></path>
                        <circle cx="12" cy="12" r="9"></circle>
                    </svg>
                <?php endif; ?>
            </span>

            <p><?= e($toast['message']) ?></p>

            <button type="button" class="toast-close" data-toast-close aria-label="Dismiss this message">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.2" stroke-linecap="round" aria-hidden="true">
                    <path d="M6 6l12 12M18 6L6 18"></path>
                </svg>
            </button>
        </div>
    <?php endforeach; ?>
</div>
