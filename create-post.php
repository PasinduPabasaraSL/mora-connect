<?php
require 'includes/auth.php';
require 'config/database.php';
requireLogin(); // must be logged in to write

$errors = [];
$title = $content = $category = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = trim($_POST['title'] ?? '');
    $content  = trim($_POST['content'] ?? '');
    $category = trim($_POST['category'] ?? '');

    if ($title === '') $errors[] = "Title is required.";
    if ($content === '') $errors[] = "Content cannot be empty.";
    if ($category === '') $errors[] = "Please choose a category.";

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            "INSERT INTO blogPost (user_id, title, content, category) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([currentUserId(), $title, $content, $category]);

        header('Location: article.php?id=' . $pdo->lastInsertId());
        exit;
    }
}

require 'includes/header.php';
?>

<div class="reading-column">
    <h2>Write a new post</h2>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endforeach; ?>

    <form method="POST" action="create-post.php">
        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" class="form-control" value="<?= htmlspecialchars($title) ?>" required>
        </div>

        <div class="form-group">
            <label for="category">Category</label>
            <select id="category" name="category" class="form-control" required>
                <option value="">Select a category</option>
                <?php foreach (['Technology', 'Philosophy', 'Psychology', 'Data Science', 'Architecture', 'Other'] as $cat): ?>
                    <option value="<?= $cat ?>" <?= $category === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="content">Content</label>
            <textarea id="content" name="content" class="form-control" required><?= htmlspecialchars($content) ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Publish</button>
    </form>
</div>

<?php require 'includes/footer.php'; ?>