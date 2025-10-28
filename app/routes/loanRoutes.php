<?php
// app/routes/loanRoutes.php

require_once __DIR__ . '/../controllers/loanController.php';
require_once __DIR__ . '/../services/loanService.php';
require_once __DIR__ . '/../models/loanModel.php';
require_once __DIR__ . '/../../config/database.php';

/**
 * Handles loan-related API routes.
 *
 * @param string $requestPath The request URI path
 * @param string $method The HTTP request method
 * @return void
 */
function handleLoanRoutes(string $requestPath, string $method): void
{
    global $matched;

    $pdo = getPDO();
    $loanModel = new LoanModel($pdo);
    $loanService = new LoanService($loanModel, $pdo);
    $loanController = new LoanController($loanService);

    $basePath = '/loans';

    if (strpos($requestPath, $basePath) === 0) {
        $path = parse_url($requestPath, PHP_URL_PATH);
        $endpoint = rtrim(substr($path, strlen($basePath)), '/');

        // CORS preflight
        if ($method === 'OPTIONS') {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
            http_response_code(204);
            $matched = true;
            return;
        }

        try {
            switch (true) {
                // POST /loans/borrow → Student borrows a book
                case ($endpoint === '/borrow' && $method === 'POST'):
                    $loanController->borrow();
                    $matched = true;
                    return;

                    // POST /loans/return → Admin marks loan as returned
                case ($endpoint === '/return' && $method === 'POST'):
                    $loanController->return();
                    $matched = true;
                    return;

                    // GET /loans/user/{userId} → Get user's loans (optional status param)
                case (preg_match('#^/user/(\d+)$#', $endpoint, $matches) && $method === 'GET'):
                    $loanController->userLoans((int)$matches[1]);
                    $matched = true;
                    return;

                    // GET /loans/{loanId} → Get details for a single loan
                case (preg_match('#^/(\d+)$#', $endpoint, $matches) && $method === 'GET'):
                    $loanController->getLoan((int)$matches[1]);
                    $matched = true;
                    return;

                    // GET /loans → Admin: list all loans (optionally by status)
                case ($endpoint === '' && $method === 'GET'):
                    $loanController->allLoans();
                    $matched = true;
                    return;

                    // DELETE /loans/{loanId} → Admin deletes/cancels a loan
                case (preg_match('#^/(\d+)$#', $endpoint, $matches) && $method === 'DELETE'):
                    $loanController->delete((int)$matches[1]);
                    $matched = true;
                    return;

                    // PATCH /loans/{loanId}/cancel → User cancels a pending loan
                case (preg_match('#^/(\d+)/cancel$#', $endpoint, $matches) && $method === 'PATCH'):
                    $loanController->cancel((int)$matches[1]);
                    $matched = true;
                    return;


                    // GET /loans/book/{copyId}
                case (preg_match('#^/book/(\d+)$#', $endpoint, $matches) && $method === 'GET'):
                    $loanController->getBookDetailsByCopyId((int)$matches[1]);
                    $matched = true;
                    return;

                case (preg_match('#^/user/(\d+)$#', $endpoint, $matches) && $method === 'GET'):
                    $loanController->getUserCopyLoanStatuses((int)$matches[1]);
                    $matched = true;
                    return;


                    // Handle unmatched endpoints
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Loan endpoint not found']);
                    $matched = true;
                    return;
            }
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Internal Server Error',
                'message' => $e->getMessage()
            ]);
            $matched = true;
        }
    }
}
