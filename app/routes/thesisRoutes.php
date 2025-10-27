<?php
// app/routes/thesisRoutes.php
require_once __DIR__ . '/../utils/Logger.php';
require_once __DIR__ . '/../controllers/thesisController.php';
require_once __DIR__ . '/../../config/database.php';

function handleThesisRoutes(string $requestPath, string $method): void
{
    global $matched;

    // Create PDO connection and pass to controller
    $pdo = getPDO();
    $controller = new ThesisController($pdo);
    $basePath = '/theses';

    // Match /theses or any sub-route
    if (strpos($requestPath, $basePath) === 0) {
        \App\Utils\Logger::debug('Thesis route matched', [
            'path' => $requestPath,
            'method' => $method
        ]);

        // Handle OPTIONS for preflight requests
        if ($method === 'OPTIONS') {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
            http_response_code(204);
            $matched = true;
            return;
        }

        try {
            switch ($method) {
                case 'GET':
                    $controller->getTheses();
                    break;

                case 'POST':
                    if ($requestPath === '/theses/callnumber') {
                        $controller->addThesisCallNumber();
                    } else {
                        $controller->createThesis();
                    }
                    break;

                case 'PUT':
                    $controller->updateThesis();
                    break;

                case 'DELETE':
                    $controller->deleteThesis();
                    break;

                default:
                    http_response_code(405);
                    echo json_encode(['error' => 'Method Not Allowed']);
                    break;
            }

            $matched = true;
        } catch (Throwable $e) {
            \App\Utils\Logger::error('Thesis route error', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine()
            ]);
            http_response_code(500);
            echo json_encode([
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ]);
            $matched = true;
        }
    }
}
