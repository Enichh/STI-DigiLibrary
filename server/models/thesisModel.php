<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

/**
 * Model for handling thesis-related database operations.
 *
 * This class provides methods for fetching, creating, updating, and deleting thesis records,
 * as well as managing author relationships.
 */
class ThesisModel
{
    private PDO $pdo;

    /**
     * Creates an instance of ThesisModel and gets a PDO connection.
     */
    public function __construct()
    {
        $this->pdo = getPDO();
    }

    /**
     * Fetches a single thesis by its ID.
     *
     * @param int $thesisId The ID of the thesis to fetch.
     * @return array|null The thesis record, or null if not found.
     */
    public function getById(int $thesisId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT thesis_id, accession_no, call_no, title, pages, pages_note, pub_year, created_at, updated_at
            FROM `tbl_theses`
            WHERE thesis_id = ?
        ");
        $stmt->execute([$thesisId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Fetches a single thesis by its accession number.
     *
     * @param string $accessionNo The accession number of the thesis.
     * @return array|null The thesis record, or null if not found.
     */
    public function getByAccessionNo(string $accessionNo): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT thesis_id, accession_no, call_no, title, pages, pages_note, pub_year, created_at, updated_at
            FROM `tbl_theses`
            WHERE accession_no = ?
        ");
        $stmt->execute([$accessionNo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Creates a new thesis record.
     *
     * @param string $accessionNo The accession number.
     * @param string $callNo The call number.
     * @param string $title The title of the thesis.
     * @param int|null $pages The number of pages.
     * @param string|null $pagesNote A note about the pages.
     * @param int $pubYear The publication year.
     * @return int The ID of the newly created thesis.
     */
    public function createThesis(string $accessionNo, string $callNo, string $title, ?int $pages, ?string $pagesNote, int $pubYear): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO `tbl_theses`
                (accession_no, call_no, title, pages, pages_note, pub_year, created_at, updated_at)
            VALUES
                (?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$accessionNo, $callNo, $title, $pages, $pagesNote, $pubYear]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Updates an existing thesis record.
     *
     * @param int $thesisId The ID of the thesis to update.
     * @param array $fields An associative array of fields to update.
     * @return void
     */
    public function updateThesis(int $thesisId, array $fields): void
    {
        // Allowed columns only
        $allowed = ['accession_no', 'call_no', 'title', 'pages', 'pages_note', 'pub_year'];
        $set = [];
        $params = [];
        foreach ($fields as $col => $val) {
            if (!in_array($col, $allowed, true)) continue;
            $set[] = "`$col` = ?";
            $params[] = $val;
        }
        if (!$set) return;
        $params[] = $thesisId;

        $sql = "UPDATE `tbl_theses` SET " . implode(', ', $set) . ", updated_at = NOW() WHERE thesis_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * Deletes a thesis record.
     *
     * @param int $thesisId The ID of the thesis to delete.
     * @return void
     */
    public function deleteThesis(int $thesisId): void
    {
        // tbl_thesis_authors has FK to tbl_theses with ON DELETE CASCADE (as defined when you created it)
        $stmt = $this->pdo->prepare("DELETE FROM `tbl_theses` WHERE thesis_id = ?");
        $stmt->execute([$thesisId]);
    }

    /**
     * Lists all theses with pagination and optional filters.
     *
     * @param int $limit The maximum number of records to return.
     * @param int $offset The starting offset for pagination.
     * @param string|null $titleSearch An optional title to search for.
     * @param int|null $year An optional publication year to filter by.
     * @return array An array of thesis records.
     */
    public function listTheses(int $limit, int $offset, ?string $titleSearch = null, ?int $year = null): array
    {
        $where = [];
        $params = [];
        if ($titleSearch !== null && $titleSearch !== '') {
            $where[] = "t.title LIKE ?";
            $params[] = $titleSearch . '%'; // uses BTREE on title efficiently for prefix searches
        }
        if ($year !== null && $year !== '') {
            $where[] = "t.pub_year = ?";
            $params[] = $year;
        }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $sql = "
            SELECT
                t.thesis_id, t.accession_no, t.call_no, t.title, t.pages, t.pages_note, t.pub_year,
                a.first_name, a.middle_name, a.last_name, ta.author_order
            FROM `tbl_theses` t
            LEFT JOIN `tbl_thesis_authors` ta
              ON ta.thesis_id = t.thesis_id AND ta.author_order = 1
            LEFT JOIN `tbl_authors` a
              ON a.author_id = ta.author_id
            $whereSql
            ORDER BY t.thesis_id
            LIMIT ? OFFSET ?
        ";
        $stmt = $this->pdo->prepare($sql);
        $i = 1;
        foreach ($params as $p) {
            $stmt->bindValue($i++, $p);
        }
        $stmt->bindValue($i++, $limit, PDO::PARAM_INT);
        $stmt->bindValue($i, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Counts the total number of theses matching optional filters.
     *
     * @param string|null $titleSearch An optional title to search for.
     * @param int|null $year An optional publication year to filter by.
     * @return int The total number of matching theses.
     */
    public function countTheses(?string $titleSearch = null, ?int $year = null): int
    {
        $where = [];
        $params = [];
        if ($titleSearch !== null && $titleSearch !== '') {
            $where[] = "title LIKE ?";
            $params[] = $titleSearch . '%';
        }
        if ($year !== null && $year !== '') {
            $where[] = "pub_year = ?";
            $params[] = $year;
        }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $stmt = $this->pdo->prepare("SELECT COUNT(*) AS total FROM `tbl_theses` $whereSql");
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['total'] : 0;
    }

    /**
     * Lists all authors for a given thesis.
     *
     * @param int $thesisId The ID of the thesis.
     * @return array An array of author records.
     */
    public function listAuthorsForThesis(int $thesisId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT a.author_id, a.first_name, a.middle_name, a.last_name, ta.author_order, ta.role
            FROM `tbl_thesis_authors` ta
            JOIN `tbl_authors` a ON a.author_id = ta.author_id
            WHERE ta.thesis_id = ?
            ORDER BY ta.author_order
        ");
        $stmt->execute([$thesisId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Adds a link between a thesis and an author.
     *
     * @param int $thesisId The ID of the thesis.
     * @param int $authorId The ID of the author.
     * @param int $authorOrder The order of the author.
     * @param string $role The role of the author.
     * @return void
     */
    public function addAuthorLink(int $thesisId, int $authorId, int $authorOrder = 1, string $role = 'Author'): void
    {
        // Prevent duplicates
        $stmt = $this->pdo->prepare("
            SELECT 1 FROM `tbl_thesis_authors` WHERE thesis_id = ? AND author_id = ?
        ");
        $stmt->execute([$thesisId, $authorId]);
        if ($stmt->fetch()) return;

        $stmt = $this->pdo->prepare("
            INSERT INTO `tbl_thesis_authors` (thesis_id, author_id, author_order, role, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$thesisId, $authorId, $authorOrder, $role]);
    }

    /**
     * Updates the order of an author for a given thesis.
     *
     * @param int $thesisId The ID of the thesis.
     * @param int $authorId The ID of the author.
     * @param int $authorOrder The new order of the author.
     * @return void
     */
    public function updateAuthorOrder(int $thesisId, int $authorId, int $authorOrder): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE `tbl_thesis_authors`
            SET author_order = ?
            WHERE thesis_id = ? AND author_id = ?
        ");
        $stmt->execute([$authorOrder, $thesisId, $authorId]);
    }

    /**
     * Removes a link between a thesis and an author.
     *
     * @param int $thesisId The ID of the thesis.
     * @param int $authorId The ID of the author.
     * @return void
     */
    public function removeAuthorLink(int $thesisId, int $authorId): void
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM `tbl_thesis_authors`
            WHERE thesis_id = ? AND author_id = ?
        ");
        $stmt->execute([$thesisId, $authorId]);
    }
}
