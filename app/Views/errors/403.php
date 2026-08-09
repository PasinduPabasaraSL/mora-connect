<?php

/** @var string $message */
?>
<div class="error-page">
    <div class="error-code">403</div>
    <p class="lead"><?= e($message ?? '') !== '' ? e($message) : 'You do not have permission to do that.' ?></p>
    <a href="<?= e(url()) ?>" class="btn btn-primary">Back to articles</a>
</div>
