<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

if (!isLoggedIn()) {
    respond('error', 'Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond('error', 'Method not allowed');
}

$data = json_decode(file_get_contents('php://input'), true);
$service_id = $data['service_id'] ?? 0;

$db = (new Database())->connect();
try {
    $stmt = $db->prepare("INSERT INTO service_requests (user_id, service_id) VALUES (?, ?)");
    $stmt->execute([$_SESSION['user_id'], $service_id]);

    respond('success', 'Service requested successfully');
} catch (PDOException $e) {
    respond('error', 'Database error: ' . $e->getMessage());
}
?>