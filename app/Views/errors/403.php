<?php

/** @var string $message */
?>
<div class="reading-column error-page">
    <h1>403</h1>
    <p class="text-lead"><?= e($message ?? '') !== '' ? e($message) : 'You do not have permission to do that.' ?></p>
    <a href="<?= e(url()) ?>" class="btn btn-primary">Back to Explore</a>
</div>
