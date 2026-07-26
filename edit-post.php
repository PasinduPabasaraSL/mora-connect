<?php
require 'includes/auth.php';
require 'config/database.php';
requireLogin();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM blogPost WHERE id = ?");
$stmt->execute([$id]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    die("Article not found.");
}

// ---- Authorization check ----
// A user must NOT be able to edit another user's blog post.
if ($post['user_id'] != currentUserId()) {
    http_response_code(403);
    require 'includes/header.php';
    echo '<div class="reading-column"><div class="alert alert-error">You do not have permission to edit this article.</div></div>';
    require 'includes/footer.php';
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = trim($_POST['title'] ?? '');
    $content  = trim($_POST['content'] ?? '');
    $category = trim($_POST['category'] ?? '');

    if ($title === '') $errors[] = "Title is required.";
    if ($content === '') $errors[] = "Content cannot be empty.";
    if ($category === '') $errors[] = "Please choose a category.";

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            "UPDATE blogPost SET title = ?, content = ?, category = ? WHERE id = ? AND user_id = ?"
        );
        $stmt->execute([$title, $content, $category, $id, currentUserId()]);

        header('Location: article.php?id=' . $id);
        exit;
    }

    $post['title'] = $title;
    $post['content'] = $content;
    $post['category'] = $category;
}

require 'includes/header.php';
?>

<div class="reading-column">
    <h2>Edit post</h2>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endforeach; ?>

    <form method="POST" action="edit-post.php?id=<?= $id ?>">
        <input type="hidden" name="id" value="<?= $id ?>">

        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" class="form-control" value="<?= htmlspecialchars($post['title']) ?>" required>
        </div>

        <div class="form-group">
            <label for="category">Category</label>
            <select id="category" name="category" class="form-control" required>
                <?php foreach (['Technology', 'Philosophy', 'Psychology', 'Data Science', 'Architecture', 'Other'] as $cat): ?>
                    <option value="<?= $cat ?>" <?= $post['category'] === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="content">Content</label>
            <textarea id="content" name="content" class="form-control" required><?= htmlspecialchars($post['content']) ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Save Changes</button>
    </form>
</div>

<?php require 'includes/footer.php'; ?>