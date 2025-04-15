<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

if (!isLoggedIn()) {
    respond('error', 'Unauthorized');
}

$db = (new Database())->connect();
try {
    $stmt = $db->prepare("SELECT id, username, email, role, is_validated FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($_SESSION['role'] === 'provider') {
        $stmt = $db->prepare("SELECT company_name, location, contact_number, description FROM providers WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $provider = $stmt->fetch(PDO::FETCH_ASSOC);
        $user['provider'] = $provider ?: null;
    }

    respond('success', 'Profile retrieved', $user);
} catch (PDOException $e) {
    respond('error', 'Database error: ' . $e->getMessage());
}
?>