<?php

/** @var int $status */
/** @var string $message */
?>
<div class="reading-column error-page">
    <h1><?= e($status ?? 500) ?></h1>
    <p class="text-lead"><?= e($message ?? '') !== '' ? e($message) : 'Something went wrong.' ?></p>
    <a href="<?= e(url()) ?>" class="btn btn-primary">Back to Explore</a>
</div>
