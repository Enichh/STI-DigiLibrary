<?php
// server/routes/thesisRoutes.php
require_once __DIR__ . '/../utils/Logger.php';
require_once __DIR__ . '/../controllers/thesisController.php';

function handleThesisRoutes(string $requestPath, string $method): void
{
    global $matched;

    $controller = new ThesisController();
    $basePath = '/theses.php';

    if ($requestPath === $basePath) {
        \App\Utils\Logger::debug('Thesis route matched', ['path' => $requestPath, 'method' => $method]);

        if ($method === 'OPTIONS') {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
            http_response_code(204);
            $matched = true;
            exit;
        }

        try {
            switch ($method) {
                case 'GET':
                    $controller->getTheses();
                    $matched = true;
                    exit;
                case 'POST':
                    header('Content-Type: application/json');
                    if ($requestPath === '/theses.php/callnumber') {
                        $controller->addThesisCallNumber();
                    } elseif ($requestPath === '/theses.php') {
                        $controller->createThesis();
                    } else {
                        http_response_code(404);
                        echo json_encode(['error' => 'Endpoint not found']);
                    }
                    $matched = true;
                    exit;

                case 'PUT':
                    header('Content-Type: application/json');
                    $controller->updateThesis();
                    $matched = true;
                    exit;
                case 'DELETE':
                    header('Content-Type: application/json');
                    $controller->deleteThesis();
                    $matched = true;
                    exit;
                default:
                    header('Content-Type: application/json');
                    http_response_code(405);
                    echo json_encode(['error' => 'Method Not Allowed']);
                    $matched = true;
                    exit;
            }
        } catch (Throwable $e) {
            error_log("Thesis route error: " . $e->getMessage());
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
            $matched = true;
            exit;
        }
    }
}
