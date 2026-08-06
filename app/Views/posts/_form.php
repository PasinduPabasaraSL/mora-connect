<?php

use App\Core\Csrf;
use App\Models\Post;

/**
 * Shared create/edit form.
 *
 * @var array<string, mixed> $post    current values
 * @var string               $action  form target
 * @var string               $submit  button label
 * @var list<string>         $errors
 */

$errors = $errors ?? [];
?>
<div class="reading-column">
    <h2><?= e($heading) ?></h2>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endforeach; ?>

    <form method="POST" action="<?= e($action) ?>">
        <?= Csrf::field() ?>

        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" class="form-control"
                   value="<?= e($post['title'] ?? '') ?>" maxlength="255" required>
        </div>

        <div class="form-group">
            <label for="category">Category</label>
            <select id="category" name="category" class="form-control" required>
                <option value="">Select a category</option>
                <?php foreach (Post::categories() as $category): ?>
                    <option value="<?= e($category) ?>" <?= ($post['category'] ?? '') === $category ? 'selected' : '' ?>>
                        <?= e($category) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="content">Content</label>
            <textarea id="content" name="content" class="form-control" required><?= e($post['content'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary"><?= e($submit) ?></button>
    </form>
</div>
