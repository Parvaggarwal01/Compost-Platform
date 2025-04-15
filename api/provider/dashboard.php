<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

if (!isLoggedIn() || !isProvider()) {
    respond('error', 'Unauthorized');
}

$db = (new Database())->connect();
try {
    // Get provider profile
    $stmt = $db->prepare("SELECT company_name, location, contact_number, description FROM providers WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get provider services
    $stmt = $db->prepare("SELECT s.* FROM services s JOIN providers p ON s.provider_id = p.id WHERE p.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get service requests
    $stmt = $db->prepare("SELECT sr.*, s.title, u.username FROM service_requests sr JOIN services s ON sr.service_id = s.id JOIN users u ON sr.user_id = u.id JOIN providers p ON s.provider_id = p.id WHERE p.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    respond('success', 'Dashboard data retrieved', [
        'profile' => $profile,
        'services' => $services,
        'requests' => $requests
    ]);
} catch (PDOException $e) {
    respond('error', 'Database error: ' . $e->getMessage());
}
?>