<?php
require 'includes/auth.php';
require 'config/database.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);

    // Ownership check happens directly in the query:
    // this DELETE only affects a row if it belongs to the logged-in user.
    $stmt = $pdo->prepare("DELETE FROM blogPost WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, currentUserId()]);

    // Optional: you could check $stmt->rowCount() === 0 here to detect
    // "either the post doesn't exist, or it's not yours" and show a message.
}

header('Location: index.php');
exit;