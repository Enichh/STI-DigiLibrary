<?php
// app/routes/paymentRoutes.php

require_once __DIR__ . '/../controllers/paymentController.php';
require_once __DIR__ . '/../services/paymentService.php';
require_once __DIR__ . '/../models/paymentModel.php';
require_once __DIR__ . '/../../config/database.php';

/**
 * Handles fine payment API routes.
 *
 * @param string $requestPath The request URI path
 * @param string $method The HTTP request method
 * @return void
 */
function handlePaymentRoutes(string $requestPath, string $method): void
{
    global $matched;

    $pdo = getPDO();
    $paymentModel = new PaymentModel($pdo);
    $paymentService = new PaymentService($paymentModel);
    $paymentController = new PaymentController($paymentService);

    $basePath = '/payments';

    if (strpos($requestPath, $basePath) === 0) {
        $path = parse_url($requestPath, PHP_URL_PATH);
        $endpoint = rtrim(substr($path, strlen($basePath)), '/');

        // CORS handling
        if ($method === 'OPTIONS') {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
            http_response_code(204);
            $matched = true;
            return;
        }

        try {
            switch (true) {
                // POST /payments → Create new payment
                case ($endpoint === '' && $method === 'POST'):
                    $paymentController->create();
                    $matched = true;
                    return;

                    // GET /payments/fine/{fineId} → Get payments for a specific fine
                case (preg_match('#^/fine/(\d+)$#', $endpoint, $matches) && $method === 'GET'):
                    $paymentController->paymentsByFine((int)$matches[1]);
                    $matched = true;
                    return;

                    // GET /payments/{paymentId} → Get payment details
                case (preg_match('#^/(\d+)$#', $endpoint, $matches) && $method === 'GET'):
                    $paymentController->get((int)$matches[1]);
                    $matched = true;
                    return;

                    // PUT /payments/{paymentId} → Update payment details
                case (preg_match('#^/(\d+)$#', $endpoint, $matches) && $method === 'PUT'):
                    $paymentController->update((int)$matches[1]);
                    $matched = true;
                    return;

                    // DELETE /payments/{paymentId} → Delete a payment
                case (preg_match('#^/(\d+)$#', $endpoint, $matches) && $method === 'DELETE'):
                    $paymentController->delete((int)$matches[1]);
                    $matched = true;
                    return;

                    // GET /payments?fine_id=...&received_by=... → List payments with filters (admin)
                case ($endpoint === '' && $method === 'GET'):
                    $paymentController->allPayments();
                    $matched = true;
                    return;

                    // Fallback: unmatched endpoint
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Payment endpoint not found']);
                    $matched = true;
                    return;
            }
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Internal Server Error',
                'message' => $e->getMessage(),
            ]);
            $matched = true;
        }
    }
}
