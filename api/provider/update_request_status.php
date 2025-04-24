<?php
// filepath: /Applications/XAMPP/xamppfiles/htdocs/compost_platform/api/provider/update_request_status.php

// --- Start Session FIRST ---

// --- DEBUGGING: Force Error Display (REMOVE IN PRODUCTION) ---
error_reporting(E_ALL);
ini_set('display_errors', 1);
// --- END DEBUGGING ---

// --- Configuration and Initialization ---
header("Content-Type: application/json");
// **IMPORTANT: Adjust Allow-Origin if your frontend is on a different port/domain**
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Credentials: true"); // Essential for session cookies
header("Access-Control-Allow-Methods: POST, OPTIONS"); // Use POST (or PUT/PATCH) for updates
header("Access-Control-Allow-Headers: Content-Type");

// Handle OPTIONS request (preflight) for CORS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit('OPTIONS OK');
}

// --- Define Base Path ---
define('PROJECT_ROOT', dirname(dirname(__DIR__)));

// --- Include Dependencies & VERIFY ---
// ** USER ACTION: Double-check these paths based on your folder structure! **
$db_path = PROJECT_ROOT . '/config/database.php'; // Assuming config is at root level
$auth_path = PROJECT_ROOT . '/includes/auth.php'; // Assuming includes is at root level

error_log("Update Request Status - Checking Auth path: " . $auth_path);
if (!file_exists($auth_path)) {
    $msg = "Server Error: Auth file not found at expected path: " . $auth_path;
    error_log("Update Request Status - FATAL: " . $msg);
    http_response_code(500); echo json_encode(['status'=>'error', 'message'=>$msg]); exit();
}
require_once $auth_path;
error_log("Update Request Status - Included auth.php successfully.");

error_log("Update Request Status - Checking DB path: " . $db_path);
if (!file_exists($db_path)) {
    $msg = "Server Error: Database config file not found at expected path: " . $db_path;
    error_log("Update Request Status - FATAL: " . $msg);
    http_response_code(500); echo json_encode(['status'=>'error', 'message'=>$msg]); exit();
}
require_once $db_path;
error_log("Update Request Status - Included database.php successfully.");


// --- Check if Core Functions Exist AFTER includes ---
if (!function_exists('respond') || !function_exists('isLoggedIn') || !function_exists('isProvider')) {
    $msg = "Server Error: Core function respond(), isLoggedIn(), or isProvider() missing AFTER includes. Check auth.php.";
    error_log("Update Request Status - FATAL: " . $msg);
    http_response_code(500); echo json_encode(['status'=>'error', 'message'=>$msg]); exit();
}
error_log("Update Request Status - Core functions exist.");


// --- Authentication ---
if (!isLoggedIn()) {
    error_log("Update Request Status - Auth failed: isLoggedIn() returned false.");
    respond('error', 'Authentication required. Please log in.', null, 401);
}
if (!isProvider()) {
    error_log("Update Request Status - Auth failed: isProvider() returned false. Session Data: " . print_r($_SESSION, true));
    respond('error', 'Unauthorized: Provider access required.', null, 403);
}
$provider_user_id = $_SESSION['user_id']; // The user_id of the logged-in provider
error_log("Update Request Status - Auth successful for provider user_id: " . $provider_user_id);


// --- Handle Request Method ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond('error', 'Invalid request method. Only POST is allowed.', null, 405);
}

// --- Input Processing ---
$input = json_decode(file_get_contents('php://input'), true);
error_log("Update Request Status - Received input: " . print_r($input, true));

$request_id = $input['request_id'] ?? null;
$new_status = $input['status'] ?? null; // Expecting 'accepted' or 'cancelled' (for reject)

// Validate input
if (!$request_id || !is_numeric($request_id) || (int)$request_id <= 0) {
    respond('error', 'Invalid or missing Service Request ID.', null, 400);
}
$request_id = (int)$request_id;

// Validate the new status - must be one of the allowed actions for a provider
$allowed_statuses = ['accepted', 'cancelled']; // Add 'completed' later if needed
if (!$new_status || !in_array($new_status, $allowed_statuses)) {
    respond('error', 'Invalid or missing status. Must be one of: ' . implode(', ', $allowed_statuses), null, 400);
}


// --- Database Interaction ---
$database = null;
$db = null;
try {
    error_log("Update Request Status - Checking Database class existence.");
    if (!class_exists('Database')) { throw new Exception("Database class not found AFTER include."); }
    $database = new Database();

    // ** Verify DB connection method **
    if (method_exists($database, 'getConnection')) { $db = $database->getConnection(); }
    elseif (method_exists($database, 'connect')) { $db = $database->connect(); }
    else { throw new Exception("Database class connection method not found."); }
    if (!$db) { throw new Exception("Database connection failed."); }
    error_log("Update Request Status - Database connection successful.");

    // --- Verify Request Ownership and Current Status ---
    // Check if the request exists, is 'pending', and belongs to a service offered by THIS provider
    $check_query = "SELECT sr.status
                    FROM service_requests sr
                    JOIN services s ON sr.service_id = s.id
                    JOIN providers p ON s.provider_id = p.id
                    WHERE sr.id = :request_id AND p.user_id = :provider_user_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':request_id', $request_id, PDO::PARAM_INT);
    $check_stmt->bindParam(':provider_user_id', $provider_user_id, PDO::PARAM_INT);
    $check_stmt->execute();
    $current_request = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$current_request) {
        error_log("Update Request Status - Request ID $request_id not found or does not belong to provider user ID $provider_user_id.");
        respond('error', 'Service request not found or you are not authorized to modify it.', null, 404); // Not Found or Forbidden
    }

    if ($current_request['status'] !== 'pending') {
        error_log("Update Request Status - Request ID $request_id is not pending (current status: " . $current_request['status'] . "). Cannot change status.");
        respond('error', 'Only pending requests can be accepted or rejected. Current status: ' . $current_request['status'], null, 400); // Bad Request
    }

    // --- Update the Request Status ---
    // We already verified ownership and pending status, so we can update directly
    // Add updated_at column if it exists
    $update_query = "UPDATE service_requests SET status = :new_status WHERE id = :request_id";
    // If you have an updated_at column:
    // $update_query = "UPDATE service_requests SET status = :new_status, updated_at = NOW() WHERE id = :request_id";

    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(':new_status', $new_status, PDO::PARAM_STR);
    $update_stmt->bindParam(':request_id', $request_id, PDO::PARAM_INT);

    if ($update_stmt->execute()) {
        if ($update_stmt->rowCount() > 0) {
            error_log("Update Request Status - Successfully updated request ID $request_id to status '$new_status' for provider user ID $provider_user_id.");
            respond('success', 'Service request status updated successfully to ' . $new_status . '.', ['request_id' => $request_id, 'new_status' => $new_status], 200);
        } else {
            // Should not happen if the check passed, but handle defensively
            error_log("Update Request Status - Update executed but no rows affected for request ID $request_id (status might have changed).");
            throw new Exception("Failed to update request status (0 rows affected). It might have been updated by another process.");
        }
    } else {
        $errorInfo = $update_stmt->errorInfo();
        error_log("Update Request Status - Failed to execute update for request ID $request_id. Error: " . print_r($errorInfo, true));
        throw new Exception("Database error occurred during status update.");
    }

} catch (PDOException $e) {
    error_log("Update Request Status - PDOException: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    respond('error', 'Database error occurred while updating status: ' . $e->getMessage(), null, 500);
} catch (Exception $e) {
    error_log("Update Request Status - Exception: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    respond('error', 'An error occurred while updating status: ' . $e->getMessage(), null, 500);
}
?>