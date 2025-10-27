<?php
// libraryIdModel.php

class LibraryIdModel
{
    private $pdo;

    // Dependency Injection: receive PDO externally for testability and loose coupling
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }


    private function generateLibraryIdNumber()
    {
        // Get the current year
        $currentYear = date('Y');

        // Find the highest existing library_id_number for the current year
        $sql = "SELECT library_id_number FROM tbl_library_ids 
            WHERE library_id_number LIKE :prefix
            ORDER BY library_id_number DESC 
            LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':prefix' => $currentYear . '-%']);
        $lastId = $stmt->fetchColumn();

        // If there's a previous ID, extract and increment its numeric part
        if ($lastId) {
            $lastSequence = (int)substr($lastId, 5);
            $newSequence = str_pad($lastSequence + 1, 5, '0', STR_PAD_LEFT);
        } else {
            // Start counting from 00001 if none exists this year
            $newSequence = '00001';
        }

        // Combine the year with the padded sequence
        return $currentYear . '-' . $newSequence;
    }


    private function getUserEmailById($userId): ?string
    {
        $sql = "SELECT email FROM tbl_users WHERE user_id = :user_id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $email = $stmt->fetchColumn();

        return $email ?: null;
    }

    private function getUserNameById($userId): ?string
    {
        $sql = "SELECT firstName, lastName FROM tbl_studentdetails WHERE user_id = :user_id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['firstName'])) {
            return trim($row['firstName'] . ' ' . ($row['lastName'] ?? ''));
        }
        return null;
    }

    public function approveLibraryId($libraryIdId, $issueYearMonth = null, $expiryYearMonth = null)
    {
        // Update status to 'active'
        $this->updateLibraryIdStatus($libraryIdId, 'active');

        // Set issue/expiry dates if provided
        if ($issueYearMonth && $expiryYearMonth) {
            $this->setLibraryIdDates($libraryIdId, $issueYearMonth, $expiryYearMonth);
        }

        // Get Library ID record (to get user_id and library_id_number)
        $record = $this->getLibraryIdById($libraryIdId);
        if (!$record) {
            return false; // ID not found
        }

        $userId = $record['user_id'];
        $libraryIdNumber = $record['library_id_number'];

        $userEmail = $this->getUserEmailById($userId);
        $userName = $this->getUserNameById($userId);

        if ($userEmail && $libraryIdNumber) {
            require_once __DIR__ . '/../services/emailService.php';
            $emailService = new EmailService();
            $emailService->sendLibraryIdApprovalEmail($userEmail, $userName, $libraryIdNumber);
        }

        return true;
    }





    public function createLibraryIdRequest($userId, $remarks = null)
    {
        $libraryIdNumber = $this->generateLibraryIdNumber();

        $sql = "INSERT INTO tbl_library_ids 
            (library_id_number, user_id, status, remarks) 
            VALUES (:library_id_number, :user_id, :status, :remarks)";

        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([
            ':library_id_number' => $libraryIdNumber,
            ':user_id' => $userId,
            ':status' => 'pending',
            ':remarks' => $remarks,
        ]);

        // If insertion was successful, send a confirmation email
        if ($success) {
            $userEmail = $this->getUserEmailById($userId);
            $userName = $this->getUserNameById($userId);

            if ($userEmail) {
                require_once __DIR__ . '/../services/emailService.php';
                $emailService = new EmailService();
                $emailService->sendLibraryIdApplicationConfirmation($userEmail, $userName);
            }
        }

        return $success;
    }



    // Get all IDs for user (optionally filter active)
    public function getLibraryIdsForUser($userId, $onlyActive = false)
    {
        $sql = "SELECT * FROM tbl_library_ids WHERE user_id = :user_id";
        if ($onlyActive) {
            $sql .= " AND status = 'active'";
        }
        $sql .= " ORDER BY library_id_id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get single Library ID by its primary key (library_id_id)
    public function getLibraryIdById($libraryIdId)
    {
        $sql = "SELECT * FROM tbl_library_ids WHERE library_id_id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $libraryIdId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Update status (for approval, suspension, etc.)
    public function updateLibraryIdStatus($libraryIdId, $status)
    {
        $sql = "UPDATE tbl_library_ids SET status = :status WHERE library_id_id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':status' => $status,
            ':id' => $libraryIdId,
        ]);
    }

    // Set issue/expiry dates (admin action on approval)
    public function setLibraryIdDates($libraryIdId, $issueYearMonth, $expiryYearMonth)
    {
        $sql = "UPDATE tbl_library_ids SET issue_year_month = :issue, expiry_year_month = :expiry WHERE library_id_id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':issue' => $issueYearMonth,
            ':expiry' => $expiryYearMonth,
            ':id' => $libraryIdId,
        ]);
    }

    // Delete a Library ID record (admin or cleanup)
    public function deleteLibraryId($libraryIdId)
    {
        $sql = "DELETE FROM tbl_library_ids WHERE library_id_id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $libraryIdId]);
    }

    // List all Library IDs for admin viewing (optionally filtered by status)
    public function listLibraryIds($status = null)
    {
        $sql = "SELECT * FROM tbl_library_ids";
        $params = [];
        if ($status !== null) {
            $sql .= " WHERE status = :status";
            $params[':status'] = $status;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
