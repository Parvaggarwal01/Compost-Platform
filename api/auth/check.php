<?php
// filepath: /Applications/XAMPP/xamppfiles/htdocs/compost_platform/api/auth/check.php

// --- Start Session FIRST ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- DEBUGGING: Force Error Display (REMOVE IN PRODUCTION) ---
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
// --- END DEBUGGING ---

// --- Configuration and Initialization ---
header('Content-Type: application/json');
// **IMPORTANT: Adjust Allow-Origin if your frontend is on a different port/domain**
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Credentials: true"); // Essential for cookies/sessions
header("Access-Control-Allow-Methods: GET, OPTIONS"); // Allow GET for checking
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

// Include auth.php (contains respond and isLoggedIn)
error_log("Check Auth - Checking Auth path: " . $auth_path);
if (!file_exists($auth_path)) {
    $msg = "Server Error: Auth file not found at expected path: " . $auth_path;
    error_log("Check Auth - FATAL: " . $msg);
    http_response_code(500); echo json_encode(['status'=>'error', 'message'=>$msg]); exit();
}
require_once $auth_path;
error_log("Check Auth - Included auth.php successfully.");

// Include database.php (optional for consistency, not used directly here)
// error_log("Check Auth - Checking DB path: " . $db_path);
// if (!file_exists($db_path)) {
//     $msg = "Server Error: Database config file not found at expected path: " . $db_path;
//     error_log("Check Auth - FATAL: " . $msg);
//     http_response_code(500); echo json_encode(['status'=>'error', 'message'=>$msg]); exit();
// }
// require_once $db_path;
// error_log("Check Auth - Included database.php successfully.");


// --- Check if Core Functions Exist AFTER includes ---
if (!function_exists('respond') || !function_exists('isLoggedIn')) {
    $msg = "Server Error: Core function respond() or isLoggedIn() missing AFTER includes. Check auth.php.";
    error_log("Check Auth - FATAL: " . $msg);
    http_response_code(500); echo json_encode(['status'=>'error', 'message'=>$msg]); exit();
}
error_log("Check Auth - Core functions respond() and isLoggedIn() exist.");


// --- Check Login Status ---
// Log session data for debugging
error_log("Check Auth - Session data: " . print_r($_SESSION, true) . ", Session ID: " . session_id());

if (isLoggedIn()) {
    // User is logged in, respond with success and role
    $role = $_SESSION['role'] ?? 'unknown'; // Default role if not set
    error_log("Check Auth - User is logged in. Role: " . $role);
    respond('success', 'User is logged in', ['role' => $role], 200);
} else {
    // User is not logged in
    error_log("Check Auth - User is NOT logged in.");
    respond('error', 'Not logged in', null, 401);
}
?>