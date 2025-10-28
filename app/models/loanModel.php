<?php
// app/models/loanModel.php

require_once __DIR__ . '/../services/emailService.php';
require_once __DIR__ . '/userModel.php';
require_once __DIR__ . '/booksModel.php';

class LoanModel
{
    private $pdo;

    // Dependency Injection: receive PDO externally for testability and loose coupling
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
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

    public function createLoan($userId, $copyId, $borrowedDate, $dueDate)
    {
        $sql = "INSERT INTO tbl_borrowing_records
        (borrower_id, copy_id, borrowed_date, due_date, status)
        VALUES (:borrower_id, :copy_id, :borrowed_date, :due_date, 'pending')";
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([
            ':borrower_id' => $userId,
            ':copy_id' => $copyId,
            ':borrowed_date' => $borrowedDate,
            ':due_date' => $dueDate,
        ]);

        if ($result) {
            // Send email notification after successful loan creation
            $this->sendLoanPendingApprovalEmail($userId, $copyId);
        }

        return $result;
    }

    public function cancelLoan($loanId)
    {
        $sql = "UPDATE tbl_borrowing_records
            SET status = 'canceled', updated_at = NOW()
            WHERE borrow_id = :borrow_id AND status = 'pending'";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':borrow_id' => $loanId]);
    }


    public function approveLoan($loanId)
    {
        $sql = "UPDATE tbl_borrowing_records 
            SET status = 'borrowed', updated_at = NOW()
            WHERE borrow_id = :loan_id AND status = 'pending'";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':loan_id' => $loanId]);
    }

    public function rejectLoan($loanId)
    {
        $sql = "UPDATE tbl_borrowing_records 
            SET status = 'rejected', updated_at = NOW()
            WHERE borrow_id = :loan_id AND status = 'pending'";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':loan_id' => $loanId]);
    }


