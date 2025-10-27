<?php
// app/controllers/catalogController.php

class CatalogController
{
    private BooksService $booksService;
    private ThesisService $thesisService;

    /**
     * Pass PDO when constructing this controller.
     */
    public function __construct(PDO $pdo)
    {
        $this->booksService = new BooksService($pdo);
        $this->thesisService = new ThesisService($pdo);
    }

    public function index()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        require_once __DIR__ . '/../views/catalog.php';
    }

    public function getStats()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $totalResources = $this->booksService->getTotalCount() + $this->thesisService->getTotalCount();
        $availableBooks = $this->booksService->getAvailableCount();

        echo json_encode([
            'success' => true,
            'totalResources' => $totalResources,
            'availableBooks' => $availableBooks
        ]);
    }

    public function getCatalogItems()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $type = $_GET['type'] ?? 'book'; // 'book' or 'thesis'
        $search = htmlspecialchars(trim($_GET['search'] ?? ''), ENT_QUOTES, 'UTF-8');
        $genre = htmlspecialchars(trim($_GET['genre'] ?? ($_GET['tag'] ?? '')), ENT_QUOTES, 'UTF-8');
        $availableOnly = isset($_GET['available_only']) && $_GET['available_only'] === 'true';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 12;

        $filters = [
            'search' => $search,
            'page' => $page,
            'limit' => $limit
        ];

        if ($type === 'book') {
            $filters['genre'] = $genre;
            $filters['available_only'] = $availableOnly;
            $result = $this->booksService->getCatalogItems($filters);
        } else {
            // For theses, only search/year-based filters; no genre/tags.
            $year = isset($_GET['year']) ? (int)$_GET['year'] : null;
            if ($year) $filters['year'] = $year;
            $result = $this->thesisService->getCatalogItems($filters);
        }

        echo json_encode($result);
    }

    public function getTags()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $type = $_GET['type'] ?? 'book';

        if ($type === 'thesis') {
            // Theses do not have tags/genre; return empty.
            $tags = [];
        } else {
            // Books: Provide all genres as tags for compatibility.
            $tags = $this->booksService->getAllGenres();
        }

        echo json_encode([
            'success' => true,
            'tags' => $tags
        ]);
    }
}
