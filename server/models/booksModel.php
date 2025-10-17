<?php
// server/models/booksModel.php

require_once __DIR__ . '/../config/database.php';

/**
 * Model for handling book-related database operations.
 *
 * This class provides methods for fetching, creating, updating, and deleting book records.
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

    /**
     * Creates an instance of BooksModel and gets a PDO connection.
     */
    public function __construct()
    {
        $this->pdo = getPDO();
    }

    /**
     * Helper function to create a consistent author name expression for SQL queries.
     *
     * @param string $alias The table alias for the authors table.
     * @return string The SQL expression for the author's full name.
     */
    private function authorExpr(string $alias = 'a'): string
    {
        return "
            TRIM(
                CONCAT(
                    IF($alias.first_name IS NULL OR $alias.first_name = 'No First Name', '', CONCAT($alias.first_name, ' ')),
                    IF($alias.middle_name IS NULL OR $alias.middle_name = 'No Middle Name', '', CONCAT($alias.middle_name, ' ')),
                    IF($alias.last_name IS NULL OR $alias.last_name = 'No Last Name', '', $alias.last_name)
                )
            )
        ";
    }

    /**
     * Helper function to normalize an ISBN string by removing invalid characters.
     *
     * @param string|null $raw The raw ISBN string.
     * @return string The normalized ISBN string.
     */
    private function normalizeIsbn(?string $raw): string
    {
        if ($raw === null) return '';
        return preg_replace('/[^0-9Xx-]/', '', trim($raw));
    }

    /**
     * Fetches all books with pagination.
     *
     * @param array $filters An associative array of filters.
     * @param int $page The current page number.
     * @param int $pageSize The number of items per page.
     * @return array An array of book records.
     */
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
                (b.isbn IN ($isbnList)) AS has_cover
            FROM tbl_books b
            LEFT JOIN tbl_book_authors ba ON ba.book_id = b.book_id
            LEFT JOIN tbl_authors a ON a.author_id = ba.author_id
            WHERE 1=1
        ";

        $params = [];
        if (!empty($filters)) {
            if (!empty($filters['search'])) {
                $sql .= " AND (b.title LIKE ? OR b.isbn LIKE ? OR b.publisher LIKE ?)";
                $searchTerm = "%{$filters['search']}%";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
            }
        }

        $sql .= " GROUP BY b.book_id";
        $sql .= " ORDER BY has_cover DESC, b.title ASC";
        $sql .= " LIMIT ? OFFSET ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([...$params, $pageSize, $offset]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Counts the total number of books for pagination.
     *
     * @param array $filters An associative array of filters.
     * @return int The total number of books.
     */
    public function countBooks(array $filters = []): int
    {
        $sql = "SELECT COUNT(DISTINCT b.book_id) as total FROM tbl_books b WHERE 1=1";
        $params = [];

        if (!empty($filters)) {
            if (!empty($filters['search'])) {
                $sql .= " AND (b.title LIKE ? OR b.isbn LIKE ? OR b.publisher LIKE ?)";
                $searchTerm = "%{$filters['search']}%";
                $params = [$searchTerm, $searchTerm, $searchTerm];
            }
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return (int)($result['total'] ?? 0);
    }

    /**
     * Searches for books by title, author, or ISBN with pagination.
     *
     * @param array $filters An associative array of search filters.
     * @param int $page The current page number.
     * @param int $pageSize The number of items per page.
     * @return array An array of book records.
     */
    public function searchBooks(array $filters = [], int $page = 1, int $pageSize = 20): array
    {
        $page = max(1, (int)$page);
        $pageSize = max(1, min(100, (int)$pageSize));
        $offset = ($page - 1) * $pageSize;

        $title = isset($filters['title']) ? trim((string)$filters['title']) : '';
        $author = isset($filters['author']) ? trim((string)$filters['author']) : '';
        $isbn = $this->normalizeIsbn($filters['isbn'] ?? null);

        $authorExpr = $this->authorExpr('a');
        $isbnList = "'" . implode("','", $this->availableCovers) . "'";

        $sql = "
            SELECT b.*,
                GROUP_CONCAT($authorExpr SEPARATOR ', ') AS author,
                (b.isbn IN ($isbnList)) AS has_cover
            FROM tbl_books b
            LEFT JOIN tbl_book_authors ba ON ba.book_id = b.book_id
            LEFT JOIN tbl_authors a ON a.author_id = ba.author_id
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

        $sql .= " GROUP BY b.book_id";
        $sql .= " ORDER BY has_cover DESC, b.title ASC";
        $sql .= " LIMIT ? OFFSET ?";

        $params[] = $pageSize;
        $params[] = $offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Counts the total number of books matching search criteria.
     *
     * @param array $filters An associative array of search filters.
     * @return int The total number of matching books.
     */
    public function countSearchBooks(array $filters = []): int
    {
        $title = isset($filters['title']) ? trim((string)$filters['title']) : '';
        $author = isset($filters['author']) ? trim((string)$filters['author']) : '';
        $isbn = $this->normalizeIsbn($filters['isbn'] ?? null);

        $params = [];

        $sql = "
            SELECT COUNT(DISTINCT b.book_id) AS total
            FROM tbl_books b
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

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }

    /**
     * Fetches a book by its ID.
     *
     * @param int $id The ID of the book to fetch.
     * @return array|null The book record, or null if not found.
     */
    public function fetchBookById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM tbl_books WHERE book_id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Inserts a new book into the database.
     *
     * @param array $data An associative array of book data.
     * @return int The ID of the newly inserted book.
     */
    public function insertBook(array $data): int
    {
        $sql = "INSERT INTO tbl_books 
            (isbn, isbn13, title, subtitle, edition, volume, publication_year, publisher_id, pages, language, description, cover_image, created_at, updated_at)
            VALUES 
            (:isbn, :isbn13, :title, :subtitle, :edition, :volume, :publication_year, :publisher_id, :pages, :language, :description, :cover_image, NOW(), NOW())";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':isbn' => $data['isbn'] ?? null,
            ':isbn13' => $data['isbn13'] ?? null,
            ':title' => $data['title'] ?? null,
            ':subtitle' => $data['subtitle'] ?? null,
            ':edition' => $data['edition'] ?? null,
            ':volume' => $data['volume'] ?? null,
            ':publication_year' => $data['publication_year'] ?? null,
            ':publisher_id' => $data['publisher_id'] ?? null,
            ':pages' => $data['pages'] ?? null,
            ':language' => $data['language'] ?? null,
            ':description' => $data['description'] ?? null,
            ':cover_image' => $data['cover_image'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Updates a book in the database.
     *
     * @param int $id The ID of the book to update.
     * @param array $data An associative array of book data.
     * @return bool True on success, false on failure.
     */
    public function updateBook(int $id, array $data): bool
    {
        $sql = "UPDATE tbl_books SET
            isbn = :isbn,
            isbn13 = :isbn13,
            title = :title,
            subtitle = :subtitle,
            edition = :edition,
            volume = :volume,
            publication_year = :publication_year,
            publisher_id = :publisher_id,
            pages = :pages,
            language = :language,
            description = :description,
            cover_image = :cover_image,
            updated_at = NOW()
            WHERE book_id = :book_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':isbn' => $data['isbn'] ?? null,
            ':isbn13' => $data['isbn13'] ?? null,
            ':title' => $data['title'] ?? null,
            ':subtitle' => $data['subtitle'] ?? null,
            ':edition' => $data['edition'] ?? null,
            ':volume' => $data['volume'] ?? null,
            ':publication_year' => $data['publication_year'] ?? null,
            ':publisher_id' => $data['publisher_id'] ?? null,
            ':pages' => $data['pages'] ?? null,
            ':language' => $data['language'] ?? null,
            ':description' => $data['description'] ?? null,
            ':cover_image' => $data['cover_image'] ?? null,
            ':book_id' => $id,
        ]);
    }

    /**
     * Deletes a book from the database.
     *
     * @param int $id The ID of the book to delete.
     * @return bool True on success, false on failure.
     */
    public function deleteBook(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM tbl_books WHERE book_id = ?");
        return $stmt->execute([$id]);
    }
}
