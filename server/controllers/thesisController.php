<?php
// server/controllers/thesisController.php

declare(strict_types=1);

require_once __DIR__ . '/../services/thesisService.php';

/**
 * Controller for handling thesis-related API requests.
 *
 * This class manages CRUD operations for theses, including fetching, creating, updating, and deleting theses.
 * It interacts with the ThesisService to perform business logic and returns JSON responses.
 */
class ThesisController
{
    private ThesisService $service;

    /**
     * Creates an instance of ThesisService.
     */
    public function __construct()
    {
        $this->service = new ThesisService();
    }

    /**
     * Handles GET requests for theses.
     *
     * Fetches a single thesis by ID or accession number, or a paginated list of theses.
     * Supports searching by title and filtering by publication year.
     *
     * @return void
     */
    public function getTheses(): void
    {
        header('Content-Type: application/json');

        // Single thesis by ID
        $id = isset($_GET['thesis_id']) ? (int)$_GET['thesis_id'] : null;
        if ($id) {
            $thesis = $this->service->getThesisById($id);
            if (!$thesis) {
                http_response_code(404);
                echo json_encode(['error' => 'Thesis not found']);
                return;
            }
            http_response_code(200);
            echo json_encode($thesis);
            return;
        }

        // Single thesis by accession_no
        $acc = isset($_GET['accession_no']) ? trim((string)$_GET['accession_no']) : '';
        if ($acc !== '') {
            $thesis = $this->service->getThesisByAccession($acc);
            if (!$thesis) {
                http_response_code(404);
                echo json_encode(['error' => 'Thesis not found']);
                return;
            }
            http_response_code(200);
            echo json_encode($thesis);
            return;
        }

        // Pagination and filters
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $pageSize = isset($_GET['pageSize']) ? max(1, min(100, (int)$_GET['pageSize'])) : 20;
        $title = isset($_GET['title']) ? trim((string)$_GET['title']) : '';
        $year = isset($_GET['year']) ? (int)$_GET['year'] : null;

        try {
            $result = $this->service->getAllTheses([
                'page' => $page,
                'pageSize' => $pageSize,
                'title' => $title,
                'year' => $year,
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

    /**
     * Handles POST requests to create a new thesis.
     *
     * @return void
     */
    public function createThesis(): void
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
            $thesisId = $this->service->createThesis($data);
            if ($thesisId === null) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid thesis payload']);
                return;
            }
            http_response_code(201);
            echo json_encode(['thesis_id' => $thesisId]);
            return;
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
            return;
        }
    }

    /**
     * Handles PUT requests to update an existing thesis.
     *
     * @return void
     */
    public function updateThesis(): void
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

        $id = isset($data['thesis_id']) ? (int)$data['thesis_id'] : 0;
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing or invalid thesis_id']);
            return;
        }

        try {
            $ok = $this->service->updateThesis($id, $data);
            http_response_code(200);
            echo json_encode(['updated' => (bool)$ok]);
            return;
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
            return;
        }
    }

    /**
     * Handles DELETE requests to remove a thesis.
     *
     * @return void
     */
    public function deleteThesis(): void
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

        $id = isset($data['thesis_id']) ? (int)$data['thesis_id'] : 0;
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing or invalid thesis_id']);
            return;
        }

        try {
            $ok = $this->service->deleteThesis($id);
            http_response_code(200);
            echo json_encode(['deleted' => (bool)$ok]);
            return;
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
            return;
        }
    }
}
