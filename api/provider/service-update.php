<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';

if (!isLoggedIn() || !isProvider()) {
    respond('error', 'Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond('error', 'Method not allowed');
}

$data = json_decode(file_get_contents('php://input'), true);
$service_id = $data['service_id'] ?? 0;
$service_type = sanitize($data['service_type'] ?? '');
$title = sanitize($data['title'] ?? '');
$description = sanitize($data['description'] ?? '');
$location = sanitize($data['location'] ?? '');
$price = $data['price'] ?? null;

if (!$service_id || empty($title) || empty($location) || !in_array($service_type, ALLOWED_SERVICE_TYPES)) {
    respond('error', 'Invalid input');
}

$db = (new Database())->connect();
try {
    // Verify service belongs to provider
    $stmt = $db->prepare("SELECT s.id FROM services s JOIN providers p ON s.provider_id = p.id WHERE s.id = ? AND p.user_id = ?");
    $stmt->execute([$service_id, $_SESSION['user_id']]);
    if ($stmt->rowCount() === 0) {
        respond('error', 'Service not found or unauthorized');
    }

    // Update service
    $updates = ["service_type = ?", "title = ?", "description = ?", "location = ?"];
    $params = [$service_type, $title, $description, $location];

    if ($price !== null) {
        $updates[] = "price = ?";
        $params[] = $price;
    }

    $params[] = $service_id;
    $query = "UPDATE services SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute($params);

    respond('success', 'Service updated successfully');
} catch (PDOException $e) {
    respond('error', 'Database error: ' . $e->getMessage());
}
?>