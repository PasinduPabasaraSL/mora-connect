<?php

    session_start();

    /**
     * Returns true if a user is currently logged in.
     */
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    /**
     * Call this at the top of any page that should be private.
     * Redirects to login.php if no one is logged in.
     */
    function requireLogin() {
        if (!isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }

    /**
     * Convenience getter for the logged-in user's id (or null).
     */
    function currentUserId() {
        return $_SESSION['user_id'] ?? null;
    }