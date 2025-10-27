<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Custom log file location (Windows XAMPP)
$logFilePath = 'C:\\xampp\\htdocs\\STI-DigiLibrary\\logs\\app.log';

// Ensure directory exists
if (!file_exists(dirname($logFilePath))) {
    mkdir(dirname($logFilePath), 0777, true);
}

// Set PHP error log destination
ini_set('error_log', $logFilePath);
error_log("=== [STARTUP] STI-DigiLibrary initialized at " . date("Y-m-d H:i:s") . " ===");

// Custom error handler
set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($logFilePath) {
    $msg = json_encode([
        'type' => 'PHP_ERROR',
        'code' => $errno,
        'message' => $errstr,
        'file' => $errfile,
        'line' => $errline,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    error_log($msg, 3, $logFilePath);
    return false;
});

// Custom exception handler
set_exception_handler(function ($exception) use ($logFilePath) {
    $msg = json_encode([
        'type' => 'UNCAUGHT_EXCEPTION',
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    error_log($msg, 3, $logFilePath);
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error']);
});

ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

$sessionParams = [
    'lifetime' => 86400, // 24 hours
    'path' => '/',
    'domain' => '',
    'secure' => false, // true if HTTPS
    'httponly' => true,
    'samesite' => 'Lax'
];
session_set_cookie_params($sessionParams);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
    error_log("Session started with ID: " . session_id(), 3, $logFilePath);
}

// Configure CORS for frontend (React)
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$basePath = '/STI-DigiLibrary/public';

if (str_starts_with($requestPath, $basePath)) {
    $requestPath = substr($requestPath, strlen($basePath));
}
if (!str_starts_with($requestPath, '/')) $requestPath = '/' . $requestPath;
if ($requestPath !== '/' && str_ends_with($requestPath, '/')) $requestPath = rtrim($requestPath, '/');

$isApiRequest = str_starts_with($requestPath, '/api') || str_starts_with($requestPath, '/config');
if (str_starts_with($requestPath, '/api/')) $requestPath = substr($requestPath, 4);
elseif ($requestPath === '/api') $requestPath = '/';

error_log(json_encode([
    'type' => 'REQUEST_DEBUG',
    'uri' => $_SERVER['REQUEST_URI'],
    'normalized_path' => $requestPath,
    'method' => $method,
    'is_api' => $isApiRequest,
    'timestamp' => date('Y-m-d H:i:s')
]) . PHP_EOL, 3, $logFilePath);

$matched = false;

if (!$isApiRequest) {
    $views = [
        '/'                 => '/../app/views/pages/login.php',
        '/login'            => '/../app/views/pages/login.php',
        '/catalog'          => '/../app/views/pages/catalog.php',
        '/admin-dashboard'  => '/../app/views/pages/adminDashboard.php',
        '/profile'          => '/../app/views/pages/profile.php',
    ];

    if (array_key_exists($requestPath, $views)) {
        require_once __DIR__ . $views[$requestPath];
        error_log("Page rendered: {$requestPath}", 3, $logFilePath);
        exit;
    }

    http_response_code(404);
    echo "<h1>404 | Page Not Found</h1><p>The page <strong>{$requestPath}</strong> does not exist.</p>";
    error_log("404 Page Not Found: {$requestPath}", 3, $logFilePath);
    exit;
}

header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    error_log("Session auto-started for API route.", 3, $logFilePath);
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/utils/Logger.php';

\App\Utils\Logger::debug('Incoming API Request', [
    'path' => $requestPath,
    'method' => $method,
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
]);

require_once __DIR__ . '/../app/routes/configRoutes.php';
require_once __DIR__ . '/../app/routes/authRoutes.php';
require_once __DIR__ . '/../app/routes/booksRoutes.php';
require_once __DIR__ . '/../app/routes/thesisRoutes.php';
require_once __DIR__ . '/../app/routes/userRoutes.php';
require_once __DIR__ . '/../app/routes/catalogRoutes.php';
require_once __DIR__ . '/../app/routes/libraryIdRoutes.php';
require_once __DIR__ . '/../app/routes/loanRoutes.php';
require_once __DIR__ . '/../app/routes/fineRoutes.php';

// Dispatch routes
handleConfigRoutes($requestPath, $method);
if (!$matched) handleAuthRoutes($requestPath, $method);
if (!$matched) handleBooksRoutes($requestPath, $method);
if (!$matched) handleThesisRoutes($requestPath, $method);
if (!$matched) handleUserRoutes($requestPath, $method);
if (!$matched) handleCatalogRoutes($requestPath, $method);
if (!$matched) handleLibraryIdRoutes($requestPath, $method);
if (!$matched) handleLoanRoutes($requestPath, $method);
if (!$matched) handleFineRoutes($requestPath, $method);
if (!$matched) {
    http_response_code(404);
    echo json_encode([
        'error' => 'Not Found',
        'message' => 'The requested API endpoint does not exist',
        'path' => $requestPath,
        'method' => $method,
    ]);
    error_log("API 404 Not Found: {$requestPath}", 3, $logFilePath);
}

\App\Utils\Logger::debug('Request completed', [
    'matched' => $matched,
    'status_code' => http_response_code(),
    'execution_ms' => round(microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"], 5) * 1000
]);

error_log(json_encode([
    'type' => 'REQUEST_COMPLETE',
    'status_code' => http_response_code(),
    'execution_ms' => round(microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"], 5) * 1000,
    'timestamp' => date('Y-m-d H:i:s')
]) . PHP_EOL, 3, $logFilePath);
