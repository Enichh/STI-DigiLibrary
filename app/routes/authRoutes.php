<?php
// app/routes/authRoutes.php

require_once __DIR__ . '/../utils/Logger.php';
require_once __DIR__ . '/../controllers/authController.php';

/**
 * Handles authentication-related routes
 * 
 * @param string $requestPath The request URI path
 * @param string $method The HTTP request method
 * @return void
 */
function handleAuthRoutes(string $requestPath, string $method): void
{
    global $matched;

    $authController = new AuthController();
    $basePath = '/auth';

    // Check if the request path begins with /auth (handles authentication)
    if (strpos($requestPath, $basePath) === 0) {
        \App\Utils\Logger::debug('Auth route matched', [
            'path' => $requestPath,
            'method' => $method
        ]);

        // Extract the path without query parameters
        $path = parse_url($requestPath, PHP_URL_PATH);
        $endpoint = rtrim(substr($path, strlen($basePath)), '/');

        switch ($endpoint) {
            case '/login':
                if ($method === 'POST') {
                    $authController->login();
                    $matched = true;
                    return;
                }
                break;

            case '/signup':
                if ($method === 'POST') {
                    $authController->signup();
                    $matched = true;
                    return;
                }
                break;

            case '/verify':
                if ($method === 'POST') {
                    error_log("=== AUTH VERIFY ENDPOINT HIT ===");
                    error_log("Request Path: " . $requestPath);
                    error_log("Method: " . $method);
                    error_log("POST Data: " . file_get_contents('php://input'));

                    $authController->sendVerificationEmail();
                    $matched = true;
                    return;
                }
                break;

            case '/verify-code':
                if ($method === 'POST') {
                    error_log("=== AUTH VERIFY CODE ENDPOINT HIT ===");
                    error_log("Request Path: " . $requestPath);
                    error_log("Method: " . $method);
                    error_log("POST Data: " . file_get_contents('php://input'));

                    $authController->verifyCode();
                    $matched = true;
                    return;
                }
                break;

            case '/locked-code':
                error_log("=== AUTH LOCKED CODE ENDPOINT HIT ===");
                error_log("Request Path: " . $requestPath);
                error_log("Method: " . $method);
                error_log("POST Data: " . file_get_contents('php://input'));

                if ($method === 'POST') {
                    $authController->sendLockedCode();
                    $matched = true;
                    return;
                }
                break;

            case '/verify-locked-code':
                error_log("=== AUTH VERIFY LOCKED CODE ENDPOINT HIT ===");
                error_log("Request Path: " . $requestPath);
                error_log("Method: " . $method);
                error_log("POST Data: " . file_get_contents('php://input'));

                if ($method === 'POST') {
                    $authController->verifyLockedCode();
                    $matched = true;
                    return;
                }
                break;

            case '/change-password':
                error_log("=== AUTH CHANGE PASSWORD ENDPOINT HIT ===");
                error_log("Request Path: " . $requestPath);
                error_log("Method: " . $method);
                error_log("POST Data: " . file_get_contents('php://input'));

                if ($method === 'POST') {
                    $authController->changePassword();
                    $matched = true;
                    return;
                }
                break;

            case '/reset-password':
                error_log("AUTH ROUTES: matched reset-password");
                if ($method === 'POST') {
                    $authController->resetPassword();
                    $matched = true;
                    return;
                }
                break;

            case '/confirm-reset-password':
                error_log("AUTH ROUTES: matched confirm-reset-password");
                if ($method === 'POST') {
                    $authController->confirmResetPassword();
                    $matched = true;
                    return;
                }
                break;
        }
    }
}
