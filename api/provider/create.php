<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'provider') {
    respond('error', 'Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond('error', 'Method not allowed');
}

$data = json_decode(file_get_contents('php://input'), true);
$required_fields = ['company_name', 'location'];
if (!validateInput($data, $required_fields)) {
    respond('error', 'Missing required fields');
}

$company_name = sanitize($data['company_name']);
$location = sanitize($data['location']);
$contact_number = sanitize($data['contact_number'] ?? '');
$description = sanitize($data['description'] ?? '');

$db = (new Database())->connect();
try {
    // Check if provider profile exists
    $stmt = $db->prepare("SELECT id FROM providers WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    if ($stmt->rowCount() > 0) {
        respond('error', 'Provider profile already exists');
    }

    // Insert provider
    $stmt = $db->prepare("INSERT INTO providers (user_id, company_name, location, contact_number, description) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $company_name, $location, $contact_number, $description]);

    respond('success', 'Provider profile created successfully');
} catch (PDOException $e) {
    respond('error', 'Database error: ' . $e->getMessage());
}
?>