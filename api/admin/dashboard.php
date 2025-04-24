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
    // Get pending providers
    $stmt = $db->prepare("SELECT u.id, u.username, u.email, p.company_name, p.location
                          FROM users u
                          LEFT JOIN providers p ON u.id = p.user_id
                          WHERE u.role = 'provider' AND u.is_validated = 0");
    $stmt->execute();
    $pending_providers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get pending services
    $stmt = $db->prepare("SELECT s.id, s.title, s.description, s.location, s.price, u.username AS company_name
                      FROM services s
                      LEFT JOIN users u ON s.provider_id = u.id
                      WHERE s.is_approved = 0");
    $stmt->execute();
    $pending_services = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get user statistics
    $stmt = $db->prepare("SELECT
                            (SELECT COUNT(*) FROM users WHERE role = 'user') as total_users,
                            (SELECT COUNT(*) FROM users WHERE role = 'provider' AND is_validated = 1) as active_providers,
                            (SELECT COUNT(*) FROM users WHERE role = 'provider' AND is_validated = 0) as pending_providers,
                            (SELECT COUNT(*) FROM services WHERE is_approved = 1) as active_services,
                            (SELECT COUNT(*) FROM services WHERE is_approved = 0) as pending_services,
                            (SELECT COUNT(*) FROM service_requests) as total_requests");
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get monthly registrations for chart data
    $stmt = $db->prepare("SELECT
                            DATE_FORMAT(created_at, '%Y-%m') as month,
                            COUNT(CASE WHEN role = 'user' THEN 1 END) as user_count,
                            COUNT(CASE WHEN role = 'provider' THEN 1 END) as provider_count
                         FROM users
                         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                         GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                         ORDER BY month ASC");
    $stmt->execute();
    $monthly_registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    respond('success', [
        'pending_providers' => $pending_providers,
        'pending_services' => $pending_services,
        'stats' => $stats,
        'monthly_registrations' => $monthly_registrations
    ]);
} catch (PDOException $e) {
    respond('error', 'Database error: ' . $e->getMessage());
}
?>