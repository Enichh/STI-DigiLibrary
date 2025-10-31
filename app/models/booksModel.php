<?php
// app/models/booksModel.php


require_once __DIR__ . '/../../config/database.php';

/**
 * Model for handling book-related database operations.
 * Provides methods for fetching, creating, updating, and deleting book records.
 */
class BooksModel
{
    private $pdo;
    private $availableCovers = [
        '9780070181397',
        '9780070663183',
        '9780071314213',
        '9780071398770',
        '9780071752947',
        '9780071775182',
        '9780071790390',
        '9780071797962',
        '9780072126747',
        '9780072133424',
        '9780072228878',
        '9780072816990',
        '9780072834963',
        '9780072851236',
        '9780073523408',
        '9780077331269',
        '9780078210587',
        '9780078253041',
        '9780080489797',
        '9780080495910',
        '9780080890364',
        '9780123540515',
        '9780123694461',
        '9780123849687',
        '9780128182031',
        '9780130091918',
        '9780130282200',
        '9780130909244',
        '9780131378865',
        '9780131395312',
        '9780132171939',
        '9780132397759',
        '9780132404167',
        '9780132469197',
        '9780132783392',
        '9780132983945',
        '9780132983976',
        '9780133016277',
        '9780133038521',
        '9780133057577',
        '9780133061567',
        '9780133118070',
        '9780133479102',
        '9780133591743',
        '9780133793888',
        '9780134658339',
        '9780134764108',
        '9780134778259',
        '9780136587743',
        '9780136886020',
        '9780137084029',
        '9780137142521',
        '9780138993948',
        '9780201354614',
        '9780201383720',
        '9780201403572',
        '9780201733556',
        '9780201756081',
        '9780262132336',
        '9780273759768',
        '9780321174079',
        '9780321678799',
        '9780321718389',
        '9780321719904',
        '9780321772824',
        '9780324259285',
        '9780357674611',
        '9780470149492',
        '9780470242018',
        '9780470260791',
        '9780470479223',
        '9780470493731',
        '9780470570005',
        '9780470977385',
        '9780534366919',
        '9780534950606',
        '9780538470025',
        '9780538474436',
        '9780538747455',
        '9780619016623',
        '9780619017651',
        '9780619034429',
        '9780619064976',
        '9780619121273',
        '9780619202224',
        '9780619217242',
        '9780619243654',
        '9780619254971',
        '9780672315497',
        '9780672330414',
        '9780735605176',
        '9780745662527',
        '9780760011485',
        '9780760011775',
        '9780763737696',
        '9780763790615',
        '9780764577185',
        '9780768692471',
        '9780789560995',
        '9780789561176',
        '9780789700766',
        '9780789742278',
        '9780805303346',
        '9780840734808',
        '9781111222109',
        '9781111306366',
        '9781111529413',
        '9781111532598',
        '9781111825560',
        '9781118026236',
        '9781118052013',
        '9781118134610',
        '9781118204139',
        '9781118234884',
        '9781118235843',
        '9781118237083',
        '9781118239421',
        '9781118240281',
        '9781118356555',
        '9781119301127',
        '9781119495208',
        '9781119940531',
        '9781119963998',
        '9781133526087',
        '9781133593546',
        '9781133788881',
        '9781139500005',
        '9781259080791',
        '9781259563652',
        '9781260548006',
        '9781266816871',
        '9781284074901',
        '9781285082882',
        '9781285867403',
        '9781292052120',
        '9781351621984',
        '9781408048016',
        '9781418835620',
        '9781418859374',
        '9781420087857',
        '9781423902492',
        '9781423902553',
        '9781423902911',
        '9781423903000',
        '9781423925446',
        '9781423927167',
        '9781435453906',
        '9781435454231',
        '9781439079201',
        '9781439080146',
        '9781439081310',
        '9781449374297',
        '9781466511019',
        '9781466588745',
        '9781486002580',
        '9781542453318',
        '9781556223730',
        '9781572314405',
        '9781584505570',
        '9781584505808',
        '9781587131103',
        '9781587131127',
        '9781587133480',
        '9781597495622',
        '9781598633429',
        '9781781579954',
        '9781838828134',
        '9781840785388',
        '9781844803552',
        '9781844808915',
        '9783827330437',
        '9787508309897',
        '9788122416381',
        '9788126508853',
        '9788126509621',
        '9788126532377',
        '9788131501153',
        '9788131502181',
        '9788131701140',
        '9788131711880',
        '9788131716052',
        '9788131725283',
        '9788131754955',
        '9788177586886',
        '9789688802052'
    ];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }



    /**
     * Sanitizes the provided genre string by decoding HTML entities and trimming whitespace.
     * @param string|null $genre
     * @return string|null
     */
    public static function sanitizeGenre(?string $genre): ?string
    {
        if ($genre === null) {
            return null;
        }
        // Decode all HTML entities (handles &amp;, &amp;amp;, etc.)
        $sanitized = html_entity_decode($genre, ENT_QUOTES | ENT_HTML5);
        // Decode repeatedly if necessary (for nested encodings)
        while ($sanitized !== html_entity_decode($sanitized, ENT_QUOTES | ENT_HTML5)) {
            $sanitized = html_entity_decode($sanitized, ENT_QUOTES | ENT_HTML5);
        }
        return trim($sanitized);
    }

    private function smartTitleCase(string $title): string
    {
        $smallWords = ['a', 'an', 'the', 'and', 'but', 'or', 'for', 'nor', 'on', 'at', 'to', 'from', 'by', 'of', 'in'];
        $words = explode(' ', strtolower($title));
        $wordCount = count($words);
        foreach ($words as $i => $word) {
            if ($i === 0 || $i === $wordCount - 1 || !in_array($word, $smallWords)) {
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
        $title = preg_replace_callback('/\b([A-Z]{2,})\b/', fn($m) => strtoupper($m[1]), $title);
        $title = $this->smartTitleCase($title);
        return $title;
    }

    private function authorExpr(string $alias = 'a'): string
    {
        return "
        TRIM(
            CONCAT(
                IF($alias.first_name IS NULL OR $alias.first_name = '' OR $alias.first_name = 'No First Name', '', CONCAT($alias.first_name, ' ')),
                IF($alias.middle_name IS NULL OR $alias.middle_name = '' OR $alias.middle_name = 'No Middle Name', '', CONCAT($alias.middle_name, ' ')),
                IF($alias.last_name IS NULL OR $alias.last_name = '' OR $alias.last_name = 'No Last Name', '', $alias.last_name)
            )
        )
    ";
    }


    private function normalizeIsbn(?string $raw): string
    {
        if ($raw === null) return '';
        return preg_replace('/[^0-9Xx-]/', '', trim($raw));
    }

    public function fetchAllBooks(array $filters = [], int $page = 1, int $pageSize = 20): array
    {
        $page = max(1, (int)$page);
        $pageSize = max(1, min(100, (int)$pageSize));
        $offset = ($page - 1) * $pageSize;

        $isbnList = "'" . implode("','", $this->availableCovers) . "'";
        $authorExpr = $this->authorExpr('a');

        $sql = "
            SELECT b.*,
                GROUP_CONCAT($authorExpr SEPARATOR ', ') AS author,
                GROUP_CONCAT(DISTINCT g.name SEPARATOR ', ') AS genre,
                (b.isbn IN ($isbnList)) AS has_cover
            FROM tbl_books b
            LEFT JOIN tbl_book_authors ba ON ba.book_id = b.book_id
            LEFT JOIN tbl_authors a ON a.author_id = ba.author_id
            LEFT JOIN tbl_book_genres bg ON b.book_id = bg.book_id
            LEFT JOIN tbl_genres g ON bg.genre_id = g.genre_id
            WHERE 1=1
        ";

        $params = [];
        if (!empty($filters['search'])) {
            $sql .= " AND (b.title LIKE ? OR b.isbn LIKE ? OR $authorExpr LIKE ? OR g.name LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
        }
        if (!empty($filters['genre']) && $filters['genre'] !== 'all') {
            $sql .= " AND g.name = ?";
            $params[] = $filters['genre'];
        }

        $sql .= " GROUP BY b.book_id";
        $sql .= " ORDER BY has_cover DESC, b.title ASC";
        $sql .= " LIMIT ? OFFSET ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([...$params, $pageSize, $offset]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as &$row) {
            if (isset($row['title'])) {
                $row['title'] = $this->normalizeTitle($row['title']);
            }
        }
        return $rows;
    }

    public function countBooks(array $filters = []): int
    {
        $sql = "SELECT COUNT(DISTINCT b.book_id) as total FROM tbl_books b
                LEFT JOIN tbl_book_genres bg ON b.book_id = bg.book_id
                LEFT JOIN tbl_genres g ON bg.genre_id = g.genre_id
                WHERE 1=1";
        $params = [];
        if (!empty($filters['search'])) {
            $sql .= " AND (b.title LIKE ? OR b.isbn LIKE ? OR g.name LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params = [$searchTerm, $searchTerm, $searchTerm];
        }
        if (!empty($filters['genre']) && $filters['genre'] !== 'all') {
            $sql .= " AND g.name = ?";
            $params[] = $filters['genre'];
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return (int)($result['total'] ?? 0);
    }

    public function getTotalCount(): int
    {
        $sql = "SELECT COUNT(DISTINCT book_id) as total FROM tbl_books";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    }

    public function getAvailableCount(): int
    {
        $sql = "SELECT COUNT(*) as available FROM tbl_book_copies WHERE status = 'available'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['available'] ?? 0);
    }


    /**
     * Counts the total number of book copies in the library.
     * 
     * @return int Total number of book copies
     */
    public function countAllBookCopies(): int
    {
        $sql = "SELECT COUNT(*) as total FROM tbl_book_copies";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    }


    public function getCatalogItems(array $filters, int $limit, int $offset): array
    {
        $search = $filters['search'] ?? '';
        $genre = $filters['genre'] ?? '';
        $availableOnly = $filters['available_only'] ?? false;

        $isbnList = "'" . implode("','", $this->availableCovers) . "'";
        $authorExpr = $this->authorExpr('a');

        $sql = "SELECT DISTINCT 
                b.book_id,
                b.isbn,
                b.title,
                b.edition,
                b.publication_year,
                b.cover_image,
                b.description,
                p.name as publisher_name,
                GROUP_CONCAT(DISTINCT $authorExpr ORDER BY ba.author_order SEPARATOR ', ') as authors,
                GROUP_CONCAT(DISTINCT g.name SEPARATOR ', ') as genres,
                COUNT(DISTINCT bc.copy_id) as total_copies,
                SUM(CASE WHEN bc.status = 'available' THEN 1 ELSE 0 END) as available_copies,
                (b.isbn IN ($isbnList)) AS has_cover
            FROM tbl_books b
            LEFT JOIN tbl_publishers p ON b.publisher_id = p.publisher_id
            LEFT JOIN tbl_book_authors ba ON b.book_id = ba.book_id
            LEFT JOIN tbl_authors a ON ba.author_id = a.author_id
            LEFT JOIN tbl_book_copies bc ON b.book_id = bc.book_id
            LEFT JOIN tbl_book_genres bg ON b.book_id = bg.book_id
            LEFT JOIN tbl_genres g ON bg.genre_id = g.genre_id
            WHERE 1=1";

        $params = [];
        if (!empty($search)) {
            $sql .= " AND (
            b.title LIKE :search1
            OR b.isbn LIKE :search2
            OR (" . $authorExpr . ") LIKE :search3
            OR g.name LIKE :search4
        )";
            $params[':search1'] = '%' . $search . '%';
            $params[':search2'] = '%' . $search . '%';
            $params[':search3'] = '%' . $search . '%';
            $params[':search4'] = '%' . $search . '%';
        }
        if (!empty($genre) && $genre !== 'all') {
            $genreSanitized = self::sanitizeGenre($genre);
            $sql .= " AND g.name = :genre";
            $params[':genre'] = $genreSanitized;
        }

        $sql .= " GROUP BY b.book_id";
        if ($availableOnly) {
            $sql .= " HAVING available_copies > 0";
        }
        $sql .= " ORDER BY has_cover DESC, b.title ASC LIMIT :limit OFFSET :offset";

        // Debug logging
        error_log("SQL Query: " . $sql);
        error_log("Query Params: " . print_r($params, true));
        error_log("Limit: " . $limit . ", Offset: " . $offset);

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        // Log the final prepared query (for debugging)
        ob_start();
        $stmt->debugDumpParams();
        error_log("Prepared Query: " . ob_get_clean());

        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $bookIds = [];
        foreach ($rows as &$row) {
            if (isset($row['title'])) {
                $row['title'] = $this->normalizeTitle($row['title']);
            }
            $isbn = $row['isbn'] ?? '';
            $coverFilename = $isbn . '.webp';
            if (in_array($isbn, $this->availableCovers)) {
                $row['cover_image'] = '/assets/covers/' . $coverFilename;
            } elseif (!empty($row['cover_image'])) {
                $row['cover_image'] = '/assets/covers/' . $row['cover_image'];
            } else {
                $row['cover_image'] = '/assets/images/nocover.png';
            }
            if (isset($row['book_id'])) {
                $bookIds[] = (int)$row['book_id'];
            }
        }

        if (empty($bookIds)) {
            return $rows;
        }

        $placeholders = implode(',', array_fill(0, count($bookIds), '?'));
        $copiesSql = "SELECT copy_id, book_id, accession_no, call_no, status, edition
                  FROM tbl_book_copies
                  WHERE book_id IN ($placeholders)";
        $copiesParams = $bookIds;
        if ($availableOnly) {
            $copiesSql .= " AND status = 'available'";
        }
        $copiesSql .= " ORDER BY book_id ASC, status = 'available' DESC, accession_no ASC";

        $copiesStmt = $this->pdo->prepare($copiesSql);
        $copiesStmt->execute($copiesParams);
        $copiesRows = $copiesStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $copiesByBook = [];
        foreach ($copiesRows as $c) {
            $bid = (int)$c['book_id'];
            if (!isset($copiesByBook[$bid])) {
                $copiesByBook[$bid] = [];
            }
            $copiesByBook[$bid][] = [
                'copy_id' => (int)$c['copy_id'],
                'accession_no' => $c['accession_no'],
                'call_no' => $c['call_no'],
                'status' => $c['status'],
                'edition' => $c['edition'],
            ];
        }

        foreach ($rows as &$row) {
            $bid = (int)($row['book_id'] ?? 0);
            $row['copies'] = $copiesByBook[$bid] ?? [];
        }

        return $rows;
    }


    public function countCatalogItems(array $filters): int
    {
        $search = $filters['search'] ?? '';
        $genre = $filters['genre'] ?? '';
        $availableOnly = $filters['available_only'] ?? false;
        $authorExpr = $this->authorExpr('a');

        $sql = "SELECT COUNT(DISTINCT b.book_id) as total
            FROM tbl_books b
            LEFT JOIN tbl_book_authors ba ON b.book_id = ba.book_id
            LEFT JOIN tbl_authors a ON ba.author_id = a.author_id
            LEFT JOIN tbl_book_copies bc ON b.book_id = bc.book_id
            LEFT JOIN tbl_book_genres bg ON b.book_id = bg.book_id
            LEFT JOIN tbl_genres g ON bg.genre_id = g.genre_id
            WHERE 1=1";
        $params = [];
        if (!empty($search)) {
            $sql .= " AND (
                b.title LIKE :search1
                OR b.isbn LIKE :search2
                OR (" . $authorExpr . ") LIKE :search3
                OR g.name LIKE :search4
            )";
            $params[':search1'] = '%' . $search . '%';
            $params[':search2'] = '%' . $search . '%';
            $params[':search3'] = '%' . $search . '%';
            $params[':search4'] = '%' . $search . '%';
        }
        if (!empty($genre) && $genre !== 'all') {
            $sql .= " AND g.name = :genre";
            $params[':genre'] = $genre;
        }
        if ($availableOnly) {
            $sql .= " AND bc.status = 'available'";
        }
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    }

    public function getAllGenres(): array
    {
        $sql = "SELECT DISTINCT name AS genre FROM tbl_genres ORDER BY name ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_column($results, 'genre');
    }

    public function searchBooks(array $filters = [], int $page = 1, int $pageSize = 20): array
    {
        $page = max(1, (int)$page);
        $pageSize = max(1, min(100, (int)$pageSize));
        $offset = ($page - 1) * $pageSize;
        $title = isset($filters['title']) ? trim((string)$filters['title']) : '';
        $author = isset($filters['author']) ? trim((string)$filters['author']) : '';
        $genre = isset($filters['genre']) ? trim((string)$filters['genre']) : '';
        $isbn = $this->normalizeIsbn($filters['isbn'] ?? null);

        $authorExpr = $this->authorExpr('a');
        $isbnList = "'" . implode("','", $this->availableCovers) . "'";

        $sql = "
            SELECT b.*,
                GROUP_CONCAT($authorExpr SEPARATOR ', ') AS author,
                GROUP_CONCAT(DISTINCT g.name SEPARATOR ', ') AS genre,
                (b.isbn IN ($isbnList)) AS has_cover
            FROM tbl_books b
            LEFT JOIN tbl_book_authors ba ON ba.book_id = b.book_id
            LEFT JOIN tbl_authors a ON a.author_id = ba.author_id
            LEFT JOIN tbl_book_genres bg ON b.book_id = bg.book_id
            LEFT JOIN tbl_genres g ON bg.genre_id = g.genre_id
            WHERE 1=1
        ";
        $params = [];
        if ($title !== '') {
            $sql .= " AND b.title LIKE ?";
            $params[] = '%' . $title . '%';
        }
        if ($isbn !== '') {
            $sql .= " AND (REPLACE(b.isbn, '-', '') = REPLACE(?, '-', '') OR REPLACE(b.isbn13, '-', '') = REPLACE(?, '-', ''))";
            $params[] = $isbn;
            $params[] = $isbn;
        }
        if ($author !== '') {
            $sql .= "
                AND EXISTS (
                    SELECT 1
                    FROM tbl_book_authors sba
                    JOIN tbl_authors sa ON sa.author_id = sba.author_id
                    WHERE sba.book_id = b.book_id
                    AND " . $this->authorExpr('sa') . " LIKE ?
                )
            ";
            $params[] = '%' . $author . '%';
        }
        if ($genre !== '') {
            $sql .= " AND g.name LIKE ?";
            $params[] = '%' . $genre . '%';
        }

        $sql .= " GROUP BY b.book_id";
        $sql .= " ORDER BY has_cover DESC, b.title ASC";
        $sql .= " LIMIT ? OFFSET ?";

        $params[] = $pageSize;
        $params[] = $offset;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($rows as &$row) {
            if (isset($row['title'])) {
                $row['title'] = $this->normalizeTitle($row['title']);
            }
        }
        return $rows;
    }

    public function countSearchBooks(array $filters = []): int
    {
        $title = isset($filters['title']) ? trim((string)$filters['title']) : '';
        $author = isset($filters['author']) ? trim((string)$filters['author']) : '';
        $genre = isset($filters['genre']) ? trim((string)$filters['genre']) : '';
        $isbn = $this->normalizeIsbn($filters['isbn'] ?? null);
        $params = [];

        $sql = "
            SELECT COUNT(DISTINCT b.book_id) AS total
            FROM tbl_books b
            LEFT JOIN tbl_book_genres bg ON b.book_id = bg.book_id
            LEFT JOIN tbl_genres g ON bg.genre_id = g.genre_id
            WHERE 1=1
        ";
        if ($title !== '') {
            $sql .= " AND b.title LIKE ?";
            $params[] = '%' . $title . '%';
        }
        if ($isbn !== '') {
            $sql .= " AND (REPLACE(b.isbn, '-', '') = REPLACE(?, '-', '') OR REPLACE(b.isbn13, '-', '') = REPLACE(?, '-', ''))";
            $params[] = $isbn;
            $params[] = $isbn;
        }
        if ($author !== '') {
            $sql .= "
                AND EXISTS (
                    SELECT 1
                    FROM tbl_book_authors sba
                    JOIN tbl_authors sa ON sa.author_id = sba.author_id
                    WHERE sba.book_id = b.book_id
                    AND " . $this->authorExpr('sa') . " LIKE ?
                )
            ";
            $params[] = '%' . $author . '%';
        }
        if ($genre !== '') {
            $sql .= " AND g.name LIKE ?";
            $params[] = '%' . $genre . '%';
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }

    public function fetchBookById(int $id): ?array
    {
        try {
            $sql = "
                SELECT 
                    b.*,
                    GROUP_CONCAT(DISTINCT g.name SEPARATOR ', ') AS genre,
                    GROUP_CONCAT(
                        DISTINCT CONCAT(
                            a.first_name,
                            IF(a.middle_name IS NOT NULL AND a.middle_name != '', CONCAT(' ', a.middle_name), ''),
                            ' ',
                            a.last_name
                        )
                        ORDER BY ba.author_order 
                        SEPARATOR ', '
                    ) AS authors,
                    (
                        SELECT copy_id 
                        FROM tbl_book_copies
                        WHERE book_id = b.book_id AND status = 'available'
                        LIMIT 1
                    ) AS copy_id
                FROM tbl_books b
                LEFT JOIN tbl_book_genres bg ON b.book_id = bg.book_id
                LEFT JOIN tbl_genres g ON bg.genre_id = g.genre_id
                LEFT JOIN tbl_book_authors ba ON b.book_id = ba.book_id
                LEFT JOIN tbl_authors a ON ba.author_id = a.author_id
                WHERE b.book_id = ?
                GROUP BY b.book_id
            ";

            $stmt = $this->pdo->prepare($sql);
            if (!$stmt) {
                error_log(sprintf(
                    'Database prepare failed in fetchBookById for book ID %d. Error: %s',
                    $id,
                    json_encode($this->pdo->errorInfo())
                ));
                return null;
            }

            $executed = $stmt->execute([$id]);
            if (!$executed) {
                error_log(sprintf(
                    'Database query failed in fetchBookById for book ID %d. Error: %s',
                    $id,
                    json_encode($stmt->errorInfo())
                ));
                return null;
            }

            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

            // Apply title normalization if data exists
            if ($row && isset($row['title'])) {
                $row['title'] = $this->normalizeTitle($row['title']);
            }

            return $row;
        } catch (\PDOException $e) {
            error_log(sprintf(
                'PDOException in fetchBookById for book ID %d: %s\nStack trace: %s',
                $id,
                $e->getMessage(),
                $e->getTraceAsString()
            ));
            return null;
        }
    }


    // Create book + relations + copies atomically
    public function insertBook(array $data): int
    {
        $this->pdo->beginTransaction();

        try {
            $publisherId = $this->resolvePublisherId($data['publisher'] ?? null);

            $stmt = $this->pdo->prepare(
                "INSERT INTO tbl_books
             (isbn, title, edition, volume, publication_year, publisher_id, pages, language, description, cover_image, genre, created_at, updated_at)
             VALUES
             (:isbn, :title, :edition, :volume, :publication_year, :publisher_id, :pages, :language, :description, :cover_image, :genre, NOW(), NOW())"
            );

            $stmt->execute([
                ':isbn'             => $data['isbn'] ?? null,
                ':title'            => $data['title'], // required
                ':edition'          => $data['edition'] ?? null,
                ':volume'           => $data['volume'] ?? null,
                ':publication_year' => $data['publication_year'] ?? ($data['year'] ?? null),
                ':publisher_id'     => $publisherId,
                ':pages'            => isset($data['pages']) ? (int) $data['pages'] : null,
                ':language'         => $data['language'] ?? null, // DB default 'English' if null
                ':description'      => $data['description'] ?? null,
                ':cover_image'      => $data['cover_image'] ?? ($data['cover'] ?? null),
                ':genre'            => $data['genre'] ?? null
            ]);

            $bookId = (int) $this->pdo->lastInsertId();

            // Authors (string to IDs, then link with order)
            if (!empty($data['author'])) {
                $authorIds = $this->getOrCreateAuthorIds($data['author']);
                $this->linkBookAuthors($bookId, $authorIds);
            }

            // Genre link table (optional single-genre from UI)
            if (!empty($data['genre'])) {
                $genreId = $this->getOrCreateGenreId($data['genre']);
                $this->linkBookGenres($bookId, [$genreId]);
            }

            // Call number (normalized parts + display string for copies)
            $cn = $this->normalizeCallNumber($data);
            $this->insertCallNumber('book', $bookId, $cn);
            $callNo = $this->formatCallNo($cn);

            // Physical copies
            $copies = isset($data['copies']) ? max(0, (int) $data['copies']) : 1;
            $baseAccession = $data['accessionCode'] ?? ($data['accession_no'] ?? null);

            if ($copies > 0) {
                $this->insertBookCopies(
                    $bookId,
                    $copies,
                    $baseAccession,
                    $callNo,
                    $data['edition'] ?? null
                );
            }

            $this->pdo->commit();
            return $bookId;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }


    // Edit book + relations atomically (non-destructive to existing copies unless adding)
    public function updateBook(int $bookId, array $data): void
    {
        $this->pdo->beginTransaction();
        try {
            $publisherId = $this->resolvePublisherId($data['publisher'] ?? null);

            $stmt = $this->pdo->prepare(
                "UPDATE tbl_books
                 SET isbn = :isbn,
                     title = :title,
                     edition = :edition,
                     volume = :volume,
                     publication_year = :publication_year,
                     publisher_id = :publisher_id,
                     pages = :pages,
                     language = :language,
                     description = :description,
                     cover_image = :cover_image,
                     genre = :genre,
                     updated_at = NOW()
                 WHERE book_id = :book_id"
            );

            $stmt->execute([
                ':isbn'              => $data['isbn']            ?? null,
                ':title'             => $data['title'], // required
                ':edition'           => $data['edition']         ?? null,
                ':volume'            => $data['volume']          ?? null,
                ':publication_year'  => $data['publication_year'] ?? ($data['year'] ?? null),
                ':publisher_id'      => $publisherId,
                ':pages'             => isset($data['pages']) ? (int)$data['pages'] : null,
                ':language'          => $data['language']        ?? null,
                ':description'       => $data['description']     ?? null,
                ':cover_image'       => $data['cover_image']     ?? ($data['cover'] ?? null),
                ':genre'             => $data['genre']           ?? null,
                ':book_id'           => $bookId,
            ]);

            // Replace author links if authors provided
            if (array_key_exists('author', $data)) {
                $this->unlinkBookAuthors($bookId);
                if (!empty($data['author'])) {
                    $authorIds = $this->getOrCreateAuthorIds($data['author']);
                    $this->linkBookAuthors($bookId, $authorIds);
                }
            }

            // Replace genre link if provided (single-genre UI)
            if (array_key_exists('genre', $data)) {
                $this->unlinkBookGenres($bookId);
                if (!empty($data['genre'])) {
                    $genreId = $this->getOrCreateGenreId($data['genre']);
                    $this->linkBookGenres($bookId, [$genreId]);
                }
            }

            // Upsert call number and cascade display call_no to copies
            if ($this->hasCallNumber('book', $bookId)) {
                $cn = $this->normalizeCallNumber($data, true);
                $this->updateCallNumber('book', $bookId, $cn);
                $callNo = $this->formatCallNo($cn);
                $this->updateCopiesCallNoAndEdition($bookId, $callNo, $data['edition'] ?? null);
            } else {
                $cn = $this->normalizeCallNumber($data);
                $this->insertCallNumber('book', $bookId, $cn);
                $callNo = $this->formatCallNo($cn);
                $this->updateCopiesCallNoAndEdition($bookId, $callNo, $data['edition'] ?? null);
            }

            // If increasing copy count, add new copies (non-destructive)
            if (isset($data['copies'])) {
                $target = max(0, (int)$data['copies']);
                $current = $this->countBookCopies($bookId);
                if ($target > $current) {
                    $baseAccession = $data['accessionCode'] ?? ($data['accession_no'] ?? null);
                    $callNo = $callNo ?? $this->formatCallNo($this->loadCallNumber('book', $bookId));
                    $this->insertBookCopies($bookId, $target - $current, $baseAccession, $callNo, $data['edition'] ?? null, $current);
                }
            }

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    // ---------- Helpers (single-responsibility, reused) ----------

    private function resolvePublisherId(?string $name): ?int
    {
        if ($name === null || trim($name) === '') return null;
        $name = trim($name);

        $find = $this->pdo->prepare("SELECT publisher_id FROM tbl_publishers WHERE name = :name LIMIT 1");
        $find->execute([':name' => $name]);
        $row = $find->fetch(PDO::FETCH_ASSOC);
        if ($row) return (int)$row['publisher_id'];

        $ins = $this->pdo->prepare("INSERT INTO tbl_publishers (name, created_at, updated_at) VALUES (:name, NOW(), NOW())");
        $ins->execute([':name' => $name]);
        return (int)$this->pdo->lastInsertId();
    }

    private function getOrCreateAuthorIds(string $authorString): array
    {
        // Split on semicolons first, then commas if no semicolons present
        $parts = str_contains($authorString, ';')
            ? array_map('trim', explode(';', $authorString))
            : array_map('trim', explode(',', $authorString));

        $ids = [];
        foreach ($parts as $i => $full) {
            if ($full === '') continue;
            // Naive "first ... last" split; adapt as needed
            $tokens = preg_split('/\s+/', $full);
            $last = array_pop($tokens) ?: '';
            $first = implode(' ', $tokens);

            $sel = $this->pdo->prepare(
                "SELECT author_id FROM tbl_authors WHERE first_name = :first AND last_name = :last LIMIT 1"
            );
            $sel->execute([':first' => $first, ':last' => $last]);
            $row = $sel->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $ids[] = (int)$row['author_id'];
                continue;
            }

            $ins = $this->pdo->prepare(
                "INSERT INTO tbl_authors (first_name, last_name, created_at, updated_at)
                 VALUES (:first, :last, NOW(), NOW())"
            );
            $ins->execute([':first' => $first, ':last' => $last]);
            $ids[] = (int)$this->pdo->lastInsertId();
        }
        return $ids;
    }

    private function linkBookAuthors(int $bookId, array $authorIds): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO tbl_book_authors (book_id, author_id, author_order, role, created_at)
             VALUES (:book_id, :author_id, :author_order, 'Author', NOW())"
        );
        $order = 1;
        foreach ($authorIds as $aid) {
            $stmt->execute([
                ':book_id' => $bookId,
                ':author_id' => $aid,
                ':author_order' => $order++,
            ]);
        }
    }

    private function unlinkBookAuthors(int $bookId): void
    {
        $del = $this->pdo->prepare("DELETE FROM tbl_book_authors WHERE book_id = :book_id");
        $del->execute([':book_id' => $bookId]);
    }

    private function getOrCreateGenreId(string $name): int
    {
        $name = trim($name);
        $sel = $this->pdo->prepare("SELECT genre_id FROM tbl_genres WHERE name = :name LIMIT 1");
        $sel->execute([':name' => $name]);
        $row = $sel->fetch(PDO::FETCH_ASSOC);
        if ($row) return (int)$row['genre_id'];

        $ins = $this->pdo->prepare("INSERT INTO tbl_genres (name) VALUES (:name)");
        $ins->execute([':name' => $name]);
        return (int)$this->pdo->lastInsertId();
    }

    private function linkBookGenres(int $bookId, array $genreIds): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT IGNORE INTO tbl_book_genres (book_id, genre_id) VALUES (:book_id, :genre_id)"
        );
        foreach ($genreIds as $gid) {
            $stmt->execute([':book_id' => $bookId, ':genre_id' => $gid]);
        }
    }

    private function unlinkBookGenres(int $bookId): void
    {
        $del = $this->pdo->prepare("DELETE FROM tbl_book_genres WHERE book_id = :book_id");
        $del->execute([':book_id' => $bookId]);
    }

    private function normalizeCallNumber(array $data, bool $partial = false): array
    {
        $py = $data['publication_year'] ?? ($data['year'] ?? null);
        $cn = $data['callNumber'] ?? [];

        $shelf = $cn['shelf'] ?? ($data['shelf'] ?? null);
        $cc    = $cn['classificationCode'] ?? ($data['classificationCode'] ?? null);
        $num   = $cn['classificationNumber'] ?? ($data['classificationNumber'] ?? null);
        $cutter = $cn['cutter'] ?? ($data['cutter'] ?? null);
        $year  = $cn['year'] ?? $py;

        // In add: require all; in edit: keep provided values
        if (!$partial && (!$shelf || !$cc || !$num || !$cutter || !$year)) {
            throw new InvalidArgumentException('Missing call number components.');
        }
        return [
            'shelf_number' => $shelf,
            'classification_code' => $cc,
            'classification_number' => $num,
            'cutter' => $cutter,
            'year' => $year,
        ];
    }

    private function formatCallNo(array $cn): string
    {
        $parts = array_filter([
            $cn['shelf_number'] ?? null,
            $cn['classification_code'] ?? null,
            $cn['classification_number'] ?? null,
            $cn['cutter'] ?? null,
            $cn['year'] ?? null,
        ], fn($v) => $v !== null && $v !== '');
        return implode(' ', $parts);
    }

    private function insertCallNumber(string $refType, int $refId, array $cn): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO tbl_call_number
             (shelf_number, classification_code, classification_number, cutter, year, reference_type, reference_id, created_at, updated_at)
             VALUES (:shelf, :cc, :num, :cutter, :year, :rt, :rid, NOW(), NOW())"
        );
        $stmt->execute([
            ':shelf'  => $cn['shelf_number'],
            ':cc'     => $cn['classification_code'],
            ':num'    => $cn['classification_number'],
            ':cutter' => $cn['cutter'],
            ':year'   => $cn['year'],
            ':rt'     => $refType,
            ':rid'    => $refId,
        ]);
    }

    private function hasCallNumber(string $refType, int $refId): bool
    {
        $q = $this->pdo->prepare(
            "SELECT 1 FROM tbl_call_number WHERE reference_type = :rt AND reference_id = :rid LIMIT 1"
        );
        $q->execute([':rt' => $refType, ':rid' => $refId]);
        return (bool)$q->fetchColumn();
    }

    private function loadCallNumber(string $refType, int $refId): array
    {
        $q = $this->pdo->prepare(
            "SELECT shelf_number, classification_code, classification_number, cutter, year
             FROM tbl_call_number WHERE reference_type = :rt AND reference_id = :rid LIMIT 1"
        );
        $q->execute([':rt' => $refType, ':rid' => $refId]);
        $row = $q->fetch(PDO::FETCH_ASSOC) ?: [];
        return [
            'shelf_number' => $row['shelf_number'] ?? null,
            'classification_code' => $row['classification_code'] ?? null,
            'classification_number' => $row['classification_number'] ?? null,
            'cutter' => $row['cutter'] ?? null,
            'year' => $row['year'] ?? null,
        ];
    }

    private function updateCallNumber(string $refType, int $refId, array $cn): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE tbl_call_number
             SET shelf_number = :shelf,
                 classification_code = :cc,
                 classification_number = :num,
                 cutter = :cutter,
                 year = :year,
                 updated_at = NOW()
             WHERE reference_type = :rt AND reference_id = :rid"
        );
        $stmt->execute([
            ':shelf'  => $cn['shelf_number'],
            ':cc'     => $cn['classification_code'],
            ':num'    => $cn['classification_number'],
            ':cutter' => $cn['cutter'],
            ':year'   => $cn['year'],
            ':rt'     => $refType,
            ':rid'    => $refId,
        ]);
    }

    public function countBookCopies(int $bookId): int
    {
        $q = $this->pdo->prepare("SELECT COUNT(*) FROM tbl_book_copies WHERE book_id = :bid");
        $q->execute([':bid' => $bookId]);
        return (int)$q->fetchColumn();
    }

    private function insertBookCopies(
        int $bookId,
        int $count,
        ?string $baseAccession,
        string $callNo,
        ?string $edition,
        int $existingCount = 0
    ): void {
        $stmt = $this->pdo->prepare(
            "INSERT INTO tbl_book_copies
             (book_id, accession_no, call_no, acquisition_date, `condition`, edition, created_at, updated_at)
             VALUES (:bid, :acc, :call, NULL, 'good', :edition, NOW(), NOW())"
        );

        // Determine numbering start
        $startIndex = $existingCount + 1;

        // If base has trailing digits, continue from that; else append -001..N
        $pad = 3;
        $base = $baseAccession;

        // If only 1 new copy and base provided, use base as-is
        if ($count === 1 && $existingCount === 0 && $base) {
            $stmt->execute([
                ':bid' => $bookId,
                ':acc' => $base,
                ':call' => $callNo,
                ':edition' => $edition,
            ]);
            return;
        }

        // Determine suffix strategy
        $hasNumericSuffix = false;
        $startNum = 1;
        if ($base && preg_match('/^(.*?)(\d+)$/', $base, $m)) {
            $hasNumericSuffix = true;
            $base = $m[1];
            $startNum = (int)$m[2];
            $pad = strlen($m[2]);
        }

        for ($i = 0; $i < $count; $i++) {
            if ($base) {
                if ($hasNumericSuffix) {
                    $acc = $base . str_pad((string)($startNum + $i), $pad, '0', STR_PAD_LEFT);
                } else {
                    $acc = rtrim($base, '-') . '-' . str_pad((string)($startIndex + $i), $pad, '0', STR_PAD_LEFT);
                }
            } else {
                $acc = 'B' . $bookId . '-' . str_pad((string)($startIndex + $i), $pad, '0', STR_PAD_LEFT);
            }

            $stmt->execute([
                ':bid' => $bookId,
                ':acc' => $acc,
                ':call' => $callNo,
                ':edition' => $edition,
            ]);
        }
    }

    private function updateCopiesCallNoAndEdition(int $bookId, string $callNo, ?string $edition): void
    {
        $sql = "UPDATE tbl_book_copies SET call_no = :call_no, edition = :edition, updated_at = NOW() WHERE book_id = :bid";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':call_no' => $callNo, ':edition' => $edition, ':bid' => $bookId]);
    }

    public function deleteBook(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM tbl_books WHERE book_id = ?");
        return $stmt->execute([$id]);
    }

    public function getBookTitleByCopyId(int $copyId): ?string
    {
        $sql = "SELECT b.title
              FROM tbl_book_copies c
              JOIN tbl_books b ON c.book_id = b.book_id
             WHERE c.copy_id = :copy_id
             LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':copy_id' => $copyId]);
        $title = $stmt->fetchColumn();
        return $title ? $this->normalizeTitle($title) : null;
    }


    public function countCopiesByStatus(?string $status = null): int
    {
        // Normalize and whitelist status; accept minor variants from UI
        $normalized = null;
        if ($status !== null) {
            $s = strtolower(preg_replace('/[^a-z_]/i', '', $status));
            // Allow common variants; map to the DB enum
            $map = [
                'available'   => 'available',
                'checkedout'  => 'checked_out',
                'checked_out' => 'checked_out',
                'onhold'      => 'on_hold',
                'on_hold'     => 'on_hold',
                'intransit'   => 'in_transit',
                'in_transit'  => 'in_transit',
                'lost'        => 'lost',
                'damaged'     => 'damaged',
                'withdrawn'   => 'withdrawn',
                'missing'     => 'missing',
            ];
            $normalized = $map[$s] ?? null;
        }

        if ($normalized === null) {
            $sql = "SELECT COUNT(*) FROM tbl_book_copies";
            $stmt = $this->pdo->query($sql);
            return (int)$stmt->fetchColumn();
        }

        $sql = "SELECT COUNT(*) FROM tbl_book_copies WHERE status = :status";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':status' => $normalized]);
        return (int)$stmt->fetchColumn();
    }
}
