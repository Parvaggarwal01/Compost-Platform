<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond('error', 'Method not allowed');
}

$data = json_decode(file_get_contents('php://input'), true);
$username = $data['username'] ?? '';
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';
$role = $data['role'] ?? 'user'; // Default to user

if (empty($username) || empty($email) || empty($password) || !in_array($role, ['user', 'provider', 'admin'])) {
    respond('error', 'Invalid input');
}

$db = (new Database())->connect();
try {
    // Check if user exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$email, $username]);
    if ($stmt->rowCount() > 0) {
        respond('error', 'User already exists');
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Insert user
    $stmt = $db->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$username, $email, $hashed_password, $role]);

    respond('success', 'Registration successful. Awaiting validation.');
} catch (PDOException $e) {
    respond('error', 'Database error: ' . $e->getMessage());
}
?>