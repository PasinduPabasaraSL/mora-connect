<?php
require 'includes/auth.php';
require 'config/database.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? ''); // username OR email
    $password   = $_POST['password'] ?? '';

    if ($identifier === '' || $password === '') {
        $errors[] = "Please fill in all fields.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        // Important: password_verify() checks the plain password against
        // the bcrypt hash — never compare passwords with ==
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            header('Location: index.php');
            exit;
        } else {
            $errors[] = "Incorrect username/email or password.";
        }
    }
}

require 'includes/header.php';
?>

<div class="auth-page">
    <div class="auth-card">
        <div class="brand-mark"><span>MC</span></div>
        <h2>Sign In</h2>
        <p class="subtitle">Welcome back to MoraConnect.</p>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="identifier">Username or Email</label>
                <input type="text" id="identifier" name="identifier" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;">Sign In</button>
        </form>

        <p class="switch-link">Don't have an account? <a href="register.php">Create one</a></p>
    </div>
</div>

<?php require 'includes/footer.php'; ?>