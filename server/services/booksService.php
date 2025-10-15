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

    // Fetch all books (optionally with filters)
    public function getAllBooks($filters = [])
    {
        return $this->model->fetchAllBooks($filters);
    }

    // Fetch a single book by ID
    public function getBookById($id)
    {
        if (!$id) return null;
        return $this->model->fetchBookById($id);
    }

    // Create a new book
    public function createBook($data)
    {
        // Validate and encode fields as needed before passing to model
        return $this->model->insertBook($data);
    }

    // Update an existing book
    public function updateBook($id, $data)
    {
        if (!$id) return null;
        return $this->model->updateBook($id, $data);
    }

    // Delete a book
    public function deleteBook($id)
    {
        if (!$id) return null;
        return $this->model->deleteBook($id);
    }
}