    // Mark a loan as returned (admin action)
    public function completeLoanReturn($loanId, $returnDate)
    {
        $sql = "UPDATE tbl_borrowing_records
            SET return_date = :return_date, status = 'returned', updated_at = NOW()
            WHERE borrow_id = :borrow_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':return_date' => $returnDate,
            ':borrow_id' => $loanId,
        ]);
    }

    // Fetch all loans for a user
    public function getLoansForUser($userId, $status = null)
    {
        $sql = "SELECT * FROM tbl_borrowing_records WHERE borrower_id = :borrower_id";
        $params = [':borrower_id' => $userId];
        if ($status !== null) {
            $sql .= " AND status = :status";
            $params[':status'] = $status;
        }
        $sql .= " ORDER BY borrow_id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get active loan by copy ID (for checking availability)
    public function getActiveLoanByCopyId($copyId)
    {
        $sql = "SELECT * FROM tbl_borrowing_records 
                WHERE copy_id = :copy_id AND status = 'borrowed'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':copy_id' => $copyId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Fetch a loan by its primary key
    public function getLoanById($loanId)
    {
        $sql = "SELECT * FROM tbl_borrowing_records WHERE borrow_id = :borrow_id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':borrow_id' => $loanId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Update status (e.g., overdue, lost, etc.)
    public function updateLoanStatus($loanId, $status)
    {
        $sql = "UPDATE tbl_borrowing_records SET status = :status, updated_at = NOW() WHERE borrow_id = :borrow_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':status' => $status,
            ':borrow_id' => $loanId,
        ]);
    }

    // Delete a loan record (admin or cleanup)
    public function deleteLoan($loanId)
    {
        $sql = "DELETE FROM tbl_borrowing_records WHERE borrow_id = :borrow_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':borrow_id' => $loanId]);
    }

    // List all loans for admin, optionally filter by status
    public function listLoans($status = null)
    {
        $sql = "SELECT * FROM tbl_borrowing_records";
        $params = [];
        if ($status !== null) {
            $sql .= " WHERE status = :status";
            $params[':status'] = $status;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Fetch all borrowings that are overdue (not returned and due_date before today)
    public function getOverdueBorrowings()
    {
        $sql = "SELECT borrow_id, borrower_id, due_date
            FROM tbl_borrowing_records
            WHERE status = 'borrowed' 
              AND due_date < CURDATE()
              AND (return_date IS NULL)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Check if user has pending fines
    public function hasPendingFines($borrowerId)
    {
        $sql = "SELECT COUNT(*) FROM tbl_fines WHERE borrower_id = :borrower_id AND status = 'pending'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':borrower_id' => $borrowerId]);
        return $stmt->fetchColumn() > 0;
    }

    // Get count of currently borrowed books by user
    public function getCurrentBorrowedCount($borrowerId)
    {
        $sql = "SELECT COUNT(*) FROM tbl_borrowing_records WHERE borrower_id = :borrower_id AND status = 'borrowed'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':borrower_id' => $borrowerId]);
        return $stmt->fetchColumn();
    }

    // Get book_id from copy_id (for business checks)
    public function getBookIdByCopyId($copyId)
    {
        $sql = "SELECT book_id FROM tbl_book_copies WHERE copy_id = :copy_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':copy_id' => $copyId]);
        return $stmt->fetchColumn() ?: null;
    }

    // Check if user has borrowed the same book (by title), not yet returned
    public function hasBorrowedSameBook($borrowerId, $bookId)
    {
        $sql = "SELECT COUNT(*) FROM tbl_borrowing_records br
            INNER JOIN tbl_book_copies bc ON br.copy_id = bc.copy_id
            WHERE br.borrower_id = :borrower_id AND br.status = 'borrowed' AND bc.book_id = :book_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':borrower_id' => $borrowerId, ':book_id' => $bookId]);
        return $stmt->fetchColumn() > 0;
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


    /**
     * Fetch full book details given a copy_id.
     * Returns the same detailed structure as fetchBookById.
     */
    public function getBookDetailsByCopyId($copyId): ?array
    {
        // First, lookup the book_id from the copy_id
        $sql = "SELECT book_id FROM tbl_book_copies WHERE copy_id = :copy_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':copy_id' => $copyId]);
        $bookId = $stmt->fetchColumn();

        if (!$bookId) {
            return null;
        }

        // Now, fetch the full book details just like fetchBookById
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
        $stmt->execute([$bookId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        // Optionally normalize title if that method exists
        if ($row && isset($row['title']) && method_exists($this, 'normalizeTitle')) {
            $row['title'] = $this->normalizeTitle($row['title']);
        }

        return $row;
    }


    // Helper method to get user email by user ID
    private function getUserEmailById($userId): ?string
    {
        $userModel = new UserModel();
        $user = $userModel->getUserById($userId);
        return $user ? $user['email'] : null;
    }

    // Helper method to get book title by copy ID
    private function getBookTitleByCopyId($copyId): ?string
    {
        $booksModel = new BooksModel($this->pdo);
        return $booksModel->getBookTitleByCopyId($copyId);
    }

    // Send loan pending approval email notification
    private function sendLoanPendingApprovalEmail($userId, $copyId): void
    {
        try {
            // Get user email
            $userEmail = $this->getUserEmailById($userId);
            if (!$userEmail) {
                error_log("No email found for user ID: $userId");
                return;
            }

            // Get book title
            $bookTitle = $this->getBookTitleByCopyId($copyId);
            if (!$bookTitle) {
                error_log("No book title found for copy ID: $copyId");
                return;
            }

            // Send email notification
            $emailService = new EmailService();
            $emailService->sendLoanPendingApprovalEmail($userEmail, $bookTitle);
        } catch (Exception $e) {
            // Log error but don't fail the loan creation
            error_log("Failed to send loan pending approval email: " . $e->getMessage());
        }
    }

    public function getUserCopyLoanStatuses(int $userId): array
    {
        $sql = "
        SELECT 
            bc.copy_id,
            br.status AS loan_status
        FROM tbl_book_copies bc
        LEFT JOIN tbl_borrowing_records br 
            ON bc.copy_id = br.copy_id AND br.borrower_id = :user_id
    ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $statusMap = [];
        foreach ($rows as $row) {
            // Only set the status if there is an actual loan record (not just null)
            if (!empty($row['loan_status'])) {
                $statusMap[$row['copy_id']] = $row['loan_status'];
            }
        }
        return $statusMap;
    }
}
