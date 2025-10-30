<?php
// app/routes/notificationRoutes.php

require_once __DIR__ . '/../utils/Logger.php';
require_once __DIR__ . '/../controllers/notificationController.php';
require_once __DIR__ . '/../../config/database.php';

/**
 * Handles notification-related API routes.
 *
 * @param string $requestPath The request URI path
 * @param string $method The HTTP request method
 * @return void
 */
function handleNotificationRoutes(string $requestPath, string $method): void
{
    global $matched;

    $pdo = getPDO();
    $controller = new NotificationController($pdo);
    $basePath = '/notifications';

    if (strpos($requestPath, $basePath) === 0) {
        \App\Utils\Logger::debug('Notifications route matched', [
            'path' => $requestPath,
            'method' => $method
        ]);

        $path = parse_url($requestPath, PHP_URL_PATH);
        $endpoint = rtrim(substr($path, strlen($basePath)), '/');

        // Handle CORS preflight
        if ($method === 'OPTIONS') {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
            http_response_code(204);
            $matched = true;
            return;
        }

        try {
            switch (true) {
                // GET /notifications/recent?limit=5
                case ($endpoint === '/recent' && $method === 'GET'):
                    $controller->getRecent();
                    $matched = true;
                    return;

                    // GET /notifications/unread-count
                case ($endpoint === '/unread-count' && $method === 'GET'):
                    $controller->countUnread();
                    $matched = true;
                    return;

                    // POST /notifications
                case ($endpoint === '' && $method === 'POST'):
                    $controller->create();
                    $matched = true;
                    return;

                    // PATCH /notifications/{id}/read
                case (preg_match('#^/(\d+)/read$#', $endpoint, $matches) && $method === 'PATCH'):
                    $controller->markAsRead((int)$matches[1]);
                    $matched = true;
                    return;

                    // DELETE /notifications/{id}
                case (preg_match('#^/(\d+)$#', $endpoint, $matches) && $method === 'DELETE'):
                    $controller->delete((int)$matches[1]);
                    $matched = true;
                    return;

                    // Default: no match
                default:
                    \App\Utils\Logger::warning('Notifications route, no match found', [
                        'endpoint' => $endpoint,
                        'method' => $method
                    ]);
                    http_response_code(404);
                    echo json_encode(['error' => 'Notifications endpoint not found']);
                    $matched = true;
                    return;
            }
        } catch (Throwable $e) {
            \App\Utils\Logger::error('Notifications route error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            http_response_code(500);
            echo json_encode([
                'error' => 'Internal Server Error',
                'message' => $e->getMessage()
            ]);
            $matched = true;
        }
    }
}
