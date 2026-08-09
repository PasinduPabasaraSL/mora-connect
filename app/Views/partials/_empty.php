<?php

/**
 * Empty-state block.
 *
 * @var string      $message
 * @var string|null $actionUrl
 * @var string|null $actionText
 */
?>
<div class="empty">
    <h3>Nothing here yet</h3>
    <p><?= e($message) ?></p>
    <?php if (!empty($actionUrl) && !empty($actionText)): ?>
        <a href="<?= e($actionUrl) ?>" class="btn btn-primary"><?= e($actionText) ?></a>
    <?php endif; ?>
</div>
