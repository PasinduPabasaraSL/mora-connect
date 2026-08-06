<?php

/** @var string $message */
?>
<div class="reading-column error-page">
    <h1>404</h1>
    <p class="text-lead"><?= e($message ?? '') !== '' ? e($message) : 'We could not find that page.' ?></p>
    <a href="<?= e(url()) ?>" class="btn btn-primary">Back to Explore</a>
</div>
