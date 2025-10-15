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

    switch ($method) {
        case 'GET':
            // List books or get single book by ?id=
            $booksController->getBooks();
            $matched = true;
            exit;
        case 'POST':
            $booksController->createBook();
            $matched = true;
            exit;
        case 'PUT':
            $booksController->updateBook();
            $matched = true;
            exit;
        case 'DELETE':
            $booksController->deleteBook();
            $matched = true;
            exit;
    }
}
