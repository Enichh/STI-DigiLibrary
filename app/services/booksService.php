<?php
// app/services/booksService.php

require_once __DIR__ . '/../models/booksModel.php';

/**
 * Service for handling book-related business logic.
 * Methods for fetching, searching, creating, updating, and deleting books.
 * All filters now use 'genre' (not tag/subject).
 * Intermediary between BooksController and BooksModel.
 */
class BooksService
{
    private BooksModel $model;

    public function __construct(PDO $pdo)
    {
        $this->model = new BooksModel($pdo);
    }

    public function getAllBooks(array $filters = []): array
    {
        $page = isset($filters['page']) ? max(1, (int)$filters['page']) : 1;
        $pageSize = isset($filters['pageSize']) ? max(1, min(100, (int)$filters['pageSize'])) : 20;

        $books = $this->model->fetchAllBooks($this->refactorFilters($filters), $page, $pageSize);
        $total = $this->model->countBooks($this->refactorFilters($filters));

        return [
            'data' => $books,
            'pagination' => [
                'page' => $page,
                'pageSize' => $pageSize,
                'totalItems' => $total,
                'totalPages' => (int)ceil($total / $pageSize),
            ],
        ];
    }

    public function searchBooks(array $filters = []): array
    {
        $page = isset($filters['page']) ? max(1, (int)$filters['page']) : 1;
        $pageSize = isset($filters['pageSize']) ? max(1, min(100, (int)$filters['pageSize'])) : 20;

        $items = $this->model->searchBooks($this->refactorFilters($filters), $page, $pageSize);
        $total = $this->model->countSearchBooks($this->refactorFilters($filters));

        return [
            'data' => $items,
            'pagination' => [
                'page' => $page,
                'pageSize' => $pageSize,
                'totalItems' => $total,
                'totalPages' => (int)ceil($total / $pageSize),
            ],
        ];
    }

    public function getBookById($id): ?array
    {
        $bookId = (int)$id;
        if ($bookId <= 0) return null;
        return $this->model->fetchBookById($bookId);
    }

    public function createBook(array $data): int
    {
        return $this->model->insertBook($data);
    }
    public function updateBook($id, array $data): ?array
    {
        $bookId = (int)$id;
        if ($bookId <= 0) {
            return null;
        }

        // Verify book exists before updating
        $existingBook = $this->model->fetchBookById($bookId); // Use fetchBookById not getBookById
        if (!$existingBook) {
            return null;
        }

        // Attempt the update
        $this->model->updateBook($bookId, $data); // Returns void

        // Return the updated book
        return $this->model->fetchBookById($bookId); // Use fetchBookById
    }


    public function deleteBook($id): ?bool
    {
        $bookId = (int)$id;
        if ($bookId <= 0) return null;
        return $this->model->deleteBook($bookId);
    }

    public function getTotalCount(): int
    {
        return $this->model->getTotalCount();
    }

    public function getAvailableCount(): int
    {
        return $this->model->getAvailableCount();
    }

    /**
     * Get catalog items with filters for the catalog page—using genre not tag.
     * @param array $filters: search, genre, available_only, page, limit
     * @return array
     */
    public function getCatalogItems(array $filters = []): array
    {
        $page  = isset($filters['page']) ? max(1, (int)$filters['page']) : 1;
        $limit = isset($filters['limit']) ? max(1, min(100, (int)$filters['limit'])) : 12;
        $offset = ($page - 1) * $limit;

        $search       = isset($filters['search']) ? trim($filters['search']) : '';
        $genre        = isset($filters['genre']) ? trim($filters['genre']) : '';
        $availableOnly = isset($filters['available_only']) ? (bool)$filters['available_only'] : false;

        $modelFilters = [
            'search' => htmlspecialchars($search, ENT_QUOTES, 'UTF-8'),
            'genre' => htmlspecialchars($genre, ENT_QUOTES, 'UTF-8'),
            'available_only' => $availableOnly
        ];

        $items = $this->model->getCatalogItems($modelFilters, $limit, $offset);
        $total = $this->model->countCatalogItems($modelFilters);

        return [
            'success' => true,
            'data' => $items,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => (int)ceil($total / $limit)
            ],
            'total' => $total
        ];
    }

    /**
     * Get all unique genres from books.
     * @return array
     */
    public function getAllGenres(): array
    {
        return $this->model->getAllGenres();
    }

    /**
     * Helper to map legacy filters to genre.
     */
    private function refactorFilters(array $filters): array
    {
        $refactored = $filters;
        foreach (['tag', 'subject'] as $obsoleteKey) {
            if (isset($refactored[$obsoleteKey])) {
                $refactored['genre'] = $refactored[$obsoleteKey];
                unset($refactored[$obsoleteKey]);
            }
        }
        return $refactored;
    }


    public function countAllBookCopies(): int
    {
        return $this->model->countAllBookCopies();
    }

    public function countBookCopies(?string $status = null): int
    {
        // Count copies across all books, optionally filtered by status
        return $this->model->countCopiesByStatus($status);
    }
}
