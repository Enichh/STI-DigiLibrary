<?php
// app/routes/thesisRoutes.php
require_once __DIR__ . '/../utils/Logger.php';
require_once __DIR__ . '/../controllers/thesisController.php';
require_once __DIR__ . '/../../config/database.php';

function handleThesisRoutes(string $requestPath, string $method): void
{
    global $matched;

    $pdo = getPDO();
    $controller = new ThesisController($pdo);
    $basePath = '/theses';

    if (strpos($requestPath, $basePath) === 0) {
        $endpoint = substr($requestPath, strlen($basePath));
        // e.g. "/count" if requestPath = "/theses/count"

        try {
            switch (true) {
                case ($endpoint === '' && $method === 'GET'):
                    $controller->getTheses();
                    break;

                case ($endpoint === '/count' && $method === 'GET'):
                    $controller->getTotalThesis();
                    break;

                case ($endpoint === '/callnumber' && $method === 'POST'):
                    $controller->addThesisCallNumber();
                    break;

                case ($endpoint === '' && $method === 'POST'):
                    $controller->createThesis();
                    break;

                case ($endpoint === '' && $method === 'PUT'):
                    $controller->updateThesis();
                    break;

                case ($endpoint === '' && $method === 'DELETE'):
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
