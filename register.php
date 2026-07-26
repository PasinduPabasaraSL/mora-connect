<?php
require 'includes/auth.php';
require 'config/database.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Grab and trim input
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // 2. Validate
    if ($username === '' || strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }
    if ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    }

    // 3. Check for existing username/email (only if no errors so far)
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $errors[] = "That username or email is already registered.";
        }
    }

    // 4. Insert new user
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'student')"
        );
        $stmt->execute([$username, $email, $hashedPassword]);

        // Log the user straight in after registering
        $_SESSION['user_id']  = $pdo->lastInsertId();
        $_SESSION['username'] = $username;
        $_SESSION['role']     = 'student';

        header('Location: index.php');
        exit;
    }
}

require 'includes/header.php';
?>

<div class="auth-page">
    <div class="auth-card">
        <div class="brand-mark"><span>MC</span></div>
        <h2>Register</h2>
        <p class="subtitle">Join the academic conversation.</p>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>

        <form method="POST" action="register.php">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control"
                       value="<?= htmlspecialchars($username ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control"
                       value="<?= htmlspecialchars($email ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;">Create Account</button>
        </form>

        <p class="switch-link">Already have an account? <a href="login.php">Sign in</a></p>
    </div>
</div>

<?php require 'includes/footer.php'; ?>