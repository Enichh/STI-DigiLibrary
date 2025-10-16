<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class ThesisModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getPDO();
    }

    // Work-level lookups
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

    // Creation and updates
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

    public function deleteThesis(int $thesisId): void
    {
        // tbl_thesis_authors has FK to tbl_theses with ON DELETE CASCADE (as defined when you created it)
        $stmt = $this->pdo->prepare("DELETE FROM `tbl_theses` WHERE thesis_id = ?");
        $stmt->execute([$thesisId]);
    }

    // Listing and counts
    public function listTheses(int $limit, int $offset, ?string $titleSearch = null, ?int $year = null): array
    {
        $where = [];
        $params = [];
        if ($titleSearch !== null && $titleSearch !== '') {
            $where[] = "t.title LIKE ?";
            $params[] = $titleSearch . '%'; // uses BTREE on title efficiently for prefix searches
        }
        if ($year !== null) {
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

    public function countTheses(?string $titleSearch = null, ?int $year = null): int
    {
        $where = [];
        $params = [];
        if ($titleSearch !== null && $titleSearch !== '') {
            $where[] = "title LIKE ?";
            $params[] = $titleSearch . '%';
        }
        if ($year !== null) {
            $where[] = "pub_year = ?";
            $params[] = $year;
        }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $stmt = $this->pdo->prepare("SELECT COUNT(*) AS total FROM `tbl_theses` $whereSql");
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['total'] : 0;
    }

    // Author links
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

    public function updateAuthorOrder(int $thesisId, int $authorId, int $authorOrder): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE `tbl_thesis_authors`
            SET author_order = ?
            WHERE thesis_id = ? AND author_id = ?
        ");
        $stmt->execute([$authorOrder, $thesisId, $authorId]);
    }

    public function removeAuthorLink(int $thesisId, int $authorId): void
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM `tbl_thesis_authors`
            WHERE thesis_id = ? AND author_id = ?
        ");
        $stmt->execute([$thesisId, $authorId]);
    }
}
