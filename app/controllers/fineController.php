<?php
// app/controllers/fineController.php

require_once __DIR__ . '/../services/fineService.php';

class FineController
{
    private $fineService;

    public function __construct(FineService $fineService)
    {
        $this->fineService = $fineService;
    }

    // POST /fines/process-overdues
    // Triggers auto fine creation/update for overdue loans (e.g. cronjob, admin)
    public function processOverdues()
    {
        try {
            $this->fineService->processOverdueFines();
            echo json_encode(['success' => true]);
        } catch (Exception $ex) {
            http_response_code(500);
            echo json_encode(['error' => $ex->getMessage()]);
        }
    }

    // GET /fines/user/:userId?status=pending
    public function userFines($userId)
    {
        $status = $_GET['status'] ?? null;
        $fines = $this->fineService->getFinesForUser($userId, $status);
        echo json_encode(['fines' => $fines]);
    }

    // GET /fines/:fineId
    public function getFine($fineId)
    {
        $fine = $this->fineService->getFineById($fineId);
        if ($fine) {
            echo json_encode(['fine' => $fine]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Fine not found']);
        }
    }

    // POST /fines/:fineId/pay
    public function markPaid($fineId)
    {
        try {
            $paidDate = $_POST['paid_date'] ?? null;
            $this->fineService->markFinePaid($fineId, $paidDate);
            echo json_encode(['success' => true]);
        } catch (Exception $ex) {
            http_response_code(400);
            echo json_encode(['error' => $ex->getMessage()]);
        }
    }

    // POST /fines/:fineId/waive
    public function waive($fineId)
    {
        try {
            $this->fineService->waiveFine($fineId);
            echo json_encode(['success' => true]);
        } catch (Exception $ex) {
            http_response_code(400);
            echo json_encode(['error' => $ex->getMessage()]);
        }
    }

    // DELETE /fines/:fineId
    public function delete($fineId)
    {
        try {
            $this->fineService->deleteFine($fineId);
            echo json_encode(['success' => true]);
        } catch (Exception $ex) {
            http_response_code(400);
            echo json_encode(['error' => $ex->getMessage()]);
        }
    }

    // GET /fines?status=pending (admin/all fines)
    public function allFines()
    {
        $status = $_GET['status'] ?? null;
        $fines = $this->fineService->listFines($status);
        echo json_encode(['fines' => $fines]);
    }
}
