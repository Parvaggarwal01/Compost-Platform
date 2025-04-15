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
$company_name = sanitize($data['company_name'] ?? '');
$location = sanitize($data['location'] ?? '');
$contact_number = sanitize($data['contact_number'] ?? '');
$description = sanitize($data['description'] ?? '');

$db = (new Database())->connect();
try {
    $updates = [];
    $params = [];

    if (!empty($company_name)) {
        $updates[] = "company_name = ?";
        $params[] = $company_name;
    }
    if (!empty($location)) {
        $updates[] = "location = ?";
        $params[] = $location;
    }
    if (!empty($contact_number)) {
        $updates[] = "contact_number = ?";
        $params[] = $contact_number;
    }
    if (!empty($description)) {
        $updates[] = "description = ?";
        $params[] = $description;
    }

    if (empty($updates)) {
        respond('error', 'No updates provided');
    }

    $params[] = $_SESSION['user_id'];
    $query = "UPDATE providers SET " . implode(', ', $updates) . " WHERE user_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute($params);

    respond('success', 'Provider profile updated successfully');
} catch (PDOException $e) {
    respond('error', 'Database error: ' . $e->getMessage());
}
?>