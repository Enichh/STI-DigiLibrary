<?php
// app/routes/userRoutes.php

require_once __DIR__ . '/../utils/Logger.php';
require_once __DIR__ . '/../controllers/userController.php';

function handleUserRoutes(string $requestPath, string $method): void
{
    global $matched;

    $controller = new UserController();
    $basePath = '/users';

    // Normalize: if request starts with /users
    if (strpos($requestPath, $basePath) === 0) {
        \App\Utils\Logger::debug('User route matched', [
            'path' => $requestPath,
            'method' => $method
        ]);

        // Handle preflight requests (CORS)
        if ($method === 'OPTIONS') {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
            http_response_code(204);
            $matched = true;
            return;
        }

        try {
            // =========================================================
            // Core User Routes
            // =========================================================

            // /users → Get all users (GET)
            if ($requestPath === $basePath && $method === 'GET') {
                $controller->getUsers();
                $matched = true;
                return;
            }

            // /users/{id} → Get user by ID
            if (preg_match('#^/users/(\d+)$#', $requestPath, $matches) && $method === 'GET') {
                $controller->getUserById((int)$matches[1]);
                $matched = true;
                return;
            }

            // /users/profile → Get current user profile
            if ($requestPath === $basePath . '/profile' && $method === 'GET') {
                $controller->getCurrentUserProfile();
                $matched = true;
                return;
            }


            // /users/{id} (PUT) → Update user
            if (preg_match('#^/users/(\d+)$#', $requestPath, $matches) && $method === 'PUT') {
                $controller->updateUser((int)$matches[1]);
                $matched = true;
                return;
            }

            // /users/{id} (DELETE) → Delete user
            if (preg_match('#^/users/(\d+)$#', $requestPath, $matches) && $method === 'DELETE') {
                $controller->deleteUser((int)$matches[1]);
                $matched = true;
                return;
            }

            // =========================================================
            // No route matched
            // =========================================================
            \App\Utils\Logger::warning('User route, no match found', [
                'path' => $requestPath,
                'method' => $method
            ]);
        } catch (Throwable $e) {
            \App\Utils\Logger::error('User route error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            http_response_code(500);
            echo json_encode([
                'error' => 'Internal Server Error',
                'message' => $e->getMessage()
            ]);
        }
    }
}
