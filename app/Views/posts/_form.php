<?php

use App\Core\Csrf;
use App\Models\Post;

/**
 * Shared create/edit form.
 *
 * @var array<string, mixed> $post    current values
 * @var string               $action  form target
 * @var string               $submit  button label
 * @var string               $heading
 * @var list<string>         $errors
 */

$errors = $errors ?? [];
?>
<div class="reading">
    <div class="section-head">
        <h2><?= e($heading) ?></h2>
    </div>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endforeach; ?>

    <div class="form-card">
        <form method="POST" action="<?= e($action) ?>">
            <?= Csrf::field() ?>

            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" class="form-control"
                       value="<?= e($post['title'] ?? '') ?>" maxlength="255"
                       placeholder="What did you build, break or figure out?" required>
            </div>

            <div class="form-group">
                <label for="category">Topic</label>
                <select id="category" name="category" class="form-control" required>
                    <option value="">Select a topic</option>
                    <?php foreach (Post::categories() as $category): ?>
                        <option value="<?= e($category) ?>" <?= ($post['category'] ?? '') === $category ? 'selected' : '' ?>>
                            <?= e($category) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="image_url">Cover image URL <span class="muted">(optional)</span></label>
                <input type="url" id="image_url" name="image_url" class="form-control"
                       value="<?= e($post['image_url'] ?? '') ?>" maxlength="500"
                       placeholder="https://example.com/image.jpg">
                <p class="hint">Leave blank and a coloured topic card is used instead.</p>
            </div>

            <div class="form-group">
                <label for="content">Article</label>
                <textarea id="content" name="content" class="form-control" required><?= e($post['content'] ?? '') ?></textarea>
                <p class="hint">Line breaks are preserved exactly as you type them.</p>
            </div>

            <button type="submit" class="btn btn-primary"><?= e($submit) ?></button>
        </form>
    </div>
</div>
