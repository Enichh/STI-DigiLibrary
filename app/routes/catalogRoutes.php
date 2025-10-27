<?php
// app/routes/catalogRoutes.php

/**
 * Catalog API Routes
 * Handles browsing and searching for books/theses
 */

function handleCatalogRoutes(string $path, string $method): void
{
    global $matched;

    // Load dependencies
    require_once __DIR__ . '/../controllers/catalogController.php';
    require_once __DIR__ . '/../services/booksService.php';
    require_once __DIR__ . '/../services/thesisService.php';
    require_once __DIR__ . '/../../config/database.php';

    // Create ONE PDO connection and reuse
    $pdo = getPDO();

    // Pass PDO to the controller
    $controller = new CatalogController($pdo);

    // GET /catalog/stats - Get catalog statistics
    if ($path === '/catalog/stats' && $method === 'GET') {
        $matched = true;
        $controller->getStats();
        return;
    }

    // GET /catalog/items - Get catalog items (books or theses)
    if ($path === '/catalog/items' && $method === 'GET') {
        $matched = true;
        $controller->getCatalogItems();
        return;
    }

    // GET /catalog/tags - Get available genres (books) or empty list (theses)
    if ($path === '/catalog/tags' && $method === 'GET') {
        $matched = true;
        $controller->getTags();
        return;
    }
}
