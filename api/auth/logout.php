<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

// Unset all session variables
$_SESSION = [];

// Destroy session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

respond('success', 'Logged out successfully');
?>
