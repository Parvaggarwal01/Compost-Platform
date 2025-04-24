<?php
session_start();

/**
 * Checks if a user is logged in by verifying the presence of 'user_id' in the session.
 * Ensures the session has been started before checking.
 *
 * @return bool True if the user is logged in, false otherwise.
 */
function isLoggedIn() {
    // Check if session has been started before accessing $_SESSION
    if (session_status() !== PHP_SESSION_ACTIVE) {
        // Log an error or handle it, but don't start the session here
        error_log("Warning: Session not active when calling isLoggedIn().");
        return false;
    }
    return isset($_SESSION['user_id']);
}

/**
 * Checks if the logged-in user has the 'admin' role.
 *
 * @return bool True if the user is an admin, false otherwise.
 */
function isAdmin() {
    // Check session status and required keys
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Checks if the logged-in user has the 'provider' role and is validated.
 *
 * @return bool True if the user is a validated provider, false otherwise.
 */
function isProvider() {
    return isLoggedIn() && $_SESSION['role'] === 'provider' && $_SESSION['is_validated'];
}

/**
 * Sends a JSON response and terminates the script.
 *
 * @param string $status The status of the response ('success' or 'error').
 * @param string $message A descriptive message for the response.
 * @param mixed|null $data Optional data payload to include in the response.
 * @param int|null $http_code Optional HTTP status code to set (e.g., 200, 400, 401, 500). Defaults based on status if null.
 */
function respond($status, $message, $data = null, $http_code = null) {
    // Set default HTTP status code if not provided
    if ($http_code === null) {
        $http_code = ($status === 'success') ? 200 : 400; // Default to 200 for success, 400 for error
    }
    http_response_code($http_code);

    // Set content type
    header('Content-Type: application/json');

    // Create response array
    $response = [
        'status' => $status,
        'message' => $message,
    ];
    // Only include 'data' key if data is not null
    if ($data !== null) {
        $response['data'] = $data;
    }

    // Output JSON response
    echo json_encode($response);

    // Terminate script execution
    exit;
}
?>