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

    // Fetch all books, or single book by id
    public function getBooks()
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : null;
        $result = $id ? $this->service->getBookById($id) : $this->service->getAllBooks();
        header('Content-Type: application/json');
        echo json_encode($result);
    }

    // Create a new book record
    public function createBook()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $result = $this->service->createBook($data);
        header('Content-Type: application/json');
        echo json_encode($result);
    }

    // Update an existing book
    public function updateBook()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $id = $data['id'] ?? null;
        $result = $this->service->updateBook($id, $data);
        header('Content-Type: application/json');
        echo json_encode($result);
    }

    // Delete a book (by id)
    public function deleteBook()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $id = $data['id'] ?? null;
        $result = $this->service->deleteBook($id);
        header('Content-Type: application/json');
        echo json_encode($result);
    }
}
