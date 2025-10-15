<?php
// server/routes/configRoutes.php

// Handles requests for frontend configuration (API endpoints, reCAPTCHA keys, etc.)
function handleConfigRoutes($requestPath, $method)
{
    global $matched;

    // Log the incoming config route check for debugging
    error_log("DEBUG: Config route check - Path: '$requestPath', Method: '$method', Expected: '/config/frontend'");

    // Serve config data if path matches and method is GET
    if ($requestPath === '/config/frontend' && $method === 'GET') {
        error_log("DEBUG: Config route matched - serving config");

        try {
            // Define configuration to send to frontend (API and reCAPTCHA info)
            $config = [
                'api' => [
                    'baseUrl' => 'http://localhost/STI-DigiLibrary/server/public',
                    'endpoints' => [
                        'verifyCode' => '/auth/verify-code.php',
                        'verifyAdminCode' => '/auth/verify-admin-code.php',
                        'verifyLockedCode' => '/auth/verify-locked-code.php',
                        'signup' => '/auth/signup.php',
                        'verify' => '/auth/verify.php',
                        'resetPassword' => '/auth/reset-password.php',
                        'confirmResetPassword' => '/auth/confirm-reset-password.php',
                        'changePassword' => '/auth/change-password.php',
                        'login' => '/auth/login.php',
                        'sendLockedCode' => '/auth/locked-code.php',
                        'issueAdminCode' => '/auth/issue-admin-code.php',
                    ],
                    'users' => [
                        'getUsers' => '/users.php',
                    ],
                ],
                'recaptcha' => [
                    'siteKey' => '6Ldr480rAAAAAFzjsARYcwQUgmlLcJ6SR1clGOsL' // Hardcoded for tatawagin ko .env rito
                ],
            ];

            // Set matched to true to indicate the request was handled
            $matched = true;

            // Return config as JSON with appropriate headers
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');

            echo json_encode($config);
            error_log("DEBUG: Config served successfully");
            exit;
        } catch (Exception $e) {
            // Handle and log errors in serving config
            error_log("ERROR: Failed to serve config: " . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Internal server error']);
            exit;
        }
    } else {
        // Log when the config route does not match the expected pattern
        error_log("DEBUG: Config route NOT matched - Path: '$requestPath', Method: '$method'");
    }
}
