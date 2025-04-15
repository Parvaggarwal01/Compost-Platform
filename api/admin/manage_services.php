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
$service_id = $data['service_id'] ?? 0;
$action = $data['action'] ?? ''; // 'approve' or 'remove'

if (!$service_id || !in_array($action, ['approve', 'remove'])) {
    respond('error', 'Invalid input');
}

$db = (new Database())->connect();
try {
    if ($action === 'approve') {
        $stmt = $db->prepare("UPDATE services SET is_approved = 1 WHERE id = ?");
        $stmt->execute([$service_id]);
    } else {
        $stmt = $db->prepare("DELETE FROM services WHERE id = ?");
        $stmt->execute([$service_id]);
    }

    respond('success', "Service $action successfully");
} catch (PDOException $e) {
    respond('error', 'Database error: ' . $e->getMessage());
}
?>