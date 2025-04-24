<?php
  // filepath: /Applications/XAMPP/xamppfiles/htdocs/compost_platform/api/user/dashboard.php

  // --- Start Session FIRST ---
  // ** UNCOMMENT THIS - It's essential for both fetching and cancelling **

  // --- DEBUGGING: Force Error Display (REMOVE IN PRODUCTION) ---
  error_reporting(E_ALL);
  ini_set('display_errors', 1);
  // --- END DEBUGGING ---

  // --- Configuration and Initialization ---
  header("Content-Type: application/json");
  header("Access-Control-Allow-Origin: http://localhost"); // Adjust if needed
  header("Access-Control-Allow-Credentials: true");
  // ** Allow POST for cancellation **
  header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
  header("Access-Control-Allow-Headers: Content-Type");

  // Handle OPTIONS request (preflight) for CORS
  if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
      http_response_code(200);
      exit();
  }

  // --- Define Base Path ---
  define('PROJECT_ROOT', dirname(dirname(__DIR__))); // Should resolve to /Applications/XAMPP/xamppfiles/htdocs/compost_platform

  // --- Include Dependencies ---
  require_once '../../config/database.php';
  require_once '../../includes/auth.php'; // Contains respond() and isLoggedIn()

  // --- Check Core Functions ---
  if (!function_exists('respond') || !function_exists('isLoggedIn')) {
      http_response_code(500);
      echo json_encode(['status' => 'error', 'message' => 'Core function missing (respond/isLoggedIn)']);
      exit();
  }

  // --- Authentication (Required for both GET and POST) ---
  if (!isLoggedIn()) {
      error_log("User Dashboard/Cancel - User not logged in. Session ID: " . session_id());
      respond('error', 'Authentication required. Please log in.', null, 401);
  }
  $user_id = $_SESSION['user_id'];
  error_log("User Dashboard/Cancel - Logged-in user_id: " . $user_id);

  // --- Route based on Request Method ---
  if ($_SERVER['REQUEST_METHOD'] === 'GET') {
      // --- Handle GET Request: Fetch Dashboard Data ---
      error_log("User Dashboard - Handling GET request.");
      fetchUserRequests($user_id);

  } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
      // --- Handle POST Request: Cancel Service Request ---
      error_log("User Dashboard - Handling POST request (Cancel).");
      cancelServiceRequest($user_id);

  } else {
      // --- Handle Invalid Method ---
      respond('error', 'Invalid request method. Only GET and POST are allowed for this endpoint.', null, 405); // Method Not Allowed
  }

  // --- Function to Fetch User Requests ---
  function fetchUserRequests($user_id) {
      global $respond; // Make respond function available
      $database = null;
      $db = null;
      try {
          if (!class_exists('Database')) {
              throw new Exception("Database class not found.");
          }
          $database = new Database();
          if (method_exists($database, 'getConnection')) {
              $db = $database->getConnection();
          } elseif (method_exists($database, 'connect')) {
              $db = $database->connect();
          } else {
              throw new Exception("Database class connection method not found.");
          }
          if (!$db) {
              throw new Exception("Database connection failed.");
          }

          $query = "SELECT sr.id, sr.service_id, sr.status, sr.created_at, s.title as service_title, s.price as service_price, s.location as service_location, p.company_name as provider_name FROM service_requests sr JOIN services s ON sr.service_id = s.id JOIN providers p ON s.provider_id = p.id WHERE sr.user_id = :user_id ORDER BY sr.created_at DESC";
          $stmt = $db->prepare($query);
          $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
          $stmt->execute();
          $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
          error_log("User Dashboard (GET) - Fetched requests: " . count($requests));
          respond('success', 'User service requests retrieved successfully.', ['service_requests' => $requests]);

      } catch (PDOException $e) {
          error_log("User Dashboard (GET) - Database error: " . $e->getMessage());
          respond('error', 'Database error: ' . $e->getMessage(), null, 500);
      } catch (Exception $e) {
          error_log("User Dashboard (GET) - General error: " . $e->getMessage());
          respond('error', 'An internal server error occurred: ' . $e->getMessage(), null, 500);
      }
  }

  // --- Function to Cancel Service Request ---
  function cancelServiceRequest($user_id) {
      global $respond; // Make respond function available

      // Get JSON data from the request body
      $input = json_decode(file_get_contents('php://input'), true);
      error_log("Cancel Request (POST) - Received input: " . print_r($input, true));

      // Validate input
      $request_id = $input['request_id'] ?? null;
      if (!$request_id || !is_numeric($request_id)) {
          respond('error', 'Invalid or missing request ID for cancellation.', null, 400);
      }
      $request_id = (int)$request_id;

      $database = null;
      $db = null;
      try {
          if (!class_exists('Database')) {
              throw new Exception("Database class not found.");
          }
          $database = new Database();
          if (method_exists($database, 'getConnection')) {
              $db = $database->getConnection();
          } elseif (method_exists($database, 'connect')) {
              $db = $database->connect();
          } else {
              throw new Exception("Database class connection method not found.");
          }
          if (!$db) {
              throw new Exception("Database connection failed.");
          }

          // --- Verify the Request Belongs to the User and is Pending ---
          $check_query = "SELECT status FROM service_requests WHERE id = :request_id AND user_id = :user_id";
          $check_stmt = $db->prepare($check_query);
          $check_stmt->bindParam(':request_id', $request_id, PDO::PARAM_INT);
          $check_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
          $check_stmt->execute();
          $request = $check_stmt->fetch(PDO::FETCH_ASSOC);

          if (!$request) {
              respond('error', 'Service request not found or you are not authorized to cancel it.', null, 403); // Forbidden or Not Found
          }
          if ($request['status'] !== 'pending') {
              respond('error', 'Only pending service requests can be cancelled.', null, 400); // Bad Request
          }

          // --- Update the Request Status to Cancelled ---
          $update_query = "UPDATE service_requests SET status = 'cancelled', updated_at = NOW() WHERE id = :request_id AND user_id = :user_id AND status = 'pending'"; // Add status check for safety
          $update_stmt = $db->prepare($update_query);
          $update_stmt->bindParam(':request_id', $request_id, PDO::PARAM_INT);
          $update_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

          if ($update_stmt->execute() && $update_stmt->rowCount() > 0) {
              error_log("Cancel Request (POST) - Successfully cancelled request ID: " . $request_id);
              respond('success', 'Service request cancelled successfully.', ['request_id' => $request_id], 200);
          } else {
              // This could happen if the status changed between the check and update (race condition) or update failed
              error_log("Cancel Request (POST) - Failed to update request status or status was not pending for ID: " . $request_id);
              throw new Exception("Failed to update request status. It might have been updated already.");
          }

      } catch (PDOException $e) {
          error_log("Cancel Request (POST) - Database error: " . $e->getMessage());
          respond('error', 'Database error during cancellation: ' . $e->getMessage(), null, 500);
      } catch (Exception $e) {
          error_log("Cancel Request (POST) - General error: " . $e->getMessage());
          respond('error', 'An error occurred during cancellation: ' . $e->getMessage(), null, 500);
      }
  }
?>