<?php
// app/routes/booksRoutes.php

require_once __DIR__ . '/../utils/Logger.php';
require_once __DIR__ . '/../controllers/booksController.php';
require_once __DIR__ . '/../../config/database.php';

/**
 * Handles book-related API routes.
 *
 * @param string $requestPath The request URI path
 * @param string $method The HTTP request method
 * @return void
 */
function handleBooksRoutes(string $requestPath, string $method): void
{
    global $matched;

    // Create PDO connection and pass to controller
    $pdo = getPDO();
    $booksController = new BooksController($pdo);
    $basePath = '/books';

    // Match /books routes
    if (strpos($requestPath, $basePath) === 0) {
        \App\Utils\Logger::debug('Books route matched', [
            'path' => $requestPath,
            'method' => $method
        ]);

        // Extract normalized endpoint (e.g. /books/{id} or /books/search)
        $path = parse_url($requestPath, PHP_URL_PATH);
        $endpoint = rtrim(substr($path, strlen($basePath)), '/');

        // Handle CORS preflight requests
        if ($method === 'OPTIONS') {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
            http_response_code(204);
            $matched = true;
            return;
        }

        try {
            // Route definitions
            switch (true) {
                // GET /books → Get all books
                case ($endpoint === '' && $method === 'GET'):
                    $booksController->getBooks();
                    $matched = true;
                    return;

                    // GET /books/{id} → Get book by ID
                case (preg_match('#^/(\d+)$#', $endpoint, $matches) && $method === 'GET'):
                    $booksController->getBookById((int)$matches[1]);
                    $matched = true;
                    return;

                    // POST /books → Create a new book
                case ($endpoint === '' && $method === 'POST'):
                    $booksController->createBook();
                    $matched = true;
                    return;

                    // PUT /books/{id} → Update existing book
                case (preg_match('#^/(\d+)$#', $endpoint, $matches) && $method === 'PUT'):
                    $booksController->updateBook((int)$matches[1]);
                    $matched = true;
                    return;

                    // DELETE /books/{id} → Delete book by ID
                case (preg_match('#^/(\d+)$#', $endpoint, $matches) && $method === 'DELETE'):
                    $booksController->deleteBook((int)$matches[1]);
                    $matched = true;
                    return;

                    // GET /books/search?query= → Search books
                case ($endpoint === '/search' && $method === 'GET'):
                    $booksController->searchBooks();
                    $matched = true;
                    return;

                    // Handle unmatched routes
                default:
                    \App\Utils\Logger::warning('Books route, no match found', [
                        'endpoint' => $endpoint,
                        'method' => $method
                    ]);
                    http_response_code(404);
                    echo json_encode(['error' => 'Books endpoint not found']);
                    $matched = true;
                    return;
            }
        } catch (Throwable $e) {
            \App\Utils\Logger::error('Books route error', [
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
