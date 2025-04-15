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
$service_type = $data['service_type'] ?? '';
$title = $data['title'] ?? '';
$description = $data['description'] ?? '';
$location = $data['location'] ?? '';
$price = $data['price'] ?? 0;

$allowed_types = ['composting', 'recycling', 'waste_collection', 'consulting'];
if (empty($title) || empty($location) || !in_array($service_type, $allowed_types)) {
    respond('error', 'Invalid input');
}

$db = (new Database())->connect();
try {
    // Get provider_id from providers table
    $stmt = $db->prepare("SELECT id FROM providers WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $provider = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$provider) {
        respond('error', 'Provider profile not found');
    }

    // Insert service
    $stmt = $db->prepare("INSERT INTO services (provider_id, service_type, title, description, location, price) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$provider['id'], $service_type, $title, $description, $location, $price]);

    respond('success', 'Service created successfully');
} catch (PDOException $e) {
    respond('error', 'Database error: ' . $e->getMessage());
}
?>