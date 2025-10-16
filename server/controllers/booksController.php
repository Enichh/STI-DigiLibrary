<?php
// server/controllers/booksController.php

require_once __DIR__ . '/../services/booksService.php';

class BooksController
{
    private $service;

    public function __construct()
    {
        $this->service = new BooksService();
    }

    // GET /api/books
    // Parameters:
    // - id (optional): fetch single book by id
    // - page, pageSize (optional): pagination for list/search
    // - q (optional): smart box, treated as ISBN if looks like ISBN, otherwise title
    // - title, author, isbn (optional): precise search filters
    public function getBooks(): void
    {
        header('Content-Type: application/json');

        // Single book fetch
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        if ($id) {
            $book = $this->service->getBookById($id);
            if (!$book) {
                http_response_code(404);
                echo json_encode(['error' => 'Book not found']);
                return;
            }
            http_response_code(200);
            echo json_encode($book);
            return;
        }

        // Pagination guards
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $pageSize = isset($_GET['pageSize']) ? max(1, min(100, (int)$_GET['pageSize'])) : 20;

        // Search inputs
        $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        $title = isset($_GET['title']) ? trim((string)$_GET['title']) : '';
        $author = isset($_GET['author']) ? trim((string)$_GET['author']) : '';
        $isbn = isset($_GET['isbn']) ? trim((string)$_GET['isbn']) : '';

        // Heuristic: if q present and no explicit fields, map q to isbn or title
        if ($q !== '' && $title === '' && $author === '' && $isbn === '') {
            if (preg_match('/^[0-9Xx-]{9,17}$/', $q)) {
                $isbn = $q;
            } else {
                $title = $q;
            }
        }

        // If any of title/author/isbn provided, route to search
        $isSearch = ($title !== '' || $author !== '' || $isbn !== '');
        if ($isSearch) {
            try {
                $result = $this->service->searchBooks([
                    'title' => $title,
                    'author' => $author,
                    'isbn' => $isbn,
                    'page' => $page,
                    'pageSize' => $pageSize,
                ]);
                http_response_code(200);
                echo json_encode($result);
                return;
            } catch (Throwable $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Internal server error']);
                return;
            }
        }

        // Default list (no search filters)
        try {
            $result = $this->service->getAllBooks([
                'page' => $page,
                'pageSize' => $pageSize,
                // Optionally: 'search' => $_GET['search'] ?? '' // if you keep a generic search
            ]);
            http_response_code(200);
            echo json_encode($result);
            return;
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
            return;
        }
    }

    // POST /api/books
    public function createBook(): void
    {
        header('Content-Type: application/json');

        $payload = file_get_contents("php://input");
        if ($payload === false) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid request body']);
            return;
        }

        $data = json_decode($payload, true);
        if (!is_array($data)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON']);
            return;
        }

        try {
            $result = $this->service->createBook($data);
            http_response_code(201);
            echo json_encode($result);
            return;
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
            return;
        }
    }

    // PUT /api/books
    public function updateBook(): void
    {
        header('Content-Type: application/json');

        $payload = file_get_contents("php://input");
        if ($payload === false) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid request body']);
            return;
        }

        $data = json_decode($payload, true);
        if (!is_array($data)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON']);
            return;
        }

        $id = isset($data['id']) ? (int)$data['id'] : 0;
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing or invalid id']);
            return;
        }

        try {
            $result = $this->service->updateBook($id, $data);
            http_response_code(200);
            echo json_encode($result);
            return;
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
            return;
        }
    }

    // DELETE /api/books
    public function deleteBook(): void
    {
        header('Content-Type: application/json');

        $payload = file_get_contents("php://input");
        if ($payload === false) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid request body']);
            return;
        }

        $data = json_decode($payload, true);
        if (!is_array($data)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON']);
            return;
        }

        $id = isset($data['id']) ? (int)$data['id'] : 0;
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing or invalid id']);
            return;
        }

        try {
            $result = $this->service->deleteBook($id);
            http_response_code(200);
            echo json_encode($result);
            return;
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
            return;
        }
    }
}
