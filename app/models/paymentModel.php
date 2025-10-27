<?php
// app/models/paymentModel.php

class PaymentModel
{
    private $pdo;

    // Dependency Injection: PDO connection for testability and loose coupling
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Create a new fine payment record
    public function createPayment($fine_id, $amount_paid, $payment_method, $payment_date, $received_by, $transaction_reference = null, $notes = null)
    {
        $sql = "INSERT INTO tbl_fine_payments 
            (fine_id, amount_paid, payment_method, payment_date, received_by, transaction_reference, notes, created_at)
            VALUES (:fine_id, :amount_paid, :payment_method, :payment_date, :received_by, :transaction_reference, :notes, NOW())";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':fine_id' => $fine_id,
            ':amount_paid' => $amount_paid,
            ':payment_method' => $payment_method,
            ':payment_date' => $payment_date,
            ':received_by' => $received_by,
            ':transaction_reference' => $transaction_reference,
            ':notes' => $notes
        ]);
    }

    // Get all payments for a fine
    public function getPaymentsByFineId($fine_id)
    {
        $sql = "SELECT * FROM tbl_fine_payments WHERE fine_id = :fine_id ORDER BY payment_date DESC, payment_id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':fine_id' => $fine_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get a payment by its primary key
    public function getPaymentById($payment_id)
    {
        $sql = "SELECT * FROM tbl_fine_payments WHERE payment_id = :payment_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':payment_id' => $payment_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Update payment details (for corrections or admin)
    public function updatePayment($payment_id, $amount_paid, $payment_method, $payment_date, $received_by, $transaction_reference = null, $notes = null)
    {
        $sql = "UPDATE tbl_fine_payments SET 
                    amount_paid = :amount_paid,
                    payment_method = :payment_method,
                    payment_date = :payment_date,
                    received_by = :received_by,
                    transaction_reference = :transaction_reference,
                    notes = :notes
                WHERE payment_id = :payment_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':amount_paid' => $amount_paid,
            ':payment_method' => $payment_method,
            ':payment_date' => $payment_date,
            ':received_by' => $received_by,
            ':transaction_reference' => $transaction_reference,
            ':notes' => $notes,
            ':payment_id' => $payment_id
        ]);
    }

    // Delete a payment record (admin only)
    public function deletePayment($payment_id)
    {
        $sql = "DELETE FROM tbl_fine_payments WHERE payment_id = :payment_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':payment_id' => $payment_id]);
    }

    // List all payments (optionally filtered by generic criteria – for future extensibility)
    public function listPayments($filters = [])
    {
        $sql = "SELECT * FROM tbl_fine_payments WHERE 1=1";
        $params = [];
        if (!empty($filters['fine_id'])) {
            $sql .= " AND fine_id = :fine_id";
            $params[':fine_id'] = $filters['fine_id'];
        }
        if (!empty($filters['received_by'])) {
            $sql .= " AND received_by = :received_by";
            $params[':received_by'] = $filters['received_by'];
        }
        // You can add more filters as needed
        $sql .= " ORDER BY payment_date DESC, payment_id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
