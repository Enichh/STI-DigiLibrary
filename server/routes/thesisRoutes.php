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
            return;
        }

        switch ($method) {
            case 'GET':
                $controller->getTheses();
                $matched = true;
                return;
            case 'POST':
                header('Content-Type: application/json');
                $controller->createThesis();
                $matched = true;
                return;
            case 'PUT':
                header('Content-Type: application/json');
                $controller->updateThesis();
                $matched = true;
                return;
            case 'DELETE':
                header('Content-Type: application/json');
                $controller->deleteThesis();
                $matched = true;
                return;
            default:
                header('Content-Type: application/json');
                http_response_code(405);
                echo json_encode(['error' => 'Method Not Allowed']);
                $matched = true;
                return;
        }
    }
}
