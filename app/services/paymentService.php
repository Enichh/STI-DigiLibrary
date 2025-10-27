<?php
// app/services/paymentService.php

require_once __DIR__ . '/../models/paymentModel.php';

class PaymentService
{
    private $paymentModel;

    public function __construct(PaymentModel $paymentModel)
    {
        $this->paymentModel = $paymentModel;
    }

    // Add a new fine payment
    public function createPayment($fine_id, $amount_paid, $payment_method, $payment_date, $received_by, $transaction_reference = null, $notes = null)
    {
        return $this->paymentModel->createPayment(
            $fine_id,
            $amount_paid,
            $payment_method,
            $payment_date,
            $received_by,
            $transaction_reference,
            $notes
        );
    }

    // Get all payments for a fine
    public function getPaymentsByFineId($fine_id)
    {
        return $this->paymentModel->getPaymentsByFineId($fine_id);
    }

    // Get a payment by its primary key
    public function getPaymentById($payment_id)
    {
        return $this->paymentModel->getPaymentById($payment_id);
    }

    // Update payment details (admin use)
    public function updatePayment($payment_id, $amount_paid, $payment_method, $payment_date, $received_by, $transaction_reference = null, $notes = null)
    {
        return $this->paymentModel->updatePayment(
            $payment_id,
            $amount_paid,
            $payment_method,
            $payment_date,
            $received_by,
            $transaction_reference,
            $notes
        );
    }

    // Delete a payment record (admin only)
    public function deletePayment($payment_id)
    {
        return $this->paymentModel->deletePayment($payment_id);
    }

    // List payments with optional filters (fine_id, received_by, etc.)
    public function listPayments($filters = [])
    {
        return $this->paymentModel->listPayments($filters);
    }
}
