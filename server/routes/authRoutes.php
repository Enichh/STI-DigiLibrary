<?php
// server/routes/authRoutes.php

require_once __DIR__ . '/../utils/Logger.php';
require_once __DIR__ . '/../controllers/authController.php';

// Instantiate authentication controller
$authController = new AuthController();

global $matched, $requestPath, $method;

$basePath = '/auth';

// Check if the request path begins with /auth (handles authentication)
if (strpos($requestPath, $basePath) === 0) {
    \App\Utils\Logger::debug('Auth route matched', [
        'path' => $requestPath,
        'method' => $method
    ]);

    switch ($requestPath) {
        // Handle login API endpoint
        case $basePath . '/login.php':
            if ($method === 'POST') {
                $authController->login();
                $matched = true;
                exit;
            }
            break;

        // Handle user/admin signup
        case $basePath . '/signup.php':
            if ($method === 'POST') {
                $authController->signup();
                $matched = true;
                exit;
            }
            break;

        // Request verification email for new signup
        case $basePath . '/verify.php':
            if ($method === 'POST') {
                $authController->sendVerificationEmail();
                $matched = true;
                exit;
            }
            break;

        // Verify code for user/admin (signup/login)
        case $basePath . '/verify-code.php':
            if ($method === 'POST') {
                $authController->verifyCode();
                $matched = true;
                exit;
            }
            break;

        // Verify code for admin-only actions
        case $basePath . '/verify-admin-code.php':
            error_log("AUTH ROUTES: matched verify-admin-code");
            if ($method === 'POST') {
                $authController->verifyAdminCode();
                $matched = true;
                exit;
            }
            break;

        // Send unlock code via email for locked account
        case $basePath . '/locked-code.php':
            error_log("AUTH ROUTES: matched locked-code");
            if ($method === 'POST') {
                $authController->sendLockedCode();
                $matched = true;
                exit;
            }
            break;

        // Verify unlock code to reactivate locked account
        case $basePath . '/verify-locked-code.php':
            error_log("AUTH ROUTES: matched verify-locked-code");
            if ($method === 'POST') {
                $authController->verifyLockedCode();
                $matched = true;
                exit;
            }
            break;

        // Issue a new admin verification code
        case $basePath . '/issue-admin-code.php':
            error_log("AUTH ROUTES: matched issue-admin-code");
            if ($method === 'POST') {
                $authController->issueAdminCode();
                $matched = true;
                exit;
            }
            break;

        // Process password change request
        case $basePath . '/change-password.php':
            error_log("AUTH ROUTES: matched change-password");
            if ($method === 'POST') {
                $authController->changePassword();
                $matched = true;
                exit;
            }
            break;

        // Initiate password reset request (send reset code)
        case $basePath . '/reset-password.php':
            error_log("AUTH ROUTES: matched reset-password");
            if ($method === 'POST') {
                $authController->resetPassword();
                $matched = true;
                exit;
            }
            break;

        // Confirm password reset and set a new password
        case $basePath . '/confirm-reset-password.php':
            error_log("AUTH ROUTES: matched confirm-reset-password");
            if ($method === 'POST') {
                $authController->confirmResetPassword();
                $matched = true;
                exit;
            }
            break;
    }
}
