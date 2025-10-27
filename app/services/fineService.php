<?php
// app/services/fineService.php

require_once __DIR__ . '/../models/fineModel.php';
require_once __DIR__ . '/../models/loanModel.php';

class FineService
{
    private $fineModel;
    private $loanModel;

    public function __construct(FineModel $fineModel, LoanModel $loanModel)
    {
        $this->fineModel = $fineModel;
        $this->loanModel = $loanModel;
    }

    // Helper to count weekdays between two dates (exclusive start, exclusive end)
    // used for fine calculation
    private function countOverdueWeekdays($start, $end)
    {
        $startDate = new DateTime($start);
        $endDate = new DateTime($end);
        $startDate->modify('+1 day'); // start counting after due date
        $weekdays = 0;
        while ($startDate < $endDate) {
            $weekday = $startDate->format('N');
            if ($weekday < 6) { // 1=Mon, 5=Fri
                $weekdays++;
            }
            $startDate->modify('+1 day');
        }
        return $weekdays;
    }

    // Process all overdue borrowings and create/update fines
    public function processOverdueFines()
    {
        $today = date('Y-m-d');
        $overdues = $this->loanModel->getOverdueBorrowings();

        foreach ($overdues as $loan) {
            // Only fine if status still 'borrowed'
            if ($loan['due_date'] < $today && $loan['status'] === 'borrowed') {
                $borrow_id = $loan['borrow_id'];
                $borrower_id = $loan['borrower_id'];

                // Fine amount: 10 pesos per overdue weekday (excluding weekends)
                $overdueDays = $this->countOverdueWeekdays($loan['due_date'], $today);
                if ($overdueDays <= 0) continue;

                $amount = $overdueDays * 10;
                $existingFine = $this->fineModel->findPendingFine($borrow_id, 'overdue');

                if ($existingFine) {
                    // Optionally update amount if overdueDays increases
                    if ($existingFine['amount'] != $amount) {
                        $this->fineModel->updateFineAmount($existingFine['fine_id'], $amount);
                    }
                } else {
                    // Create new overdue fine
                    $this->fineModel->createFine($borrow_id, $borrower_id, $amount, 'overdue');
                }
            }
        }
    }

    // Expose other fine workflows as needed, i.e. mark fine paid, waive, etc.
    public function markFinePaid($fineId, $paidDate = null)
    {
        $this->fineModel->updateFineStatus($fineId, 'paid');
        $this->fineModel->setPaidDate($fineId, $paidDate ?? date('Y-m-d H:i:s'));
    }

    public function waiveFine($fineId)
    {
        $this->fineModel->updateFineStatus($fineId, 'waived');
    }
    public function deleteFine($fineId)
    {
        $this->fineModel->deleteFine($fineId);
    }
    public function listFines($status = null)
    {
        $this->fineModel->listFines($status);
    }
    public function getFineById($fineId)
    {
        $this->fineModel->getFineById($fineId);
    }
    public function getFinesForUser($borrower_id, $status = null)
    {
        $this->fineModel->getFinesForUser($borrower_id, $status);
    }
    public function findPendingFine($borrow_id, $fine_type)
    {
        $this->fineModel->findPendingFine($borrow_id, $fine_type);
    }
    public function updateFineAmount($fine_id, $amount)
    {
        $this->fineModel->updateFineAmount($fine_id, $amount);
    }
}
