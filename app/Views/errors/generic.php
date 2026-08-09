<?php

/** @var int $status */
/** @var string $message */
?>
<div class="error-page">
    <div class="error-code"><?= e($status ?? 500) ?></div>
    <p class="lead"><?= e($message ?? '') !== '' ? e($message) : 'Something went wrong.' ?></p>
    <a href="<?= e(url()) ?>" class="btn btn-primary">Back to articles</a>
</div>
