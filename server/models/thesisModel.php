<?php

declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';

/**
 * Model for handling thesis-related database operations.
 */
class ThesisModel
{
    private PDO $pdo;
    private $availableThesisCovers = [
        'TH 207 B37 2017',
        'TH 208 A46 2017',
        'TH 211 C36 2017',
        'TH 212 D45 2017',
        'TH 213 F56 2017',
        'TH 214 C33 2017',
        'TH 215 S25 2017',
        'TH 216 A26 2017',
        'TH 217 A25 2017',
        'TH 219 S46 2017',
        'TH 220 B84 2017',
        'TH 221 B86 2017',
        'TH 223 D45 2017',
        'TH 224 B38 2017',
        'TH 225 B46 2017',
        'TH 226 A43 2017',
        'TH 227 A83 2017',
        'TH 228 G66 2017',
        'TH 229 A23 2017',
        'TH 230 M67 2017',
        'TH 231 B66 2017',
        'TH 232 D59 2017',
        'TH 233 B63 2017',
        'TH 234 G38 2017',
        'TH 235 S26 2017',
        'TH 236 C37 2017',
        'TH 237 M47 2017',
        'TH 238 P53 2017',
        'TH 239 B35 2017',
        'TH 240 C33 2017',
        'TH 241 B55 2017',
        'TH 242 D44 2017',
        'TH 243 C35 2017',
        'TH 244 B37 2017',
        'TH 245 G36 2017',
        'TH 246 D45 2017',
        'TH 247 A38 2017',
        'TH 249 D56 2017',
        'TH 250 E87 2017',
        'TH 251 O43 2017',
        'TH 252 C67 2017',
        'TH 253 C37 2017',
        'TH 254 B84 2017',
        'TH 255 A53 2017',
        'TH 256 A38 2017',
        'TH 257 E54 2017',
        'TH 258 B37 2017',
        'TH 259 A74 2017',
        'TH 260 A53 2017',
        'TH 261 E76 2017',
        'TH 262 B65 2017',
        'TH 263 A46 2017',
        'TH 264 G85 2017',
        'TH 265 S44 2017',
        'TH 266 C33 2017',
        'TH 267 D36 2017',
        'TH 268 A38 2017',
        'TH 269 F84 2017',
        'TH 270 A26 2017',
        'TH 271 E93 2017',
        'TH 272 A28 2017',
        'TH 273 A53 2017',
        'TH 274 A43 2017',
        'TH 275 B35 2017',
        'TH 276 B87 2017',
        'TH 345 C33 2016',
        'TH 346 B84 2016',
        'TH 347 C57 2016',
        'TH 348 O43 2016',
        'TH 349 C37 2016',
        'TH 350 B66 2016',
        'TH 351 B38 2016',
        'TH 352 B34 2016',
        'TH 354 C37 2016',
        'TH 355 B87 2016',
        'TH 356 A48 2016',
        'TH 357 M34 2016',
        'TH 359 C65 2019',
        'TH 360 A46 2019',
        'TH 361 M33 2019',
        'TH 362 G35 2019',
        'TH 363 D45 2019',
        'TH 364 D66 2019',
        'TH 365 B36 2019',
        'TH 366 B36 2019',
        'TH 367 B37 2019',
        'TH 368 B58 2019',
        'TH 369 D56 2019',
        'TH 370 G73 2019',
        'TH 371 B66 2019',
        'TH 372 C84 2019',
        'TH 373 A97 2019',
        'TH 374 B57 2019',
        'TH 375 B35 2019',
        'TH 376 C33 2019',
        'TH 377 F37 2019',
        'TH 378 B75 2019',
        'TH 379 A43 2019',
    ];

    private function smartTitleCase(string $title): string
    {
        $smallWords = ['a', 'an', 'the', 'and', 'but', 'or', 'for', 'nor', 'on', 'at', 'to', 'from', 'by', 'of', 'in'];
        $words = explode(' ', strtolower($title));
        $wordCount = count($words);
        foreach ($words as $i => $word) {
            // Always capitalize first and last words
            if ($i === 0 || $i === $wordCount - 1 || !in_array($word, $smallWords)) {
                // Hyphenated: Split and capitalize subwords
                $words[$i] = implode('-', array_map('ucfirst', explode('-', $word)));
            }
        }
        return implode(' ', $words);
    }

    private function normalizeTitle(string $title): string
    {
        if (class_exists('Normalizer')) {
            $title = Normalizer::normalize($title, Normalizer::FORM_C);
        }
        $title = trim($title);
        $title = preg_replace('/\s+/', ' ', $title);
        $title = preg_replace('/[[:cntrl:]]/', '', $title);
        $title = preg_replace('/(\.|,|\?|!){2,}/', '$1', $title);
        $title = str_replace(["“", "”", "‘", "’", "–", "—"], ['"', '"', "'", "'", "-", "-"], $title);
        $title = str_replace("\xC2\xA0", ' ', $title);
        $title = preg_replace('/\.{3,}/', '...', $title);

        // Preserve acronyms
        $title = preg_replace_callback('/\b([A-Z]{2,})\b/', fn($m) => strtoupper($m[1]), $title);

        // Apply smart title case
        $title = $this->smartTitleCase($title);

        return $title;
    }



