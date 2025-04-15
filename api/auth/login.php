<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond('error', 'Method not allowed');
}

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

if (empty($email) || empty($password)) {
    respond('error', 'Invalid input');
}

$db = (new Database())->connect();
try {
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        respond('error', 'Invalid credentials');
    }

    if (!$user['is_validated'] && $user['role'] !== 'user') {
        respond('error', 'Account not validated by admin');
    }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['is_validated'] = $user['is_validated'];

    respond('success', 'Login successful', ['role' => $user['role']]);
} catch (PDOException $e) {
    respond('error', 'Database error: ' . $e->getMessage());
}
?>