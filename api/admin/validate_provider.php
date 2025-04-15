<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    respond('error', 'Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond('error', 'Method not allowed');
}

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $data['user_id'] ?? 0;

if ($user_id <= 0) {
    respond('error', 'Invalid user ID');
}

$db = (new Database())->connect();
try {
    // Start transaction to ensure both updates are atomic
    $db->beginTransaction();

    // Update users table
    $stmt = $db->prepare("UPDATE users SET is_validated = 1 WHERE id = ? AND role = 'provider'");
    $stmt->execute([$user_id]);

    if ($stmt->rowCount() === 0) {
        $db->rollBack();
        respond('error', 'Provider not found or already validated');
    }

    // Insert into providers table if not exists
    $stmt = $db->prepare("INSERT IGNORE INTO providers (user_id, company_name, location) VALUES (?, 'Default Company', 'Default Location')");
    $stmt->execute([$user_id]);

    // Commit transaction
    $db->commit();

    respond('success', 'Provider validated successfully');
} catch (PDOException $e) {
    $db->rollBack();
    respond('error', 'Database error: ' . $e->getMessage());
}
?>