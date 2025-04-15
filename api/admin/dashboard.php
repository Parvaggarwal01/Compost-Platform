<?php

header('Access-Control-Allow-Origin: http://localhost:3000'); // or whatever your frontend port is
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');
// ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../../config/database.php';
require_once '../../includes/auth.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    respond('error', 'Unauthorized');
}

try {
    $db = (new Database())->connect();
    $stmt = $db->prepare("SELECT u.id, u.username, u.email, p.company_name, p.location
                          FROM users u
                          LEFT JOIN providers p ON u.id = p.user_id
                          WHERE u.role = 'provider' AND u.is_validated = 0");
    $stmt->execute();
    $pending_providers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $db->prepare("SELECT s.id, s.title, s.description, s.location, s.price, u.username AS company_name
                      FROM services s
                      LEFT JOIN users u ON s.provider_id = u.id
                      WHERE s.is_approved = 0");
    $stmt->execute();
    $pending_services = $stmt->fetchAll(PDO::FETCH_ASSOC);

    respond('success', ['pending_providers' => $pending_providers, 'pending_services' => $pending_services]);
} catch (PDOException $e) {
    respond('error', 'Database error: ' . $e->getMessage());
}
?>