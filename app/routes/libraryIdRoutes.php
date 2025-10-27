<?php
// app/routes/libraryIdRoutes.php

require_once __DIR__ . '/../controllers/libraryIdController.php';
require_once __DIR__ . '/../services/libraryIdService.php';
require_once __DIR__ . '/../models/libraryIdModel.php';
require_once __DIR__ . '/../../config/database.php';

/**
 * Handles library ID (digital ID) related API routes.
 *
 * @param string $requestPath The request URI path
 * @param string $method The HTTP request method
 * @return void
 */
function handleLibraryIdRoutes(string $requestPath, string $method): void
{
    global $matched;

    $pdo = getPDO();
    $libraryIdModel = new LibraryIdModel($pdo);
    $libraryIdService = new LibraryIdService($libraryIdModel);
    $libraryIdController = new LibraryIdController($libraryIdService);

    $basePath = '/library-ids';

    // Match /library-ids routes
    if (strpos($requestPath, $basePath) === 0) {
        // Extract normalized endpoint
        $path = parse_url($requestPath, PHP_URL_PATH);
        $endpoint = rtrim(substr($path, strlen($basePath)), '/');

        // CORS preflight
        if ($method === 'OPTIONS') {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
            http_response_code(204);
            $matched = true;
            return;
        }

        try {
            switch (true) {
                // POST /library-ids/request → User requests new library ID
                case ($endpoint === '/request' && $method === 'POST'):
                    echo json_encode($libraryIdController->requestLibraryId());
                    $matched = true;
                    return;

                    // GET /library-ids/user/{userId} → Get user's active library ID
                case (preg_match('#^/user/(\d+)$#', $endpoint, $matches) && $method === 'GET'):
                    echo json_encode($libraryIdController->getUserLibraryId((int)$matches[1]));
                    $matched = true;
                    return;

                    // POST /library-ids/{id}/approve → Admin approves a pending library ID
                case (preg_match('#^/(\d+)/approve$#', $endpoint, $matches) && $method === 'POST'):
                    // You may want to pass adminUserId from session here as well!
                    $adminUserId = $_SESSION['user_id'] ?? null;
                    echo json_encode($libraryIdController->approveLibraryId((int)$matches[1], $adminUserId));
                    $matched = true;
                    return;

                    // POST /library-ids/{id}/status → Admin updates ID status
                case (preg_match('#^/(\d+)/status$#', $endpoint, $matches) && $method === 'POST'):
                    echo json_encode($libraryIdController->updateStatus((int)$matches[1]));
                    $matched = true;
                    return;

                    // GET /library-ids/{id} → Get library ID by primary key
                case (preg_match('#^/(\d+)$#', $endpoint, $matches) && $method === 'GET'):
                    echo json_encode($libraryIdController->getLibraryIdById((int)$matches[1]));
                    $matched = true;
                    return;

                    // GET /library-ids → Admin: list all IDs (optionally by status)
                case ($endpoint === '' && $method === 'GET'):
                    $status = $_GET['status'] ?? null;
                    echo json_encode($libraryIdController->listLibraryIds($status));
                    $matched = true;
                    return;

                    // DELETE /library-ids/{id} → Admin: delete library ID
                case (preg_match('#^/(\d+)$#', $endpoint, $matches) && $method === 'DELETE'):
                    echo json_encode($libraryIdController->deleteLibraryId((int)$matches[1]));
                    $matched = true;
                    return;

                    // Handle unmatched routes
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Library ID endpoint not found']);
                    $matched = true;
                    return;
            }
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Internal Server Error',
                'message' => $e->getMessage()
            ]);
            $matched = true;
        }
    }
}
