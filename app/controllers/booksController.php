<?php
// app/controllers/booksController.php

require_once __DIR__ . '/../services/booksService.php';

/**
 * Controller for handling book-related API requests.
 *
 * Manages CRUD operations and search for books.
 * Delegates logic to the BooksService and returns standardized JSON responses.
 */
class BooksController
{
    private $service;

    /**
     * Initializes the controller with its service dependency.
     */
    public function __construct(PDO $pdo)
    {
        $this->service = new BooksService($pdo);
    }

    /**
     * GET /books
     * Fetches paginated list of books.
     */
    public function getBooks(): void
    {
        try {
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $pageSize = isset($_GET['pageSize']) ? max(1, min(100, (int)$_GET['pageSize'])) : 20;

            $result = $this->service->getAllBooks([
                'page' => $page,
                'pageSize' => $pageSize
            ]);

            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'data' => $result
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Internal server error',
                'details' => $e->getMessage()
            ]);
        }
    }

    /**
     * GET /books/{id}
     * Fetches a specific book by its ID.
     */
    public function getBookById(int $id): void
    {
        try {
            $book = $this->service->getBookById($id);

            if (!$book) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Book not found']);
                return;
            }

            http_response_code(200);
            echo json_encode(['status' => 'success', 'data' => $book]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Internal server error',
                'details' => $e->getMessage()
            ]);
        }
    }

    /**
     * GET /books/search?query=
     * Performs a book search by title, author, or ISBN.
     */
    public function searchBooks(): void
    {
        try {
            $query = trim($_GET['query'] ?? '');

            if ($query === '') {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Missing search query']);
                return;
            }

            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $pageSize = isset($_GET['pageSize']) ? max(1, min(100, (int)$_GET['pageSize'])) : 10;

            $result = $this->service->searchBooks([
                'query' => $query,
                'page' => $page,
                'pageSize' => $pageSize
            ]);

            http_response_code(200);
            echo json_encode(['status' => 'success', 'data' => $result]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Internal server error',
                'details' => $e->getMessage()
            ]);
        }
    }

    /**
     * POST /books
     * Creates a new book record.
     */
    public function createBook(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!is_array($data)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid JSON payload']);
            return;
        }

        try {
            $book = $this->service->createBook($data);
            http_response_code(201);
            echo json_encode(['status' => 'success', 'data' => $book]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Internal server error',
                'details' => $e->getMessage()
            ]);
        }
    }

    /**
     * PUT /books/{id}
     * Updates an existing book.
     */
    public function updateBook(int $id): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!is_array($data)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid JSON payload']);
            return;
        }

        try {
            $updated = $this->service->updateBook($id, $data);
            http_response_code(200);
            echo json_encode(['status' => 'success', 'data' => $updated]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Internal server error',
                'details' => $e->getMessage()
            ]);
        }
    }

    /**
     * DELETE /books/{id}
     * Deletes a book by ID.
     */
    public function deleteBook(int $id): void
    {
        try {
            $deleted = $this->service->deleteBook($id);

            if (!$deleted) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Book not found']);
                return;
            }

            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => 'Book deleted successfully']);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Internal server error',
                'details' => $e->getMessage()
            ]);
        }
    }
}
