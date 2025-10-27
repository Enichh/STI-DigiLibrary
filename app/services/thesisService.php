<?php
// app/services/thesisService.php

declare(strict_types=1);

require_once __DIR__ . '/../models/thesisModel.php';

/**
 * Service for handling thesis-related business logic.
 * Acts as an intermediary between ThesisController and ThesisModel.
 */
class ThesisService
{
    private ThesisModel $model;

    /**
     * Creates an instance of ThesisService with a ThesisModel (PDO should be injected here).
     */
    public function __construct(PDO $pdo)
    {
        $this->model = new ThesisModel($pdo);
    }

    /**
     * Fetches all theses with pagination and optional filters.
     * @param array $filters Filters: 'title', 'year', 'page', 'pageSize'.
     * @return array Thesis data and pagination.
     */
    public function getAllTheses(array $filters = []): array
    {
        $page = isset($filters['page']) ? max(1, (int)$filters['page']) : 1;
        $pageSize = isset($filters['pageSize']) ? max(1, min(100, (int)$filters['pageSize'])) : 20;

        // Only pass title and year as filters (no tag/subject logic)
        $items = $this->model->listTheses(
            $pageSize,
            ($page - 1) * $pageSize,
            $filters['title'] ?? null,
            isset($filters['year']) ? (int)$filters['year'] : null
        );
        $total = $this->model->countTheses(
            $filters['title'] ?? null,
            isset($filters['year']) ? (int)$filters['year'] : null
        );

        // Process cover images for each thesis (similar to books)
        foreach ($items as &$item) {
            $coverPath = $this->model->getCoverPathForThesis($item['thesis_id']);
            $item['cover_image'] = $coverPath ?: '/assets/images/nocover.png';
        }

        return [
            'data' => $items,
            'pagination' => [
                'page' => $page,
                'pageSize' => $pageSize,
                'totalItems' => $total,
                'totalPages' => max(1, (int)ceil($total / $pageSize)),
            ],
        ];
    }

    /**
     * Fetches a single thesis by its ID.
     * @param int|string $id Thesis ID.
     * @return array|null
     */
    public function getThesisById($id): ?array
    {
        $thesisId = (int)$id;
        if ($thesisId <= 0) return null;
        $thesis = $this->model->getById($thesisId);
        if ($thesis) {
            // Add cover image processing
            $coverPath = $this->model->getCoverPathForThesis($thesis['thesis_id']);
            $thesis['cover_image'] = $coverPath ?: '/assets/images/nocover.png';
        }
        return $thesis;
    }

    /**
     * Fetches a thesis by accession number.
     * @param string $accessionNo
     * @return array|null
     */
    public function getThesisByAccession(string $accessionNo): ?array
    {
        $accessionNo = trim($accessionNo);
        if ($accessionNo === '') return null;
        $thesis = $this->model->getByAccessionNo($accessionNo);
        if ($thesis) {
            // Add cover image processing
            $coverPath = $this->model->getCoverPathForThesis($thesis['thesis_id']);
            $thesis['cover_image'] = $coverPath ?: '/assets/images/nocover.png';
        }
        return $thesis;
    }

    /**
     * Creates a new thesis record.
     * @param array $data Thesis data.
     * @return int|null
     */
    public function createThesis(array $data): ?int
    {
        $accessionNo = isset($data['accession_no']) ? trim((string)$data['accession_no']) : '';
        $author      = isset($data['author']) ? trim((string)$data['author']) : '';
        $title       = isset($data['title']) ? trim((string)$data['title']) : '';
        $pages       = array_key_exists('pages', $data) ? ($data['pages'] === null ? null : (int)$data['pages']) : null;
        $year        = isset($data['year']) ? (int)$data['year'] : 0;

        if ($accessionNo === '' || $author === '' || $title === '' || $year < 1000 || $year > 9999) {
            return null;
        }

        return $this->model->createThesis($accessionNo, $author, $title, $pages, $year);
    }

    /**
     * Updates an existing thesis.
     * @param int|string $id Thesis ID.
     * @param array $data Thesis data.
     * @return bool|null
     */
    public function updateThesis($id, array $data): ?bool
    {
        $thesisId = (int)$id;
        if ($thesisId <= 0) return null;

        $allowed = ['accession_no', 'author', 'title', 'pages', 'year'];
        $payload = [];
        foreach ($allowed as $k) {
            if (!array_key_exists($k, $data)) continue;
            $payload[$k] = $k === 'pages'
                ? ($data[$k] === null ? null : (int)$data[$k])
                : (is_null($data[$k]) ? null : trim((string)$data[$k]));
        }
        if (!$payload) return false;

        $this->model->updateThesis($thesisId, $payload);
        return true;
    }

    /**
     * Deletes a thesis record.
     * @param int|string $id Thesis ID.
     * @return bool|null
     */
    public function deleteThesis($id): ?bool
    {
        $thesisId = (int)$id;
        if ($thesisId <= 0) return null;
        $this->model->deleteThesis($thesisId);
        return true;
    }

    /**
     * Get total count of all theses in library.
     * @return int
     */
    public function getTotalCount(): int
    {
        return $this->model->getTotalCount();
    }

    /**
     * Lists theses for catalog page. Only search and year filters.
     * @param array $filters 'search', 'year', 'page', 'limit'
     * @return array
     */
    public function getCatalogItems(array $filters = []): array
    {
        $page = isset($filters['page']) ? max(1, (int)$filters['page']) : 1;
        $limit = isset($filters['limit']) ? max(1, min(100, (int)$filters['limit'])) : 12;
        $offset = ($page - 1) * $limit;
        $search = isset($filters['search']) ? trim($filters['search']) : '';
        $year = isset($filters['year']) ? (int)$filters['year'] : null;

        $items = $this->model->listTheses($limit, $offset, $search, $year);
        $total = $this->model->countTheses($search, $year);

        // Process cover images for each thesis (similar to books)
        foreach ($items as &$item) {
            $coverPath = $this->model->getCoverPathForThesis($item['thesis_id']);
            $item['cover_image'] = $coverPath ?: '/assets/images/nocover.png';
        }

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
     * Inserts call number for a thesis.
     * @param int $thesisId
     * @param array $callNumber
     * @return int|null
     */
    public function insertCallNumberForThesis(int $thesisId, array $callNumber): ?int
    {
        $required = ['shelf_number', 'classification_code', 'classification_number', 'cutter', 'year'];
        foreach ($required as $key) {
            if (!isset($callNumber[$key]) || trim((string)$callNumber[$key]) === '') {
                return null;
            }
        }
        return $this->model->insertCallNumberForThesis(
            $thesisId,
            $callNumber['shelf_number'],
            $callNumber['classification_code'],
            $callNumber['classification_number'],
            $callNumber['cutter'],
            $callNumber['year']
        );
    }
}
