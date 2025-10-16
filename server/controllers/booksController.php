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
        
        if ($id) {
            $result = $this->service->getBookById($id);
        } else {
            // Get pagination parameters with defaults
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $pageSize = isset($_GET['pageSize']) ? max(1, min(100, intval($_GET['pageSize']))) : 20;
            
            $result = $this->service->getAllBooks([
                'page' => $page,
                'pageSize' => $pageSize
            ]);
        }
        
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
