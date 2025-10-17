<?php
// server/services/booksService.php

require_once __DIR__ . '/../models/booksModel.php';

/**
 * Service for handling book-related business logic.
 *
 * This class provides methods for fetching, searching, creating, updating, and deleting books.
 * It acts as an intermediary between the BooksController and the BooksModel.
 */
class BooksService
{
    private $model;

    /**
     * Creates an instance of BooksService and initializes the BooksModel.
     */
    public function __construct()
    {
        $this->model = new BooksModel();
    }

    /**
     * Fetches all books with pagination and optional generic search.
     *
     * @param array $filters An associative array of filters, including 'search', 'page', and 'pageSize'.
     * @return array An array containing the book data and pagination information.
     */
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

    /**
     * Searches for books by title, author, or ISBN with pagination.
     *
     * @param array $filters An associative array of search filters, including 'title', 'author', 'isbn', 'page', and 'pageSize'.
     * @return array An array containing the search results and pagination information.
     */
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

    /**
     * Fetches a single book by its ID.
     *
     * @param int $id The ID of the book to fetch.
     * @return array|null The book data, or null if not found.
     */
    public function getBookById($id): ?array
    {
        $bookId = (int)$id;
        if ($bookId <= 0) {
            return null;
        }
        return $this->model->fetchBookById($bookId);
    }

    /**
     * Creates a new book.
     *
     * @param array $data An associative array of book data.
     * @return int The ID of the newly created book.
     */
    public function createBook(array $data)
    {
        // Minimal validation; extend as needed
        return $this->model->insertBook($data);
    }

    /**
     * Updates an existing book.
     *
     * @param int $id The ID of the book to update.
     * @param array $data An associative array of book data.
     * @return bool|null True on success, false on failure, or null if the ID is invalid.
     */
    public function updateBook($id, array $data)
    {
        $bookId = (int)$id;
        if ($bookId <= 0) {
            return null;
        }
        return $this->model->updateBook($bookId, $data);
    }

    /**
     * Deletes a book.
     *
     * @param int $id The ID of the book to delete.
     * @return bool|null True on success, false on failure, or null if the ID is invalid.
     */
    public function deleteBook($id): ?bool
    {
        $bookId = (int)$id;
        if ($bookId <= 0) {
            return null;
        }
        return $this->model->deleteBook($bookId);
    }
}
