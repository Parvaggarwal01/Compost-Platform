<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

if (isLoggedIn()) {
    respond('success', 'User is logged in', ['role' => $_SESSION['role']]);
} else {
    respond('error', 'Not logged in');
}
?>