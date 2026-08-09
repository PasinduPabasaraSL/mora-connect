<?php

/** @var string $message */
?>
<div class="error-page">
    <div class="error-code">404</div>
    <p class="lead"><?= e($message ?? '') !== '' ? e($message) : 'We could not find that page.' ?></p>
    <a href="<?= e(url()) ?>" class="btn btn-primary">Back to articles</a>
</div>
