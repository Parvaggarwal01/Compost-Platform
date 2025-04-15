<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/helpers.php';

if (!isLoggedIn()) {
    respond('error', 'Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond('error', 'Method not allowed');
}

$data = json_decode(file_get_contents('php://input'), true);
$email = sanitize($data['email'] ?? '');
$password = $data['password'] ?? '';

$db = (new Database())->connect();
try {
    $updates = [];
    $params = [];

    if (!empty($email)) {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $_SESSION['user_id']]);
        if ($stmt->rowCount() > 0) {
            respond('error', 'Email already in use');
        }
        $updates[] = "email = ?";
        $params[] = $email;
    }

    if (!empty($password)) {
        $updates[] = "password = ?";
        $params[] = password_hash($password, PASSWORD_BCRYPT);
    }

    if (empty($updates)) {
        respond('error', 'No updates provided');
    }

    $params[] = $_SESSION['user_id'];
    $query = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute($params);

    respond('success', 'Profile updated successfully');
} catch (PDOException $e) {
    respond('error', 'Database error: ' . $e->getMessage());
}
?>