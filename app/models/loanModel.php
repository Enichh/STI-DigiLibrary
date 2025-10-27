<?php
// app/models/loanModel.php

class LoanModel
{
    private $pdo;

    // Dependency Injection: receive PDO externally for testability and loose coupling
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Create a new loan record (borrowing)
    public function createLoan($userId, $copyId, $borrowedDate, $dueDate, $remarks = null)
    {
        $sql = "INSERT INTO tbl_borrowing_records 
            (borrowerid, copyid, borroweddate, duedate, status, remarks) 
            VALUES (:user_id, :copy_id, :borrowed_date, :due_date, 'borrowed', :remarks)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':copy_id' => $copyId,
            ':borrowed_date' => $borrowedDate,
            ':due_date' => $dueDate,
            ':remarks' => $remarks,
        ]);
    }

    // Mark a loan as returned (admin action)
    public function completeLoanReturn($loanId, $returnDate, $remarks = null)
    {
        $sql = "UPDATE tbl_borrowing_records 
            SET returndate = :return_date, status = 'returned', remarks = :remarks 
            WHERE borrowid = :loan_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':return_date' => $returnDate,
            ':remarks' => $remarks,
            ':loan_id' => $loanId,
        ]);
    }

    // Fetch all loans for a user
    public function getLoansForUser($userId, $status = null)
    {
        $sql = "SELECT * FROM tbl_borrowing_records WHERE borrowerid = :user_id";
        $params = [':user_id' => $userId];
        if ($status !== null) {
            $sql .= " AND status = :status";
            $params[':status'] = $status;
        }
        $sql .= " ORDER BY borrowid DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get active loan by copy ID (for checking availability)
    public function getActiveLoanByCopyId($copyId)
    {
        $sql = "SELECT * FROM tbl_borrowing_records 
                WHERE copyid = :copy_id AND status = 'borrowed'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':copy_id' => $copyId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Fetch a loan by its primary key
    public function getLoanById($loanId)
    {
        $sql = "SELECT * FROM tbl_borrowing_records WHERE borrowid = :loan_id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':loan_id' => $loanId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Update status (e.g., overdue, lost, etc.)
    public function updateLoanStatus($loanId, $status)
    {
        $sql = "UPDATE tbl_borrowing_records SET status = :status WHERE borrowid = :loan_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':status' => $status,
            ':loan_id' => $loanId,
        ]);
    }

    // Delete a loan record (admin or cleanup)
    public function deleteLoan($loanId)
    {
        $sql = "DELETE FROM tbl_borrowing_records WHERE borrowid = :loan_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':loan_id' => $loanId]);
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
        $sql = "SELECT COUNT(*) FROM tbl_borrowing_records WHERE borrowerid = :borrower_id AND status = 'borrowed'";
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
            INNER JOIN tbl_book_copies bc ON br.copyid = bc.copyid
            WHERE br.borrowerid = :borrower_id AND br.status = 'borrowed' AND bc.book_id = :book_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':borrower_id' => $borrowerId, ':book_id' => $bookId]);
        return $stmt->fetchColumn() > 0;
    }
}
