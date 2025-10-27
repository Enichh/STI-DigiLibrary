<?php
// app/routes/fineRoutes.php

require_once __DIR__ . '/../controllers/fineController.php';
require_once __DIR__ . '/../services/fineService.php';
require_once __DIR__ . '/../models/fineModel.php';
require_once __DIR__ . '/../models/loanModel.php';
require_once __DIR__ . '/../../config/database.php';

/**
 * Handles all fine-related API endpoints.
 *
 * @param string $requestPath The incoming request path (e.g., /fines/process-overdues)
 * @param string $method The HTTP method (GET, POST, DELETE, etc.)
 * @return void
 */
function handleFineRoutes(string $requestPath, string $method): void
{
    global $matched;

    $pdo = getPDO();
    $fineModel = new FineModel($pdo);
    $loanModel = new LoanModel($pdo);
    $fineService = new FineService($fineModel, $loanModel);
    $fineController = new FineController($fineService);

    $basePath = '/fines';

    if (strpos($requestPath, $basePath) === 0) {
        $path = parse_url($requestPath, PHP_URL_PATH);
        $endpoint = rtrim(substr($path, strlen($basePath)), '/');

        // CORS handling
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
                // POST /fines/process-overdues
                // Auto-process overdue borrowings and create/update fines
                case ($endpoint === '/process-overdues' && $method === 'POST'):
                    $fineController->processOverdues();
                    $matched = true;
                    return;

                    // GET /fines/user/{userId} → List all fines for a user
                case (preg_match('#^/user/(\d+)$#', $endpoint, $matches) && $method === 'GET'):
                    $fineController->userFines((int)$matches[1]);
                    $matched = true;
                    return;

                    // GET /fines/{fineId} → Fetch details for a specific fine
                case (preg_match('#^/(\d+)$#', $endpoint, $matches) && $method === 'GET'):
                    $fineController->getFine((int)$matches[1]);
                    $matched = true;
                    return;

                    // POST /fines/{fineId}/pay → Mark fine as paid
                case (preg_match('#^/(\d+)/pay$#', $endpoint, $matches) && $method === 'POST'):
                    $fineController->markPaid((int)$matches[1]);
                    $matched = true;
                    return;

                    // POST /fines/{fineId}/waive → Waive an existing fine
                case (preg_match('#^/(\d+)/waive$#', $endpoint, $matches) && $method === 'POST'):
                    $fineController->waive((int)$matches[1]);
                    $matched = true;
                    return;

                    // DELETE /fines/{fineId} → Delete a fine record
                case (preg_match('#^/(\d+)$#', $endpoint, $matches) && $method === 'DELETE'):
                    $fineController->delete((int)$matches[1]);
                    $matched = true;
                    return;

                    // GET /fines?status={status} → List all fines (admin)
                case ($endpoint === '' && $method === 'GET'):
                    $fineController->allFines();
                    $matched = true;
                    return;

                    // Fallback for unmatched routes
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Fine endpoint not found']);
                    $matched = true;
                    return;
            }
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Internal Server Error',
                'message' => $e->getMessage(),
            ]);
            $matched = true;
        }
    }
}
