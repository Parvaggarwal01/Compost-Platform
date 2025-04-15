<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond('error', 'Method not allowed');
}

$location = $_GET['location'] ?? '';
$service_type = $_GET['service_type'] ?? '';
$limit = (int)($_GET['limit'] ?? 10);
$offset = (int)($_GET['offset'] ?? 0);

$db = (new Database())->connect();
try {
    $query = "SELECT s.*, p.company_name FROM services s JOIN providers p ON s.provider_id = p.id WHERE s.is_approved = 1";
    $params = [];

    if (!empty($location)) {
        $query .= " AND s.location LIKE ?";
        $params[] = "%$location%";
    }
    if (!empty($service_type)) {
        $query .= " AND s.service_type = ?";
        $params[] = $service_type;
    }

    $query .= " LIMIT ? OFFSET ?";
    $stmt = $db->prepare($query);

    // Bind dynamic conditions first
    $index = 1;
    if (!empty($location)) {
      $stmt->bindValue($index++, "%$location%", PDO::PARAM_STR);
    }
    if (!empty($service_type)) {
      $stmt->bindValue($index++, $service_type, PDO::PARAM_STR);
    }

    // Bind LIMIT and OFFSET correctly
    $stmt->bindValue($index++, $limit, PDO::PARAM_INT);
    $stmt->bindValue($index++, $offset, PDO::PARAM_INT);

    $stmt->execute();
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

    respond('success', 'Services retrieved', $services);
} catch (PDOException $e) {
    respond('error', 'Database error: ' . $e->getMessage());
}
?>