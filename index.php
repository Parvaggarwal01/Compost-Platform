<?php
require_once 'includes/auth.php';

header('Access-Control-Allow-Origin: http://localhost:5500');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = trim($uri, '/');

$routes = [
    'api/auth/login' => 'api/auth/login.php',
    'api/auth/register' => 'api/auth/register.php',
    'api/auth/logout' => 'api/auth/logout.php',
    'api/auth/check' => 'api/auth/check.php',
    'api/user/profile' => 'api/user/profile.php',
    'api/user/update' => 'api/user/update.php',
    'api/provider/create' => 'api/provider/create.php',
    'api/provider/update' => 'api/provider/update.php',
    'api/provider/dashboard' => 'api/provider/dashboard.php',
    'api/provider/services' => 'api/provider/services.php',
    'api/provider/service-update' => 'api/provider/service-update.php',
    'api/service/create' => 'api/service/create.php',
    'api/service/list' => 'api/service/list.php',
    'api/service/filter' => 'api/service/filter.php',
    'api/service/request' => 'api/service/request.php',
    'api/service/delete' => 'api/service/delete.php',
    'api/admin/validate_provider' => 'api/admin/validate_provider.php',
    'api/admin/dashboard' => 'api/admin/dashboard.php',
    'api/admin/manage_services' => 'api/admin/manage_services.php',
];

if (isset($routes[$uri])) {
    require_once $routes[$uri];
} else {
    require_once 'api/error.php';
}
?>