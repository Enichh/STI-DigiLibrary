<?php
// app/controllers/paymentController.php

require_once __DIR__ . '/../services/paymentService.php';

class PaymentController
{
    private $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    // POST /payments
    // Create a new payment record for a fine
    public function create()
    {
        try {
            $fine_id = $_POST['fine_id'] ?? null;
            $amount_paid = $_POST['amount_paid'] ?? null;
            $payment_method = $_POST['payment_method'] ?? null;
            $payment_date = $_POST['payment_date'] ?? null;
            $received_by = $_POST['received_by'] ?? null;
            $transaction_reference = $_POST['transaction_reference'] ?? null;
            $notes = $_POST['notes'] ?? null;

            if (!$fine_id || !$amount_paid || !$payment_method || !$payment_date || !$received_by) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                return;
            }

            $result = $this->paymentService->createPayment(
                $fine_id,
                $amount_paid,
                $payment_method,
                $payment_date,
                $received_by,
                $transaction_reference,
                $notes
            );
            echo json_encode(['success' => $result]);
        } catch (Exception $ex) {
            http_response_code(400);
            echo json_encode(['error' => $ex->getMessage()]);
        }
    }

    // GET /payments/fine/{fineId}
    // List all payments for a fine
    public function paymentsByFine($fineId)
    {
        $payments = $this->paymentService->getPaymentsByFineId($fineId);
        echo json_encode(['payments' => $payments]);
    }

    // GET /payments/{paymentId}
    // Get details for a specific payment
    public function get($paymentId)
    {
        $payment = $this->paymentService->getPaymentById($paymentId);
        if ($payment) {
            echo json_encode(['payment' => $payment]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Payment not found']);
        }
    }

    // PUT /payments/{paymentId}
    // Update payment information (admin only)
    public function update($paymentId)
    {
        parse_str(file_get_contents('php://input'), $input); // For PUT data
        $amount_paid = $input['amount_paid'] ?? null;
        $payment_method = $input['payment_method'] ?? null;
        $payment_date = $input['payment_date'] ?? null;
        $received_by = $input['received_by'] ?? null;
        $transaction_reference = $input['transaction_reference'] ?? null;
        $notes = $input['notes'] ?? null;

        if (!$amount_paid || !$payment_method || !$payment_date || !$received_by) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            return;
        }

        try {
            $result = $this->paymentService->updatePayment(
                $paymentId,
                $amount_paid,
                $payment_method,
                $payment_date,
                $received_by,
                $transaction_reference,
                $notes
            );
            echo json_encode(['success' => $result]);
        } catch (Exception $ex) {
            http_response_code(400);
            echo json_encode(['error' => $ex->getMessage()]);
        }
    }

    // DELETE /payments/{paymentId}
    // Delete a payment record
    public function delete($paymentId)
    {
        try {
            $result = $this->paymentService->deletePayment($paymentId);
            echo json_encode(['success' => $result]);
        } catch (Exception $ex) {
            http_response_code(400);
            echo json_encode(['error' => $ex->getMessage()]);
        }
    }

    // GET /payments?fine_id=...&received_by=... (admin list, with filters)
    public function allPayments()
    {
        $filters = [];
        if (!empty($_GET['fine_id'])) {
            $filters['fine_id'] = $_GET['fine_id'];
        }
        if (!empty($_GET['received_by'])) {
            $filters['received_by'] = $_GET['received_by'];
        }
        $payments = $this->paymentService->listPayments($filters);
        echo json_encode(['payments' => $payments]);
    }
}
