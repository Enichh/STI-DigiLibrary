<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/thesisModel.php';

class ThesisService
{
    private ThesisModel $model;

    public function __construct()
    {
        $this->model = new ThesisModel();
    }

    // Fetch all theses with pagination and optional filters
    // filters may include:
    // - title: prefix search on title
    // - year: exact pub_year
    // - page, pageSize: pagination controls
    public function getAllTheses(array $filters = []): array
    {
        $page = isset($filters['page']) ? max(1, (int)$filters['page']) : 1;
        $pageSize = isset($filters['pageSize']) ? max(1, min(100, (int)$filters['pageSize'])) : 20;

        unset($filters['page'], $filters['pageSize']);

        $items = $this->model->listTheses($pageSize, ($page - 1) * $pageSize, $filters['title'] ?? null, isset($filters['year']) ? (int)$filters['year'] : null);
        $total = $this->model->countTheses($filters['title'] ?? null, isset($filters['year']) ? (int)$filters['year'] : null);

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

    // Fetch a single thesis by ID
    public function getThesisById($id): ?array
    {
        $thesisId = (int)$id;
        if ($thesisId <= 0) return null;
        return $this->model->getById($thesisId);
    }

    // Fetch a single thesis by accession number
    public function getThesisByAccession(string $accessionNo): ?array
    {
        $accessionNo = trim($accessionNo);
        if ($accessionNo === '') return null;
        return $this->model->getByAccessionNo($accessionNo);
    }

    // Create a new thesis
    // $data keys: accession_no, call_no, title, pages (nullable), pages_note (nullable), pub_year
    public function createThesis(array $data): ?int
    {
        $accessionNo = isset($data['accession_no']) ? trim((string)$data['accession_no']) : '';
        $callNo      = isset($data['call_no']) ? trim((string)$data['call_no']) : '';
        $title       = isset($data['title']) ? trim((string)$data['title']) : '';
        $pages       = array_key_exists('pages', $data) ? ($data['pages'] === null ? null : (int)$data['pages']) : null;
        $pagesNote   = array_key_exists('pages_note', $data) ? (is_null($data['pages_note']) ? null : trim((string)$data['pages_note'])) : null;
        $pubYear     = isset($data['pub_year']) ? (int)$data['pub_year'] : 0;

        if ($accessionNo === '' || $callNo === '' || $title === '' || $pubYear < 1000 || $pubYear > 9999) {
            return null;
        }

        return $this->model->createThesis($accessionNo, $callNo, $title, $pages, $pagesNote, $pubYear);
    }

    // Update an existing thesis
    public function updateThesis($id, array $data): ?bool
    {
        $thesisId = (int)$id;
        if ($thesisId <= 0) return null;

        // Whitelist allowed fields
        $allowed = ['accession_no', 'call_no', 'title', 'pages', 'pages_note', 'pub_year'];
        $payload = [];
        foreach ($allowed as $k) {
            if (!array_key_exists($k, $data)) continue;
            $payload[$k] = $k === 'pages' ? ($data[$k] === null ? null : (int)$data[$k])
                : ($k === 'pub_year' ? (int)$data[$k] : (is_null($data[$k]) ? null : trim((string)$data[$k])));
        }

        if (!$payload) return false;

        $this->model->updateThesis($thesisId, $payload);
        return true;
    }

    // Delete a thesis (author links cascade via FK)
    public function deleteThesis($id): ?bool
    {
        $thesisId = (int)$id;
        if ($thesisId <= 0) return null;
        $this->model->deleteThesis($thesisId);
        return true;
    }

    // Authors for a thesis
    public function getThesisAuthors($id): ?array
    {
        $thesisId = (int)$id;
        if ($thesisId <= 0) return null;
        return $this->model->listAuthorsForThesis($thesisId);
    }

    public function addThesisAuthor($id, int $authorId, int $authorOrder = 1, string $role = 'Author'): ?bool
    {
        $thesisId = (int)$id;
        if ($thesisId <= 0 || $authorId <= 0) return null;
        $this->model->addAuthorLink($thesisId, $authorId, $authorOrder, $role);
        return true;
    }

    public function updateThesisAuthorOrder($id, int $authorId, int $authorOrder): ?bool
    {
        $thesisId = (int)$id;
        if ($thesisId <= 0 || $authorId <= 0) return null;
        $this->model->updateAuthorOrder($thesisId, $authorId, $authorOrder);
        return true;
    }

    public function removeThesisAuthor($id, int $authorId): ?bool
    {
        $thesisId = (int)$id;
        if ($thesisId <= 0 || $authorId <= 0) return null;
        $this->model->removeAuthorLink($thesisId, $authorId);
        return true;
    }
}
