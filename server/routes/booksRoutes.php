<?php
// server/routes/booksRoutes.php

require_once __DIR__ . '/../utils/Logger.php';
require_once __DIR__ . '/../controllers/booksController.php';

$booksController = new BooksController();
global $matched, $requestPath, $method;

$basePath = '/books.php';

if ($requestPath === $basePath) {
    \App\Utils\Logger::debug('Books route matched', [
        'path' => $requestPath,
        'method' => $method
    ]);

    // Handle CORS preflight if applicable
    if ($method === 'OPTIONS') {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        http_response_code(204);
        $matched = true;
        exit;
    }

    switch ($method) {
        case 'GET':
            $booksController->getBooks();
            $matched = true;
            exit;

        case 'POST':
            header('Content-Type: application/json');
            $booksController->createBook();
            $matched = true;
            exit;

        case 'PUT':
            header('Content-Type: application/json');
            $booksController->updateBook();
            $matched = true;
            exit;

        case 'DELETE':
            header('Content-Type: application/json');
            $booksController->deleteBook();
            $matched = true;
            exit;

        default:
            header('Content-Type: application/json');
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
            $matched = true;
            exit;
    }
}
