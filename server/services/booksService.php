<?php
// server/services/booksService.php

require_once __DIR__ . '/../models/booksModel.php';

class BooksService
{
    private $model;

    public function __construct()
    {
        $this->model = new BooksModel();
    }

    // Fetch all books with pagination and optional generic search
    // filters may include:
    // - search: generic search string (title/isbn/publisher)
    // - page, pageSize: pagination controls
    public function getAllBooks(array $filters = []): array
    {
        $page = isset($filters['page']) ? max(1, (int)$filters['page']) : 1;
        $pageSize = isset($filters['pageSize']) ? max(1, min(100, (int)$filters['pageSize'])) : 20;

        unset($filters['page'], $filters['pageSize']);

        $books = $this->model->fetchAllBooks($filters, $page, $pageSize);
        $total = $this->model->countBooks($filters);

        return [
            'data' => $books,
            'pagination' => [
                'page' => (int)$page,
                'pageSize' => (int)$pageSize,
                'totalItems' => (int)$total,
                'totalPages' => (int)ceil($total / $pageSize),
            ],
        ];
    }

    // Precise search by title, author, or isbn with pagination
    // filters should include any of: title, author, isbn
    // plus page, pageSize
    public function searchBooks(array $filters = []): array
    {
        $page = isset($filters['page']) ? max(1, (int)$filters['page']) : 1;
        $pageSize = isset($filters['pageSize']) ? max(1, min(100, (int)$filters['pageSize'])) : 20;

        unset($filters['page'], $filters['pageSize']);

        $items = $this->model->searchBooks($filters, $page, $pageSize);
        $total = $this->model->countSearchBooks($filters);

        return [
            'data' => $items,
            'pagination' => [
                'page' => (int)$page,
                'pageSize' => (int)$pageSize,
                'totalItems' => (int)$total,
                'totalPages' => (int)ceil($total / $pageSize),
            ],
        ];
    }

    // Fetch a single book by ID
    public function getBookById($id): ?array
    {
        $bookId = (int)$id;
        if ($bookId <= 0) {
            return null;
        }
        return $this->model->fetchBookById($bookId);
    }

    // Create a new book
    public function createBook(array $data)
    {
        // Minimal validation; extend as needed
        return $this->model->insertBook($data);
    }

    // Update an existing book
    public function updateBook($id, array $data)
    {
        $bookId = (int)$id;
        if ($bookId <= 0) {
            return null;
        }
        return $this->model->updateBook($bookId, $data);
    }

    // Delete a book
    public function deleteBook($id): ?bool
    {
        $bookId = (int)$id;
        if ($bookId <= 0) {
            return null;
        }
        return $this->model->deleteBook($bookId);
    }
}
