<?php
require 'includes/auth.php';
require 'config/database.php';
requireLogin();

$stmt = $pdo->prepare("
    SELECT * FROM blogPost WHERE user_id = ? ORDER BY created_at DESC
");
$stmt->execute([currentUserId()]);
$myPosts = $stmt->fetchAll();

require 'includes/header.php';
?>

<div class="reading-column">
    <h2><?= htmlspecialchars($_SESSION['username']) ?></h2>
    <p class="ui-metadata"><?= count($myPosts) ?> published article<?= count($myPosts) === 1 ? '' : 's' ?></p>

    <?php foreach ($myPosts as $post): ?>
        <div class="article-card" style="margin-bottom: 16px;">
            <span class="category-chip"><?= htmlspecialchars($post['category']) ?></span>
            <h3><a href="article.php?id=<?= $post['id'] ?>"><?= htmlspecialchars($post['title']) ?></a></h3>
            <div class="article-meta ui-metadata">
                <span><?= date('M j, Y', strtotime($post['created_at'])) ?></span>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require 'includes/footer.php'; ?>