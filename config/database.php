<?php
/**
 * config/database.php
 *
 * Central database connection file for MoraConnect.
 * Every page includes this file to get a ready-to-use PDO
 * connection in the $pdo variable.
 *
 * IMPORTANT FOR DEPLOYMENT:
 * When you upload to InfinityFree / 000WebHost, change the
 * values below to the credentials your host gives you
 * (usually visible in their control panel / cPanel).
 */

// ----- Database credentials -----
// Local development (XAMPP/WAMP) defaults shown below.
define('DB_HOST', 'localhost');
define('DB_NAME', 'moraconnect');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    // DSN = Data Source Name. utf8mb4 supports full unicode (emojis, etc).
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

    // PDO options:
    // - ERRMODE_EXCEPTION: throw exceptions on SQL errors instead of silent failures
    // - FETCH_ASSOC: fetch() returns associative arrays like ['id' => 1, 'title' => '...']
    // - EMULATE_PREPARES false: use REAL prepared statements (better security)
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

} catch (PDOException $e) {
    // In production you would log this instead of showing it to users.
    // For a university assignment, showing the message helps you debug.
    die("Database connection failed: " . $e->getMessage());
}
