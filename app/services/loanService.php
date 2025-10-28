<?php
// app/services/loanService.php

require_once __DIR__ . '/../models/loanModel.php';

class LoanService
{
    private $loanModel;

    public function __construct(LoanModel $loanModel)
    {
        $this->loanModel = $loanModel;
    }

    // Helper for calculating due date (+3 weekdays)
    private function addWeekdays($startDate, $days)
    {
        $date = new DateTime($startDate);
        $added = 0;
        while ($added < $days) {
            $date->modify('+1 day');
            if ($date->format('N') < 6) { // Monday=1..Friday=5
                $added++;
            }
        }
        return $date->format('Y-m-d');
    }

    // Borrowing request (for students) — dueDate is *always* 3 weekdays from today
    public function borrowBook($userId, $copyId, $remarks = null)
    {
        if ($this->loanModel->hasPendingFines($userId)) {
            throw new Exception('Cannot borrow: You have pending fines.');
        }
        if ($this->loanModel->getCurrentBorrowedCount($userId) >= 3) {
            throw new Exception('Cannot borrow: Maximum of 3 books already borrowed.');
        }
        $bookId = $this->loanModel->getBookIdByCopyId($copyId);
        if (!$bookId) {
            throw new Exception('Invalid book copy.');
        }
        if ($this->loanModel->hasBorrowedSameBook($userId, $bookId)) {
            throw new Exception('Cannot borrow: Already borrowed a copy of this book.');
        }
        if ($this->loanModel->getActiveLoanByCopyId($copyId)) {
            throw new Exception('Book copy is already borrowed.');
        }

        $borrowedDate = date('Y-m-d');
        $dueDate = $this->addWeekdays($borrowedDate, 3); // 3 weekdays from today
        $borrowedDateFull = date('Y-m-d H:i:s');
        return $this->loanModel->createLoan($userId, $copyId, $borrowedDateFull, $dueDate, $remarks);
    }

    public function returnBook($loanId, $remarks = null)
    {
        $loan = $this->loanModel->getLoanById($loanId);
        if (!$loan) {
            throw new Exception('Loan record not found.');
        }
        if ($loan['status'] !== 'borrowed') {
            throw new Exception('Book is not currently borrowed or already returned.');
        }

        $returnDate = date('Y-m-d H:i:s');
        return $this->loanModel->completeLoanReturn($loanId, $returnDate, $remarks);
    }

    public function getUserLoans($userId, $status = null)
    {
        return $this->loanModel->getLoansForUser($userId, $status);
    }

    public function getLoan($loanId)
    {
        return $this->loanModel->getLoanById($loanId);
    }

    public function updateLoanStatus($loanId, $status)
    {
        return $this->loanModel->updateLoanStatus($loanId, $status);
    }

    public function listAllLoans($status = null)
    {
        return $this->loanModel->listLoans($status);
    }

    public function deleteLoan($loanId)
    {
        return $this->loanModel->deleteLoan($loanId);
    }

    public function cancelLoan($loanId)
    {
        $loan = $this->loanModel->getLoanById($loanId);
        if (!$loan) {
            throw new Exception('Loan record not found.');
        }
        if ($loan['status'] !== 'pending') {
            throw new Exception('Only pending requests can be canceled.');
        }
        return $this->loanModel->cancelLoan($loanId);
    }

    public function getUserCopyLoanStatuses(int $userId): array
    {
        return $this->loanModel->getUserCopyLoanStatuses($userId);
    }

    /**
     * Fetch full book details given a copyId.
     * Returns the same detailed structure as fetchBookById.
     */
    public function getBookDetailsByCopyId($copyId): ?array
    {
        // First, lookup the book_id from the copy_id
        $bookId = $this->loanModel->getBookIdByCopyId($copyId); // Reuse existing model function if available

        if (!$bookId) {
            return null;
        }

        // Now, fetch the full book details just like fetchBookById
        $bookDetails = $this->loanModel->fetchBookById($bookId); // Ensure this returns complete details including genre/authors

        return $bookDetails;
    }
}
