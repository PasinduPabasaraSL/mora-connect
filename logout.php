<?php
require 'includes/auth.php';

// Clear all session data and destroy the session entirely
$_SESSION = [];
session_unset();
session_destroy();

header('Location: login.php');
exit;