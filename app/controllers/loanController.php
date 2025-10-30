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
            $input = json_decode(file_get_contents("php://input"), true);
            $userId = $input['user_id'] ?? null;
            $copyId = $input['copy_id'] ?? null;

            if (!$userId || !$copyId) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                return;
            }

            // Create the loan
            $result = $this->loanService->borrowBook($userId, $copyId);

            echo json_encode(['success' => $result]);
        } catch (Exception $ex) {
            http_response_code(400);
            echo json_encode(['error' => $ex->getMessage()]);
        }
    }


    // PATCH /api/loans/:loanId/cancel
    public function cancel($loanId)
    {
        try {
            $result = $this->loanService->cancelLoan($loanId);
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

    // GET /api/loans/user/:userId
    public function getUserCopyLoanStatuses($userId)
    {
        $statusMap = $this->loanService->getUserCopyLoanStatuses($userId);
        echo json_encode(['status_map' => $statusMap]);
    }

    // GET /api/loans/book/:copyId
    public function getBookDetailsByCopyId($copyId)
    {
        $bookDetails = $this->loanService->getBookDetailsByCopyId($copyId);
        header('Content-Type: application/json');
        if ($bookDetails) {
            echo json_encode(['book' => $bookDetails]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Book not found']);
        }
    }

    // GET /api/loans/recent
    public function getRecentActivity(): void
    {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
        $activity = $this->loanService->fetchRecentActivity($limit);

        header('Content-Type: application/json');
        echo json_encode([
            "success"  => true,
            "activity" => $activity
        ]);
    }

    // GET /api/loans/upcoming
    public function getUpcomingDueDates(): void
    {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 3;
        $upcoming = $this->loanService->getUpcomingDueDates($limit);

        header('Content-Type: application/json');
        echo json_encode([
            "success"  => true,
            "upcoming" => $upcoming
        ]);
    }

    // GET /api/loans/action-items
    public function getActionItems(): void
    {
        $actionItems = $this->loanService->getActionItems();

        header('Content-Type: application/json');
        echo json_encode([
            "success"      => true,
            "action_items" => $actionItems
        ]);
    }
}
