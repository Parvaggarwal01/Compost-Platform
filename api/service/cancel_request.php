<?php
// filepath: /Applications/XAMPP/xamppfiles/htdocs/compost_platform/api/service/cancel_request.php

// --- Start Session FIRST ---
// This is crucial for isLoggedIn() to work
// --- DEBUGGING: Force Error Display (REMOVE IN PRODUCTION) ---
error_reporting(E_ALL);
ini_set('display_errors', 1);
// --- END DEBUGGING ---

// --- Configuration and Initialization ---
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost"); // Ensure this matches your frontend origin
header("Access-Control-Allow-Credentials: true"); // Essential for session cookies
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle OPTIONS request (preflight) for CORS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// --- Define Base Path ---
define('PROJECT_ROOT', dirname(dirname(__DIR__))); // Resolves to /Applications/XAMPP/xamppfiles/htdocs/compost_platform

// --- Include Dependencies ---
// ** Verify these paths are correct relative to cancel_request.php **
require_once '../../config/database.php';
require_once '../../includes/auth.php'; // Contains respond() and isLoggedIn()

// --- Authentication ---
// Ensure respond() and isLoggedIn() are loaded
if (!function_exists('respond') || !function_exists('isLoggedIn')) {
    http_response_code(500);
    echo json_encode(['status'=>'error', 'message'=>'Core function missing (respond/isLoggedIn)']);
    exit();
}

// Now isLoggedIn() can access the session data loaded by session_start()
if (!isLoggedIn()) {
    error_log("Cancel Request - User not logged in. Session ID: " . session_id() . " Session Data: " . print_r($_SESSION, true));
    respond('error', 'Authentication required. Please log in.', null, 401);
}
$user_id = $_SESSION['user_id'];
error_log("Cancel Request - Logged-in user_id: " . $user_id);

// --- Handle Request ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond('error', 'Invalid request method.', null, 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['request_id'])) {
    respond('error', 'Request ID is required.', null, 400);
}

$request_id = (int)$input['request_id'];
if ($request_id <= 0) {
    respond('error', 'Invalid request ID.', null, 400);
}

// --- Database Interaction ---
$database = null;
$db = null;
try {
    if (!class_exists('Database')) {
        throw new Exception("Database class not found. Check include path for config/database.php.");
    }
    $database = new Database();
    // ** Verify: Is the method connect() or getConnection()? **
    $db = $database->connect(); // Assuming connect() is correct

    if (!$db) {
        throw new Exception("Database connection failed.");
    }

    // --- Verify the Request Belongs to the User and is Pending ---
    $query = "SELECT user_id, status FROM service_requests WHERE id = :request_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':request_id', $request_id, PDO::PARAM_INT);
    $stmt->execute();
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        respond('error', 'Service request not found.', null, 404);
    }

    if ($request['user_id'] != $user_id) {
        respond('error', 'Unauthorized: You can only cancel your own requests.', null, 403);
    }

    if ($request['status'] !== 'pending') {
        respond('error', 'Only pending requests can be cancelled.', null, 400);
    }

    // --- Update the Request Status to Cancelled ---
    $update_query = "UPDATE service_requests SET status = 'cancelled' WHERE id = :request_id";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(':request_id', $request_id, PDO::PARAM_INT);
    $update_stmt->execute();

    // Check if the update actually affected a row
    if ($update_stmt->rowCount() === 0) {
        // This could happen if the request was already cancelled or deleted between the check and the update
        // Or if the WHERE clause didn't match for some reason (unlikely given previous checks)
        error_log("Cancel Request - Update affected 0 rows for request ID: " . $request_id);
        // Decide how to handle this - maybe it's okay, maybe it's an error
        // For now, let's assume it's okay if the status check passed just before
        // If you want to be stricter, throw new Exception("Failed to update request status (0 rows affected).");
    }

    respond('success', 'Service request cancelled successfully.', null, 200);

} catch (PDOException $e) {
    error_log("Cancel Request - Database error: " . $e->getMessage());
    respond('error', 'Database error: ' . $e->getMessage(), null, 500);
} catch (Exception $e) {
    error_log("Cancel Request - General error: " . $e->getMessage());
    respond('error', 'An error occurred: ' . $e->getMessage(), null, 500);
}
?>