    public function __construct()
    {
        $this->pdo = getPDO();
    }

    /** Fetches a single thesis by its ID. */
    public function getById(int $thesisId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT thesis_id, accession_no, author, title, year, created_at, updated_at
            FROM `tbl_theses`
            WHERE thesis_id = ?
        ");
        $stmt->execute([$thesisId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Fetches a single thesis by its accession number. */
    public function getByAccessionNo(string $accessionNo): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT thesis_id, accession_no, author, title, year, created_at, updated_at
            FROM `tbl_theses`
            WHERE accession_no = ?
        ");
        $stmt->execute([$accessionNo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Creates a new thesis record. */
    public function createThesis(
        string $accessionNo,
        string $author,
        string $title,
        ?int $pages,
        int $year
    ): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO `tbl_theses`
                (accession_no, author, title, pages, year, created_at, updated_at)
            VALUES
                (?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$accessionNo, $author, $title, $pages, $year]);
        return (int)$this->pdo->lastInsertId();
    }

    /** Updates an existing thesis record. */
    public function updateThesis(int $thesisId, array $fields): void
    {
        // Allowed columns only
        $allowed = ['accession_no', 'author', 'title', 'pages', 'year'];
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

    /** Deletes a thesis record. */
    public function deleteThesis(int $thesisId): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM `tbl_theses` WHERE thesis_id = ?");
        $stmt->execute([$thesisId]);
    }

    public function listTheses(int $limit, int $offset, ?string $titleSearch = null, ?int $year = null): array
    {
        $where = [];
        $params = [];

        if ($titleSearch !== null && $titleSearch !== '') {
            $where[] = "t.title LIKE ?";
            $params[] = $titleSearch . '%';
        }
        if ($year !== null) {
            $where[] = "t.year = ?";
            $params[] = $year;
        }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $coverList = !empty($this->availableThesisCovers)
            ? "'" . implode("','", $this->availableThesisCovers) . "'"
            : "''";

        $sql = "
        SELECT
            t.thesis_id, t.accession_no, t.author, t.title, t.year,
            COALESCE(CONCAT_WS(' ',
                CASE
                    WHEN cn.shelf_number = 'THESIS' THEN 'TH'
                    WHEN cn.shelf_number = 'TH' THEN 'TH'
                    ELSE COALESCE(NULLIF(cn.shelf_number, ''), NULL)
                END,
                CASE
                    WHEN cn.classification_code = 'TH' THEN NULL
                    ELSE COALESCE(NULLIF(cn.classification_code, ''), NULL)
                END,
                COALESCE(NULLIF(cn.classification_number, ''), NULL),
                COALESCE(NULLIF(cn.cutter, ''), NULL),
                COALESCE(NULLIF(cn.year, ''), NULL)
            ), '') AS call_no,
            CASE
                WHEN COALESCE(CONCAT_WS(' ',
                    CASE
                        WHEN cn.shelf_number = 'THESIS' THEN 'TH'
                        WHEN cn.shelf_number = 'TH' THEN 'TH'
                        ELSE COALESCE(NULLIF(cn.shelf_number, ''), NULL)
                    END,
                    CASE
                        WHEN cn.classification_code = 'TH' THEN NULL
                        ELSE COALESCE(NULLIF(cn.classification_code, ''), NULL)
                    END,
                    COALESCE(NULLIF(cn.classification_number, ''), NULL),
                    COALESCE(NULLIF(cn.cutter, ''), NULL),
                    COALESCE(NULLIF(cn.year, ''), NULL)
                ), '') IN ($coverList)
                THEN 1 ELSE 0
            END AS has_cover
        FROM tbl_theses t
        LEFT JOIN tbl_call_number cn
            ON cn.reference_type = 'thesis' AND cn.reference_id = t.thesis_id
        $whereSql
        ORDER BY has_cover DESC, t.title ASC
        LIMIT ? OFFSET ?
    ";

        try {
            $stmt = $this->pdo->prepare($sql);
            $i = 1;
            foreach ($params as $p) {
                $stmt->bindValue($i++, $p);
            }
            $stmt->bindValue($i++, $limit, PDO::PARAM_INT);
            $stmt->bindValue($i, $offset, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // Normalize each thesis title
            foreach ($rows as &$row) {
                if (isset($row['title'])) {
                    $row['title'] = $this->normalizeTitle($row['title']);
                }
            }

            return $rows;
        } catch (Throwable $e) {
            error_log("Thesis API ERROR: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
            return [];
        }
    }


    /** Counts the total number of theses matching optional filters. */
    public function countTheses(?string $titleSearch = null, ?int $year = null): int
    {
        $where = [];
        $params = [];
        if ($titleSearch !== null && $titleSearch !== '') {
            $where[] = "title LIKE ?";
            $params[] = $titleSearch . '%';
        }
        if ($year !== null) {
            $where[] = "year = ?";
            $params[] = $year;
        }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $stmt = $this->pdo->prepare("SELECT COUNT(*) AS total FROM `tbl_theses` $whereSql");
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['total'] : 0;
    }

    /** Lists all authors for a given thesis. */
    // NOTE: This function is commented out because thesis authors are stored directly in tbl_theses.author field
    // public function listAuthorsForThesis(int $thesisId): array
    // {
    //     $stmt = $this->pdo->prepare("
    //         SELECT a.author_id, a.firstname, a.middlename, a.lastname, ta.author_order, ta.role
    //         FROM `tbl_thesis_authors` ta
    //         JOIN `tblauthors` a ON a.author_id = ta.author_id
    //         WHERE ta.thesis_id = ?
    //         ORDER BY ta.author_order
    //     ");
    //     $stmt->execute([$thesisId]);
    //     return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    // }

    /** Adds a link between a thesis and an author. */
    // NOTE: This function is commented out because thesis authors are stored directly in tbl_theses.author field
    // public function addAuthorLink(int $thesisId, int $authorId, int $authorOrder = 1, string $role = 'Author'): void
    // {
    //     $stmt = $this->pdo->prepare("
    //         SELECT 1 FROM `tbl_thesis_authors` WHERE thesis_id = ? AND author_id = ?
    //     ");
    //     $stmt->execute([$thesisId, $authorId]);
    //     if ($stmt->fetch()) return;

    //     $stmt = $this->pdo->prepare("
    //         INSERT INTO `tbl_thesis_authors` (thesis_id, author_id, author_order, role, created_at)
    //         VALUES (?, ?, ?, ?, NOW())
    //     ");
    //     $stmt->execute([$thesisId, $authorId, $authorOrder, $role]);
    // }

    /** Updates the order of an author for a given thesis. */
    // NOTE: This function is commented out because thesis authors are stored directly in tbl_theses.author field
    // public function updateAuthorOrder(int $thesisId, int $authorId, int $authorOrder): void
    // {
    //     $stmt = $this->pdo->prepare("
    //         UPDATE `tbl_thesis_authors`
    //         SET author_order = ?
    //         WHERE thesis_id = ? AND author_id = ?
    //     ");
    //     $stmt->execute([$authorOrder, $thesisId, $authorId]);
    // }

    /** Removes a link between a thesis and an author. */
    // NOTE: This function is commented out because thesis authors are stored directly in tbl_theses.author field
    // public function removeAuthorLink(int $thesisId, int $authorId): void
    // {
    //     $stmt = $this->pdo->prepare("
    //         DELETE FROM `tbl_thesis_authors`
    //         WHERE thesis_id = ? AND author_id = ?
    //     ");
    //     $stmt->execute([$thesisId, $authorId]);
    // }

    public function getFullCallNumberForThesis(int $thesisId): ?string
    {
        $stmt = $this->pdo->prepare("
        SELECT CONCAT_WS(' ',
            CASE
                WHEN shelf_number = 'THESIS' THEN 'TH'
                WHEN shelf_number = 'TH' THEN 'TH'
                ELSE COALESCE(NULLIF(shelf_number, ''), NULL)
            END,
            CASE
                WHEN classification_code = 'TH' THEN NULL
                ELSE COALESCE(NULLIF(classification_code, ''), NULL)
            END,
            COALESCE(NULLIF(classification_number, ''), NULL),
            COALESCE(NULLIF(cutter, ''), NULL),
            COALESCE(NULLIF(year, ''), NULL)) AS call_number
        FROM tbl_call_number
        WHERE reference_type = 'thesis' AND reference_id = ?
        LIMIT 1
    ");
        $stmt->execute([$thesisId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['call_number'] : null;
    }

    public function insertCallNumberForThesis(
        int $thesisId,
        string $shelf_number,
        string $classification_code,
        string $classification_number,
        string $cutter,
        string $year
    ): int {
        // Insert the call number for the thesis
        $stmt = $this->pdo->prepare("
        INSERT INTO tbl_call_number (
            shelf_number, classification_code, classification_number, cutter, year, reference_type, reference_id, created_at, updated_at
        ) VALUES (
            ?, ?, ?, ?, ?, 'thesis', ?, NOW(), NOW()
        )
    ");
        $stmt->execute([
            $shelf_number,
            $classification_code,
            $classification_number,
            $cutter,
            $year,
            $thesisId
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /** Gets the cover image path for a thesis based on its call number. */
    public function getCoverPathForThesis(int $thesisId): ?string
    {
        $callNumber = $this->getFullCallNumberForThesis($thesisId);
        if (!$callNumber || !in_array($callNumber, $this->availableThesisCovers, true)) {
            return null;
        }
        return "/STI-DigiLibrary/theses_covers/{$callNumber}.webp";
    }
}
