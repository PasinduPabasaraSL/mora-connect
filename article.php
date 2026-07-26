<?php
require 'includes/auth.php';
require 'config/database.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT blogPost.*, users.username
    FROM blogPost
    JOIN users ON blogPost.user_id = users.id
    WHERE blogPost.id = ?
");
$stmt->execute([$id]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    require 'includes/header.php';
    echo "<p>Article not found.</p>";
    require 'includes/footer.php';
    exit;
}

function estimateReadingTime($content) {
    $words = str_word_count(strip_tags($content));
    return max(1, round($words / 200)) . " min read";
}

$isOwner = isLoggedIn() && $_SESSION['user_id'] == $post['user_id'];

require 'includes/header.php';
?>

<article class="reading-column">
    <span class="category-chip"><?= htmlspecialchars($post['category']) ?></span>
    <h1><?= htmlspecialchars($post['title']) ?></h1>

    <div class="article-meta ui-metadata" style="margin-bottom: var(--space-md);">
        <span>By <?= htmlspecialchars($post['username']) ?></span>
        <span>&bull;</span>
        <span><?= date('M j, Y', strtotime($post['created_at'])) ?></span>
        <span>&bull;</span>
        <span><?= estimateReadingTime($post['content']) ?></span>
    </div>

    <div class="text-lead" style="color: var(--color-text); white-space: pre-wrap;"><?= nl2br(htmlspecialchars($post['content'])) ?></div>

    <?php if ($isOwner): ?>
        <div style="margin-top: var(--space-lg); display:flex; gap:8px;">
            <a href="edit-post.php?id=<?= $post['id'] ?>" class="btn btn-secondary">Edit</a>
            <form method="POST" action="delete-post.php" onsubmit="return confirm('Delete this article? This cannot be undone.');">
                <input type="hidden" name="id" value="<?= $post['id'] ?>">
                <button type="submit" class="btn btn-danger">Delete</button>
            </form>
        </div>
    <?php endif; ?>
</article>

<?php require 'includes/footer.php'; ?>