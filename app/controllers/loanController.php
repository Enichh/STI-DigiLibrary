<?php
// app/controllers/loanController.php

require_once __DIR__ . '/../services/loanService.php';

class LoanController
{
    private $loanService;

    public function __construct(LoanService $loanService)
    {
        $this->loanService = $loanService;
    }

    // POST /api/loans/borrow
    public function borrow()
    {
        try {
            // Basic input validation
            $userId = $_POST['user_id'] ?? null;
            $copyId = $_POST['copy_id'] ?? null;
            $remarks = $_POST['remarks'] ?? null;

            if (!$userId || !$copyId) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                return;
            }

            // Service sets due date & handles logic
            $result = $this->loanService->borrowBook($userId, $copyId, $remarks);
            echo json_encode(['success' => $result]);
        } catch (Exception $ex) {
            http_response_code(400);
            echo json_encode(['error' => $ex->getMessage()]);
        }
    }

    // POST /api/loans/return
    public function return()
    {
        try {
            $loanId = $_POST['loan_id'] ?? null;
            $remarks = $_POST['remarks'] ?? null;

            if (!$loanId) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing loan_id']);
                return;
            }

            $result = $this->loanService->returnBook($loanId, $remarks);
            echo json_encode(['success' => $result]);
        } catch (Exception $ex) {
            http_response_code(400);
            echo json_encode(['error' => $ex->getMessage()]);
        }
    }

    // GET /api/loans/user/:userId
    public function userLoans($userId)
    {
        $status = $_GET['status'] ?? null;
        $loans = $this->loanService->getUserLoans($userId, $status);
        echo json_encode(['loans' => $loans]);
    }

    // GET /api/loans/:loanId
    public function getLoan($loanId)
    {
        $loan = $this->loanService->getLoan($loanId);
        if ($loan) {
            echo json_encode(['loan' => $loan]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Loan not found']);
        }
    }

    // GET /api/loans?status=borrowed
    public function allLoans()
    {
        $status = $_GET['status'] ?? null;
        $loans = $this->loanService->listAllLoans($status);
        echo json_encode(['loans' => $loans]);
    }

    // DELETE /api/loans/:loanId
    public function delete($loanId)
    {
        try {
            $result = $this->loanService->deleteLoan($loanId);
            echo json_encode(['success' => $result]);
        } catch (Exception $ex) {
            http_response_code(400);
            echo json_encode(['error' => $ex->getMessage()]);
        }
    }
}
