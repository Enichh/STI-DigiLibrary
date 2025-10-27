<?php
// app/models/fineModel.php

class FineModel
{
    private $pdo;

    // Dependency injection: PDO is passed for loose coupling
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Create a new fine (automatically sets to pending)
    public function createFine($borrow_id, $borrower_id, $amount, $fine_type, $description = null, $issue_date = null)
    {
        $sql = "INSERT INTO tbl_fines 
                (borrow_id, borrower_id, amount, fine_type, status, issue_date, description)
                VALUES (:borrow_id, :borrower_id, :amount, :fine_type, 'pending', :issue_date, :description)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':borrow_id'   => $borrow_id,
            ':borrower_id' => $borrower_id,
            ':amount'      => $amount,
            ':fine_type'   => $fine_type,
            ':issue_date'  => $issue_date ?? date('Y-m-d H:i:s'),
            ':description' => $description,
        ]);
    }

    // Fetch a fine by its ID
    public function getFineById($fine_id)
    {
        $sql = "SELECT * FROM tbl_fines WHERE fine_id = :fine_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':fine_id' => $fine_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get all fines for a borrower, optionally filter by status
    public function getFinesForUser($borrower_id, $status = null)
    {
        $sql = "SELECT * FROM tbl_fines WHERE borrower_id = :borrower_id";
        $params = [':borrower_id' => $borrower_id];

        if ($status !== null) {
            $sql .= " AND status = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY fine_id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Update fine status (pending, paid, waived)
    public function updateFineStatus($fine_id, $status)
    {
        $sql = "UPDATE tbl_fines SET status = :status WHERE fine_id = :fine_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':status' => $status,
            ':fine_id' => $fine_id,
        ]);
    }

    // Update paid_date when a fine is settled
    public function setPaidDate($fine_id, $paid_date)
    {
        $sql = "UPDATE tbl_fines SET paid_date = :paid_date WHERE fine_id = :fine_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':paid_date' => $paid_date,
            ':fine_id'   => $fine_id,
        ]);
    }

    // Delete a fine (admin action or cleanup)
    public function deleteFine($fine_id)
    {
        $sql = "DELETE FROM tbl_fines WHERE fine_id = :fine_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':fine_id' => $fine_id]);
    }

    // List all fines (system-wide for admin); optional filter by status
    public function listFines($status = null)
    {
        $sql = "SELECT * FROM tbl_fines";
        $params = [];

        if ($status !== null) {
            $sql .= " WHERE status = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY fine_id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Find a pending fine for a specific borrow record and fine type
    public function findPendingFine($borrow_id, $fine_type)
    {
        $sql = "SELECT * FROM tbl_fines 
                WHERE borrow_id = :borrow_id AND fine_type = :fine_type AND status = 'pending'
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':borrow_id' => $borrow_id, ':fine_type' => $fine_type]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Update fine amount
    public function updateFineAmount($fine_id, $amount)
    {
        $sql = "UPDATE tbl_fines SET amount = :amount WHERE fine_id = :fine_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':amount' => $amount,
            ':fine_id' => $fine_id,
        ]);
    }
}
