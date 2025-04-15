<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

if (!isLoggedIn() || !isProvider()) {
    respond('error', 'Unauthorized');
}

$db = (new Database())->connect();
try {
    $stmt = $db->prepare("SELECT s.* FROM services s JOIN providers p ON s.provider_id = p.id WHERE p.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

    respond('success', 'Services retrieved', $services);
} catch (PDOException $e) {
    respond('error', 'Database error: ' . $e->getMessage());
}
?>