<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/thesisModel.php';

/**
 * Service for handling thesis-related business logic.
 *
 * This class provides methods for fetching, creating, updating, and deleting theses,
 * as well as managing their authors. It acts as an intermediary between the
 * ThesisController and the ThesisModel.
 */
class ThesisService
{
    private ThesisModel $model;

    /**
     * Creates an instance of ThesisService and initializes the ThesisModel.
     */
    public function __construct()
    {
        $this->model = new ThesisModel();
    }

    /**
     * Fetches all theses with pagination and optional filters.
     *
     * @param array $filters An associative array of filters, including 'title', 'year', 'page', and 'pageSize'.
     * @return array An array containing the thesis data and pagination information.
     */
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
                'totalPages' => max(1, (int)ceil($total / $pageSize)),
            ],
        ];
    }

    /**
     * Fetches a single thesis by its ID.
     *
     * @param int $id The ID of the thesis to fetch.
     * @return array|null The thesis data, or null if not found.
     */
    public function getThesisById($id): ?array
    {
        $thesisId = (int)$id;
        if ($thesisId <= 0) return null;
        return $this->model->getById($thesisId);
    }

    /**
     * Fetches a single thesis by its accession number.
     *
     * @param string $accessionNo The accession number of the thesis.
     * @return array|null The thesis data, or null if not found.
     */
    public function getThesisByAccession(string $accessionNo): ?array
    {
        $accessionNo = trim($accessionNo);
        if ($accessionNo === '') return null;
        return $this->model->getByAccessionNo($accessionNo);
    }

    /**
     * Creates a new thesis.
     *
     * @param array $data An associative array of thesis data.
     * @return int|null The ID of the new thesis, or null if validation fails.
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
     *
     * @param int $id The ID of the thesis to update.
     * @param array $data An associative array of thesis data.
     * @return bool|null True on success, false on failure, or null if the ID is invalid.
     */
    public function updateThesis($id, array $data): ?bool
    {
        $thesisId = (int)$id;
        if ($thesisId <= 0) return null;

        // Whitelist allowed fields
        $allowed = ['accession_no', 'author', 'title', 'pages'];
        $fieldMapping = [
            'accession_no' => 'accessionno',
            'author' => 'author',
            'title' => 'title',
            'pages' => 'pages',
        ];
        $payload = [];
        foreach ($allowed as $k) {
            if (!array_key_exists($k, $data)) continue;
            $dbField = $fieldMapping[$k];
            $payload[$dbField] = $k === 'pages' ? ($data[$k] === null ? null : (int)$data[$k])
                : (is_null($data[$k]) ? null : trim((string)$data[$k]));
        }

        if (!$payload) return false;

        $this->model->updateThesis($thesisId, $payload);
        return true;
    }



    /**
     * Deletes a thesis.
     *
     * @param int $id The ID of the thesis to delete.
     * @return bool|null True on success, or null if the ID is invalid.
     */
    public function deleteThesis($id): ?bool
    {
        $thesisId = (int)$id;
        if ($thesisId <= 0) return null;
        $this->model->deleteThesis($thesisId);
        return true;
    }

    /**
     * Gets the authors for a given thesis.
     *
     * @param int $id The ID of the thesis.
     * @return array|null An array of author data, or null if the ID is invalid.
     */
    // NOTE: This function is commented out because thesis authors are stored directly in tbl_theses.author field
    // public function getThesisAuthors($id): ?array
    // {
    //     $thesisId = (int)$id;
    //     if ($thesisId <= 0) return null;
    //     return $this->model->listAuthorsForThesis($thesisId);
    // }

    /**
     * Adds an author to a thesis.
     *
     * @param int $id The ID of the thesis.
     * @param int $authorId The ID of the author to add.
     * @param int $authorOrder The order of the author.
     * @param string $role The role of the author.
     * @return bool|null True on success, or null if IDs are invalid.
     */
    // NOTE: This function is commented out because thesis authors are stored directly in tbl_theses.author field
    // public function addThesisAuthor($id, int $authorId, int $authorOrder = 1, string $role = 'Author'): ?bool
    // {
    //     $thesisId = (int)$id;
    //     if ($thesisId <= 0 || $authorId <= 0) return null;
    //     $this->model->addAuthorLink($thesisId, $authorId, $authorOrder, $role);
    //     return true;
    // }

    /**
     * Updates the order of an author for a thesis.
     *
     * @param int $id The ID of the thesis.
     * @param int $authorId The ID of the author.
     * @param int $authorOrder The new order of the author.
     * @return bool|null True on success, or null if IDs are invalid.
     */
    // NOTE: This function is commented out because thesis authors are stored directly in tbl_theses.author field
    // public function updateThesisAuthorOrder($id, int $authorId, int $authorOrder): ?bool
    // {
    //     $thesisId = (int)$id;
    //     if ($thesisId <= 0 || $authorId <= 0) return null;
    //     $this->model->updateAuthorOrder($thesisId, $authorId, $authorOrder);
    //     return true;
    // }

    /**
     * Removes an author from a thesis.
     *
     * @param int $id The ID of the thesis.
     * @param int $authorId The ID of the author to remove.
     * @return bool|null True on success, or null if IDs are invalid.
     */
    // NOTE: This function is commented out because thesis authors are stored directly in tbl_theses.author field
    // public function removeThesisAuthor($id, int $authorId): ?bool
    // {
    //     $thesisId = (int)$id;
    //     if ($thesisId <= 0 || $authorId <= 0) return null;
    //     $this->model->removeAuthorLink($thesisId, $authorId);
    //     return true;
    // }

    /**
     * Inserts a normalized call number for a thesis.
     *
     * @param int $thesisId The ID of the thesis.
     * @param array $callNumber An array with all required call number parts.
     *   - shelf_number, classification_code, classification_number, cutter, year
     * @return int|null The ID of the new call number, or null if validation fails.
     */
    public function insertCallNumberForThesis(int $thesisId, array $callNumber): ?int
    {
        // Basic validation
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
