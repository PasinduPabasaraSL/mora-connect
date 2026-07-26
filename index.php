<?php
require 'includes/auth.php';
require 'config/database.php';

// Fetch latest posts with author username, newest first
$stmt = $pdo->query("
    SELECT blogPost.*, users.username
    FROM blogPost
    JOIN users ON blogPost.user_id = users.id
    ORDER BY blogPost.created_at DESC
");
$posts = $stmt->fetchAll();

// Simple reading-time estimate: ~200 words per minute
function estimateReadingTime($content) {
    $words = str_word_count(strip_tags($content));
    $minutes = max(1, round($words / 200));
    return $minutes . " min read";
}

require 'includes/header.php';
?>

<section class="hero-section">
    <div class="hero-inner">
        <h1>Ideas that matter, published by the next generation.</h1>
        <p class="text-lead">MoraConnect is the publishing platform for University of Moratuwa students.</p>
        <div class="hero-actions">
            <a href="#latest" class="btn btn-primary">Start Reading</a>
            <?php if (!isLoggedIn()): ?>
                <a href="register.php" class="btn btn-secondary">Become a Writer</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<h3 id="latest" class="ui-label" style="border-bottom:1px solid var(--color-border); padding-bottom:8px; margin-bottom: var(--space-md);">
    Latest Publications
</h3>

<div class="articles-grid">
    <?php if (empty($posts)): ?>
        <p>No articles published yet. <?php if (isLoggedIn()): ?><a href="create-post.php">Write the first one</a>.<?php endif; ?></p>
    <?php endif; ?>

    <?php
    // Deterministic gradient per category so cards have visual weight
    // even before real cover images are added.
    $categoryGradients = [
        'Technology'   => 'linear-gradient(135deg, #2e4d44, #45655b)',
        'Philosophy'   => 'linear-gradient(135deg, #44474a, #74777a)',
        'Psychology'   => 'linear-gradient(135deg, #12181b, #42484b)',
        'Data Science' => 'linear-gradient(135deg, #1a1c1c, #595f63)',
        'Architecture' => 'linear-gradient(135deg, #2e4d44, #12181b)',
        'Other'        => 'linear-gradient(135deg, #74777a, #c4c7c9)',
    ];
    ?>

    <?php foreach ($posts as $post): ?>
        <div class="article-card">
            <div class="card-thumb" style="background: <?= $categoryGradients[$post['category']] ?? $categoryGradients['Other'] ?>;">
                <span><?= htmlspecialchars($post['category']) ?></span>
            </div>
            <span class="category-chip"><?= htmlspecialchars($post['category']) ?></span>
            <h3><a href="article.php?id=<?= (int)$post['id'] ?>"><?= htmlspecialchars($post['title']) ?></a></h3>
            <p class="excerpt"><?= htmlspecialchars(substr(strip_tags($post['content']), 0, 160)) ?>...</p>
            <div class="article-meta ui-metadata">
                <span><?= htmlspecialchars($post['username']) ?></span>
                <span>&bull;</span>
                <span><?= date('M j, Y', strtotime($post['created_at'])) ?></span>
                <span>&bull;</span>
                <span><?= estimateReadingTime($post['content']) ?></span>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require 'includes/footer.php'; ?>