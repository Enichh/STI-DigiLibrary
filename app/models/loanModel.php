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
}
