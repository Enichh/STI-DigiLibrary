<?php
session_start();

$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
} else {
    error_log("Warning: Autoloader not found at: $autoloadPath");
}

try {
    if (class_exists('Dotenv\Dotenv')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->load();
    }
} catch (Exception $e) {
    error_log("Warning: Could not load .env file: " . $e->getMessage());
}

if (getenv('APP_ENV') !== 'production') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$requestPath = preg_replace('#^/STI-DigiLibrary/server/public#', '', $requestPath);
$requestPath = preg_replace('#^/server/public#', '', $requestPath);

$method = $_SERVER['REQUEST_METHOD'];

error_log("DEBUG: Request received - Original URI: {$_SERVER['REQUEST_URI']}, Path: '$requestPath', Method: '$method'");

$matched = false;
require __DIR__ . '/../routes/authRoutes.php';
require __DIR__ . '/../routes/userRoutes.php';
require __DIR__ . '/../routes/configRoutes.php';

error_log("DEBUG: Calling handleUserRoutes with path: '$requestPath', method: '$method'");
handleUserRoutes($requestPath, $method);

error_log("DEBUG: Calling handleConfigRoutes with path: '$requestPath', method: '$method'");
handleConfigRoutes($requestPath, $method);

error_log("DEBUG: After route handling - matched: " . ($matched ? 'true' : 'false'));

if (!$matched) {
    error_log('No route matched - Path: ' . $requestPath . ', Method: ' . $method);

    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        "error" => "Endpoint not found",
        "path"  => $requestPath
    ]);
}
