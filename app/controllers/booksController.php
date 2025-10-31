<?php
// app/controllers/booksController.php

require_once __DIR__ . '/../services/booksService.php';

/**
 * Controller for handling book-related API requests.
 * Manages CRUD operations and search for books.
 * Delegates logic to the BooksService and returns standardized JSON responses.
 */
class BooksController
{
    private BooksService $service;

    public function __construct(PDO $pdo)
    {
        $this->service = new BooksService($pdo);
    }

    // GET /books
    public function getBooks(): void
    {
        try {
            $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
            $pageSize = isset($_GET['pageSize']) ? max(1, min(100, (int) $_GET['pageSize'])) : 20;

            $result = $this->service->getAllBooks(['page' => $page, 'pageSize' => $pageSize]);
            $this->successResponse($result);
        } catch (Throwable $e) {
            $this->logAndRespondError('getBooks', $e);
        }
    }

    // GET /books/{id}
    public function getBookById(int $id): void
    {
        try {
            $book = $this->service->getBookById($id);

            if (!$book) {
                $this->errorResponse('Book not found', 404);
                return;
            }

            $this->successResponse($book);
        } catch (Throwable $e) {
            $this->logAndRespondError('getBookById', $e);
        }
    }

    // GET /books/search?query=
    public function searchBooks(): void
    {
        try {
            $query = trim($_GET['query'] ?? '');

            if ($query === '') {
                $this->errorResponse('Missing search query', 400);
                return;
            }

            $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
            $pageSize = isset($_GET['pageSize']) ? max(1, min(100, (int) $_GET['pageSize'])) : 10;

            $result = $this->service->searchBooks([
                'query' => $query,
                'page' => $page,
                'pageSize' => $pageSize
            ]);

            $this->successResponse($result);
        } catch (Throwable $e) {
            $this->logAndRespondError('searchBooks', $e);
        }
    }

    // POST /books
    public function createBook(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!is_array($data)) {
            $this->errorResponse('Invalid JSON payload', 400);
            return;
        }

        if (empty($data['title'])) {
            $this->errorResponse('Missing required field: title', 400);
            return;
        }

        try {
            $bookId = $this->service->createBook($data);
            $book = $this->service->getBookById($bookId);
            $this->successResponse($book, 201);
        } catch (Throwable $e) {
            $this->logAndRespondError('createBook', $e);
        }
    }

    // PUT /books/{id}
    public function updateBook(int $id): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!is_array($data)) {
            $this->errorResponse('Invalid JSON payload', 400);
            return;
        }

        try {
            $updated = $this->service->updateBook($id, $data);

            if ($updated === null) {
                $this->errorResponse('Book not found', 404);
                return;
            }

            $book = $this->service->getBookById($id);
            $this->successResponse($book);
        } catch (Throwable $e) {
            $this->logAndRespondError('updateBook', $e);
        }
    }

    // DELETE /books/{id}
    public function deleteBook(int $id): void
    {
        try {
            $deleted = $this->service->deleteBook($id);

            if (!$deleted) {
                $this->errorResponse('Book not found', 404);
                return;
            }

            $this->jsonResponse(200, [
                'status' => 'success',
                'message' => 'Book deleted successfully'
            ]);
        } catch (Throwable $e) {
            $this->logAndRespondError('deleteBook', $e);
        }
    }

    public function getTotalCount(): void
    {
        try {
            $count = $this->service->getTotalCount();
            $this->successResponse(['count' => $count]);
        } catch (Throwable $e) {
            $this->logAndRespondError('getTotalCount', $e);
        }
    }

    public function countAllBookCopies(): void
    {
        try {
            $count = $this->service->countAllBookCopies();
            $this->successResponse(['count' => $count]);
        } catch (Throwable $e) {
            $this->logAndRespondError('countAllBookCopies', $e);
        }
    }

    public function countBookCopies(): void
    {
        try {
            $status = isset($_GET['status']) ? trim($_GET['status']) : null;
            $count = $this->service->countBookCopies($status);
            $this->successResponse(['count' => $count]);
        } catch (Throwable $e) {
            $this->logAndRespondError('countBookCopies', $e);
        }
    }

    // Response helpers (single responsibility, reusable)
    private function jsonResponse(int $code, array $payload): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($payload);
    }

    private function successResponse(array $data, int $code = 200): void
    {
        $this->jsonResponse($code, ['status' => 'success', 'data' => $data]);
    }

    private function errorResponse(string $message, int $code = 500): void
    {
        $this->jsonResponse($code, ['status' => 'error', 'message' => $message]);
    }

    private function logAndRespondError(string $context, Throwable $e): void
    {
        error_log("BooksController::{$context} error: " . $e->getMessage());
        $this->errorResponse('Internal server error');
    }
}
