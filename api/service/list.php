<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');
ini_set('display_errors', 1); // Enable for debugging
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond('error', 'Method not allowed');
}

$limit = (int)($_GET['limit'] ?? 10); // Default to 10 services per page
$offset = (int)($_GET['offset'] ?? 0); // Default to first page

error_log("Limit: $limit, Offset: $offset"); // Debug log

$db = (new Database())->connect();
try {
    // Query to fetch all approved services
    $query = "SELECT s.id, s.title, s.description, s.location, s.price, u.username AS company_name
                      FROM services s
                      LEFT JOIN users u ON s.provider_id = u.id
                      WHERE s.is_approved = 1
              ORDER BY s.created_at DESC
              LIMIT ? OFFSET ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $limit, PDO::PARAM_INT);
    $stmt->bindParam(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total count for pagination
    $countStmt = $db->prepare("SELECT COUNT(*) FROM services WHERE is_approved = 1");
    $countStmt->execute();
    $total = $countStmt->fetchColumn();

    if (empty($services)) {
        error_log("No approved services found with limit=$limit, offset=$offset");
    }

    respond('success', 'Services retrieved', [
        'services' => $services,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset
    ]);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    respond('error', 'Database error: ' . $e->getMessage());
}
?>