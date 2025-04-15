<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

if (!isLoggedIn() || !isProvider()) {
    respond('error', 'Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond('error', 'Method not allowed');
}

$data = json_decode(file_get_contents('php://input'), true);
$service_id = $data['service_id'] ?? 0;

if (!$service_id) {
    respond('error', 'Service ID required');
}

$db = (new Database())->connect();
try {
    // Verify service belongs to provider
    $stmt = $db->prepare("SELECT s.id FROM services s JOIN providers p ON s.provider_id = p.id WHERE s.id = ? AND p.user_id = ?");
    $stmt->execute([$service_id, $_SESSION['user_id']]);
    if ($stmt->rowCount() === 0) {
        respond('error', 'Service not found or unauthorized');
    }

    // Delete service
    $stmt = $db->prepare("DELETE FROM services WHERE id = ?");
    $stmt->execute([$service_id]);

    respond('success', 'Service deleted successfully');
} catch (PDOException $e) {
    respond('error', 'Database error: ' . $e->getMessage());
}
?>